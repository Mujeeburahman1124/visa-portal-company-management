<?php
$pageTitle = "Public 2-Factor Visa Tracking — MS Travel Hub";
$metaDescription = "Track your visa application progress in real-time. Secure 2-factor authentication lookup.";
$currentRoute = '/track';

ob_start();
?>

<div class="py-4 bg-dark text-white text-center">
    <div class="container">
        <h1 class="fw-bold fs-2"><i class="fa-solid fa-shield-halved text-info me-2"></i> Public 2-Factor Visa Tracking Center</h1>
        <p class="text-info mb-0">Enter your Application Reference Number AND Passport Number for instant status lookup.</p>
    </div>
</div>

<div class="py-5">
    <div class="container" style="max-width: 800px;">

        <!-- 2-Factor Search Box -->
        <div class="card card-custom p-4 p-md-5 mb-4">
            <h4 class="fw-bold text-dark mb-3"><i class="fa-solid fa-lock text-primary me-2"></i> Secure Application Lookup</h4>
            <p class="text-secondary small mb-4">For privacy protection, tracking requires dual verification of your Application Reference and Passport Number.</p>

            <form action="/track" method="POST">
                <?= csrf_field() ?>

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-secondary">Application Reference Number <span class="text-danger">*</span></label>
                        <input type="text" name="application_number" class="form-control" placeholder="e.g. MSV-2026-8841" value="<?= e($_POST['application_number'] ?? $_GET['app_num'] ?? '') ?>" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label small fw-semibold text-secondary">Passport Number <span class="text-danger">*</span></label>
                        <input type="text" name="passport_number" class="form-control" placeholder="e.g. A9821034" value="<?= e($_POST['passport_number'] ?? $_GET['pass_num'] ?? '') ?>" required>
                    </div>
                </div>

                <button type="submit" class="btn btn-brand btn-lg w-100 mt-4 fw-semibold">
                    <i class="fa-solid fa-magnifying-glass me-2"></i> Track Application Progress
                </button>
            </form>
        </div>

        <!-- Tracking Search Results -->
        <?php if ($searched && $trackingResult): ?>
            <div class="card card-custom p-4 p-md-5 shadow">
                <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 pb-3 border-bottom">
                    <div>
                        <span class="badge bg-secondary-subtle text-secondary fw-bold px-3 py-1.5 mb-1">
                            Ref: <?= e($trackingResult['application_number']) ?>
                        </span>
                        <h4 class="fw-bold text-dark mb-0"><?= e($trackingResult['flag_emoji'] ?? '🌐') ?> <?= e($trackingResult['country_name']) ?> — <?= e($trackingResult['service_name']) ?></h4>
                    </div>

                    <div class="text-end mt-2 mt-md-0">
                        <span class="badge bg-success fs-6 px-3 py-2">
                            <?= e($trackingResult['public_stage']) ?>
                        </span>
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="p-3 bg-light rounded-3">
                            <small class="text-muted d-block">Application Date</small>
                            <span class="fw-semibold text-dark"><?= format_date($trackingResult['application_date']) ?></span>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="p-3 bg-light rounded-3">
                            <small class="text-muted d-block">Entry &amp; Duration</small>
                            <span class="fw-semibold text-dark"><?= e($trackingResult['entry_type']) ?> &bull; <?= e($trackingResult['duration']) ?></span>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="p-3 bg-light rounded-3">
                            <small class="text-muted d-block">Expected Target</small>
                            <span class="fw-semibold text-info"><?= format_date($trackingResult['expected_completion_date']) ?></span>
                        </div>
                    </div>
                </div>

                <!-- Visual Progress Stepper -->
                <h5 class="fw-bold text-dark mb-3">Application Milestones</h5>
                <div class="p-4 bg-light rounded-4">
                    <?php 
                        $stages = [
                            'Application Received',
                            'Documents Verification & Compliance Review',
                            'Prepared for Embassy Submission',
                            'Under Embassy / Immigration Processing',
                            'Visa Approved & Issued'
                        ];
                        $currentPublicStage = $trackingResult['public_stage'];
                    ?>
                    <div class="d-flex flex-column gap-3">
                        <?php foreach ($stages as $idx => $stg): ?>
                            <?php 
                                $isDone = false;
                                $isCurrent = ($stg === $currentPublicStage);
                                if ($currentPublicStage === 'Visa Approved & Issued') {
                                    $isDone = true;
                                } elseif ($currentPublicStage === 'Under Embassy / Immigration Processing' && $idx <= 3) {
                                    $isDone = true;
                                } elseif ($currentPublicStage === 'Documents Verification & Compliance Review' && $idx <= 1) {
                                    $isDone = true;
                                } elseif ($idx === 0) {
                                    $isDone = true;
                                }
                            ?>
                            <div class="d-flex align-items-center gap-3">
                                <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold" 
                                     style="width:36px; height:36px; background-color: <?= $isDone ? '#10b981' : ($isCurrent ? '#0284c7' : '#cbd5e1') ?>;">
                                    <?= $idx + 1 ?>
                                </div>
                                <div>
                                    <h6 class="fw-bold mb-0 <?= $isDone || $isCurrent ? 'text-dark' : 'text-muted' ?>"><?= e($stg) ?></h6>
                                    <small class="text-secondary"><?= $isCurrent ? 'Current Stage' : ($isDone ? 'Completed' : 'Pending') ?></small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="mt-4 pt-3 border-top text-center text-muted small">
                    For detailed document submission or passport copies, please sign in to the <a href="/portal/login" class="text-primary fw-semibold">Applicant Customer Portal</a>.
                </div>
            </div>
        <?php endif; ?>

    </div>
</div>

<?php
$content = ob_get_clean();
require dirname(__DIR__) . '/public/layout.php';
