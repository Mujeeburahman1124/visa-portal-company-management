<?php
declare(strict_types=1);

/**
 * VISA TRACK — Schema Validation Script
 * Run: php scripts/check-schema.php
 * Returns exit code 0 on success, 1 on failure.
 */

require_once dirname(__DIR__) . '/app/autoload.php';

use App\Config\Database;
use App\Database\DatabaseBootstrapper;

DatabaseBootstrapper::init(true);

$pdo    = Database::getConnection();
$driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
$passed = 0;
$failed = 0;
$missing = [];

$requirements = [
    'roles'                        => ['id','name','slug'],
    'permissions'                  => ['id','name','slug','module'],
    'role_permissions'             => ['role_id','permission_id'],
    'branches'                     => ['id','name','code','is_active'],
    'users'                        => ['id','role_id','branch_id','name','email','password_hash','is_active'],
    'customers'                    => ['id','customer_code','first_name','last_name','full_name','email','mobile','city','is_active'],
    'customer_passports'           => ['id','customer_id','passport_number','expiry_date','is_primary'],
    'customer_national_ids'        => ['id','customer_id','id_number','expiry_date'],
    'countries'                    => ['id','name','iso_code','flag_emoji','is_active'],
    'visa_categories'              => ['id','name','slug','is_active'],
    'visa_services'                => ['id','country_id','category_id','name','slug','entry_type','selling_price','is_active'],
    'visa_requirements'            => ['id','service_id','document_type_id','is_mandatory','is_critical','is_active'],
    'document_types'               => ['id','name','code','category','is_active'],
    'suppliers'                    => ['id','supplier_code','company_name','email','is_active'],
    'agents'                       => ['id','agent_code','company_name','email','password_hash','is_active'],
    'applications'                 => ['id','application_number','customer_id','visa_service_id','status','current_stage','selling_price','paid_amount'],
    'application_status_history'   => ['id','application_id','from_status','to_status'],
    'documents'                    => ['id','application_id','customer_id','document_type_id','file_path','status'],
    'document_versions'            => ['id','document_id','file_path','file_name','mime_type'],
    'document_requests'            => ['id','application_id','customer_id','document_type_id','status'],
    'payments'                     => ['id','payment_number','application_id','customer_id','amount','status'],
    'refunds'                      => ['id','refund_number','application_id','customer_id','amount'],
    'supplier_payments'            => ['id','payment_reference','supplier_id','application_id','paid_amount'],
    'tasks'                        => ['id','application_id','task_title','status','assigned_to','completion_notes','proof_of_work','proof_attachment'],
    'notifications'                => ['id','title','message','is_read'],
    'activity_logs'                => ['id','action','module'],
    'system_settings'              => ['id','setting_key','setting_value'],
    'email_templates'              => ['id','template_key','title','subject','body_html'],
    'password_resets'              => ['id','email','token','expires_at'],
    'portal_activation_tokens'     => ['id','customer_id','token','expires_at'],
    'visa_approvals'               => ['id','application_id','visa_number','updated_at'],
    'payroll_records'              => ['id','payroll_code','user_id','payroll_month','net_salary'],
    'staff_attendance'             => ['id','user_id','attendance_date','status'],
    'jobs'                         => ['id','job_title','status'],
    'agent_applications'           => ['id','agent_id','application_id'],
    'customer_wallets'             => ['id','customer_id','currency','current_balance'],
    'wallet_transactions'          => ['id','transaction_id','customer_id','wallet_id','invoice_id','payment_method','reference'],
    'supplier_wallets'             => ['id','supplier_id','currency','current_balance'],
    'supplier_wallet_transactions' => ['id','transaction_id','supplier_id','wallet_id','original_amount','exchange_rate','converted_amount','payment_method','reference'],
    'agent_wallets'                => ['id','agent_id','currency','current_balance'],
    'agent_wallet_transactions'    => ['id','transaction_id','agent_id','wallet_id','original_amount','exchange_rate','converted_amount','payment_method','reference'],
    'schema_migrations'            => ['id','version','applied_at'],
];

function tableExists(PDO $pdo, string $driver, string $table): bool {
    try {
        if ($driver === 'sqlite') {
            return (bool)$pdo->query("SELECT 1 FROM sqlite_master WHERE type='table' AND name=" . $pdo->quote($table))->fetchColumn();
        }
        return (bool)$pdo->query("SHOW TABLES LIKE " . $pdo->quote($table))->fetchColumn();
    } catch (\Throwable $e) { return false; }
}

function getColumns(PDO $pdo, string $driver, string $table): array {
    try {
        if ($driver === 'sqlite') {
            return array_column($pdo->query("PRAGMA table_info($table)")->fetchAll(PDO::FETCH_ASSOC), 'name');
        }
        return array_column($pdo->query("SHOW COLUMNS FROM `$table`")->fetchAll(PDO::FETCH_ASSOC), 'Field');
    } catch (\Throwable $e) { return []; }
}

echo "=== VISA TRACK Schema Validation [$driver] ===\n" . str_repeat('-', 60) . "\n";

foreach ($requirements as $table => $requiredCols) {
    if (!tableExists($pdo, $driver, $table)) {
        echo "FAIL  $table — TABLE MISSING\n";
        $failed++; $missing[] = "TABLE MISSING: $table"; continue;
    }
    $missingCols = array_diff($requiredCols, getColumns($pdo, $driver, $table));
    if (empty($missingCols)) { echo "PASS  $table\n"; $passed++; }
    else { echo "FAIL  $table — MISSING COLS: " . implode(', ', $missingCols) . "\n"; $failed++; $missing[] = "COLUMN MISSING in $table: " . implode(', ', $missingCols); }
}

echo str_repeat('-', 60) . "\nPASSED: $passed  FAILED: $failed  TOTAL: " . ($passed + $failed) . "\n";
if (!empty($missing)) {
    echo "\nFAILURES:\n"; foreach ($missing as $m) echo "  - $m\n";
    echo "\nFix: set DB_AUTO_MIGRATE=true and reload once, then set back to false.\n"; exit(1);
}
echo "\nAll schema checks PASSED.\n";
try { echo "Schema version: " . (int)$pdo->query("SELECT COALESCE(MAX(version),0) FROM schema_migrations")->fetchColumn() . "\n"; } catch(\Throwable $e) {}
exit(0);
