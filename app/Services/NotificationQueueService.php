<?php
declare(strict_types=1);

namespace App\Services;

use App\Config\App;
use App\Config\Database;
use App\Config\Env;
use PDO;
use Exception;

class NotificationQueueService
{
    /**
     * Enqueue a notification job with idempotency check.
     */
    public static function enqueue(array $job): ?int
    {
        $pdo = Database::getConnection();
        
        $eventType = $job['event_type'] ?? 'system.general';
        $recipientType = $job['recipient_type'] ?? 'Applicant';
        $recipientId = $job['recipient_id'] ?? null;
        $recipientName = $job['recipient_name'] ?? null;
        $recipientEmail = $job['recipient_email'] ?? null;
        $recipientPhone = $job['recipient_phone'] ?? null;
        $channel = $job['channel'] ?? 'Email';
        $templateName = $job['template_name'] ?? null;
        $idempotencyKey = $job['idempotency_key'] ?? null;
        $payloadJson = is_string($job['payload'] ?? null) ? $job['payload'] : json_encode($job['payload'] ?? []);
        $maxRetries = (int)Env::get('NOTIFICATION_MAX_RETRIES', 3);

        // Check idempotency if key provided
        if (!empty($idempotencyKey)) {
            $stmtCheck = $pdo->prepare("SELECT id, status FROM notification_queue WHERE idempotency_key = ?");
            $stmtCheck->execute([$idempotencyKey]);
            $existing = $stmtCheck->fetch(PDO::FETCH_ASSOC);
            if ($existing) {
                return (int)$existing['id'];
            }
        }

        $stmt = $pdo->prepare("INSERT INTO notification_queue 
            (event_type, recipient_type, recipient_id, recipient_name, recipient_email, recipient_phone, channel, template_name, idempotency_key, payload_json, status, max_retries, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Pending', ?, CURRENT_TIMESTAMP)");
        
        $stmt->execute([
            $eventType,
            $recipientType,
            $recipientId,
            $recipientName,
            $recipientEmail,
            $recipientPhone,
            $channel,
            $templateName,
            $idempotencyKey,
            $payloadJson,
            $maxRetries
        ]);

        return (int)$pdo->lastInsertId();
    }

    /**
     * Process pending and retry-eligible jobs in the queue.
     */
    public static function processQueue(int $limit = 25): array
    {
        $pdo = Database::getConnection();
        $processed = 0;
        $succeeded = 0;
        $failed = 0;
        $results = [];

        // Select jobs that are Pending or Retrying and past next_retry_at
        $sql = "SELECT * FROM notification_queue 
            WHERE status IN ('Pending', 'Retrying') 
            AND (next_retry_at IS NULL OR next_retry_at <= CURRENT_TIMESTAMP)
            ORDER BY id ASC LIMIT " . (int)$limit;

        $stmt = $pdo->query($sql);
        $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        foreach ($jobs as $job) {
            $processed++;
            $jobId = (int)$job['id'];
            $channel = $job['channel'];
            $payload = json_decode($job['payload_json'] ?? '{}', true) ?: [];

            // Mark job as Processing
            $pdo->prepare("UPDATE notification_queue SET status = 'Processing' WHERE id = ?")->execute([$jobId]);

            $dispatchResult = self::executeJobDispatch($job, $payload);

            if ($dispatchResult['success']) {
                $succeeded++;
                $pdo->prepare("UPDATE notification_queue 
                    SET status = 'Sent', processed_at = CURRENT_TIMESTAMP, error_message = NULL 
                    WHERE id = ?")->execute([$jobId]);

                // Update or write to notification_logs
                self::recordLog($job, $dispatchResult, 'Sent');
            } else {
                $failed++;
                $newRetryCount = (int)$job['retry_count'] + 1;
                $maxRetries = (int)$job['max_retries'];
                $errMsg = $dispatchResult['error'] ?? 'Unknown dispatch failure';

                if ($newRetryCount < $maxRetries) {
                    // Exponential backoff minutes: 2m, 5m, 15m
                    $backoffMinutes = match ($newRetryCount) {
                        1 => 2,
                        2 => 5,
                        default => 15,
                    };
                    $nextRetry = date('Y-m-d H:i:s', time() + ($backoffMinutes * 60));

                    $pdo->prepare("UPDATE notification_queue 
                        SET status = 'Retrying', retry_count = ?, next_retry_at = ?, error_message = ? 
                        WHERE id = ?")->execute([$newRetryCount, $nextRetry, $errMsg, $jobId]);

                    self::recordLog($job, $dispatchResult, 'Retrying', $newRetryCount, $nextRetry);
                } else {
                    // Max retries exceeded -> mark Failed permanently
                    $pdo->prepare("UPDATE notification_queue 
                        SET status = 'Failed', retry_count = ?, error_message = ?, processed_at = CURRENT_TIMESTAMP 
                        WHERE id = ?")->execute([$newRetryCount, $errMsg, $jobId]);

                    self::recordLog($job, $dispatchResult, 'Failed', $newRetryCount);
                }
            }

            $results[] = [
                'job_id' => $jobId,
                'channel' => $channel,
                'status' => $dispatchResult['success'] ? 'Sent' : 'Failed',
                'error' => $dispatchResult['error'] ?? null,
            ];
        }

        return [
            'total_processed' => $processed,
            'succeeded' => $succeeded,
            'failed' => $failed,
            'jobs' => $results,
        ];
    }

    /**
     * Dispatch a single queue job payload to the respective provider service.
     */
    private static function executeJobDispatch(array $job, array $payload): array
    {
        $channel = strtolower($job['channel']);

        if ($channel === 'email') {
            return EmailService::send([
                'to' => $job['recipient_email'] ?? $payload['to'] ?? '',
                'name' => $job['recipient_name'] ?? $payload['name'] ?? '',
                'subject' => $payload['subject'] ?? 'Notification from ' . App::COMPANY_NAME,
                'bodyHtml' => $payload['bodyHtml'] ?? $payload['content'] ?? '',
                'data' => $payload['data'] ?? [],
            ]);
        } elseif ($channel === 'whatsapp') {
            return WhatsAppService::send([
                'phoneNumber' => $job['recipient_phone'] ?? $payload['phoneNumber'] ?? '',
                'templateName' => $job['template_name'] ?? $payload['templateName'] ?? '',
                'languageCode' => $payload['languageCode'] ?? 'en_US',
                'variables' => $payload['variables'] ?? [],
                'messageText' => $payload['messageText'] ?? '',
                'data' => $payload['data'] ?? [],
            ]);
        } elseif ($channel === 'in-app' || $channel === 'in_app') {
            return NotificationService::createInAppNotification($payload);
        }

        return [
            'success' => false,
            'message_id' => null,
            'provider' => $channel,
            'error' => "Unsupported delivery channel: {$channel}",
            'simulated' => false,
        ];
    }

    /**
     * Write or update delivery record in notification_logs.
     */
    private static function recordLog(
        array $job,
        array $dispatchResult,
        string $status,
        int $retryCount = 0,
        ?string $nextRetryAt = null
    ): void {
        $pdo = Database::getConnection();

        $stmt = $pdo->prepare("INSERT INTO notification_logs 
            (event_type, recipient_type, recipient_id, recipient_name, recipient_email, recipient_phone, channel, template_name, idempotency_key, status, provider_message_id, request_payload, response_payload, error_message, retry_count, next_retry_at, sent_at, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)");

        $sentAt = $status === 'Sent' ? date('Y-m-d H:i:s') : null;
        $providerMsgId = $dispatchResult['message_id'] ?? null;
        $errorMsg = $dispatchResult['error'] ?? null;
        $responsePayload = !empty($dispatchResult['response']) ? json_encode($dispatchResult['response']) : null;

        $stmt->execute([
            $job['event_type'] ?? 'system.general',
            $job['recipient_type'] ?? 'Applicant',
            $job['recipient_id'] ?? null,
            $job['recipient_name'] ?? null,
            $job['recipient_email'] ?? null,
            $job['recipient_phone'] ?? null,
            $job['channel'],
            $job['template_name'] ?? null,
            $job['idempotency_key'] ?? null,
            $status,
            $providerMsgId,
            $job['payload_json'] ?? null,
            $responsePayload,
            $errorMsg,
            $retryCount,
            $nextRetryAt,
            $sentAt
        ]);
    }

    /**
     * Manually retry a failed log entry from the admin panel.
     */
    public static function retryFailedLog(int $logId): array
    {
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM notification_logs WHERE id = ?");
        $stmt->execute([$logId]);
        $log = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$log) {
            return ['success' => false, 'message' => 'Notification log record not found.'];
        }

        $payload = !empty($log['request_payload']) ? json_decode($log['request_payload'], true) : [];
        $dispatchResult = self::executeJobDispatch($log, $payload);

        if ($dispatchResult['success']) {
            $pdo->prepare("UPDATE notification_logs 
                SET status = 'Sent', provider_message_id = ?, error_message = NULL, sent_at = CURRENT_TIMESTAMP, retry_count = retry_count + 1 
                WHERE id = ?")->execute([$dispatchResult['message_id'] ?? 'retry-ok', $logId]);

            return [
                'success' => true,
                'message' => "Notification successfully resent via {$log['channel']}.",
                'message_id' => $dispatchResult['message_id'] ?? null,
            ];
        } else {
            $pdo->prepare("UPDATE notification_logs 
                SET status = 'Failed', error_message = ?, retry_count = retry_count + 1 
                WHERE id = ?")->execute([$dispatchResult['error'] ?? 'Retry attempt failed', $logId]);

            return [
                'success' => false,
                'message' => "Retry failed: " . ($dispatchResult['error'] ?? 'Unknown error'),
            ];
        }
    }
}
