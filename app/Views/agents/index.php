<?php
$pageTitle = 'B2B Travel Agents & Partners — VISA TRACK';
$flash = get_flash();
require_once dirname(__DIR__) . '/layouts/header.php';
require_once dirname(__DIR__) . '/layouts/sidebar.php';
require_once dirname(__DIR__) . '/layouts/topbar.php';

$totalAgents = count($agents);
$activeAgents = count(array_filter($agents, fn($x) => (int)$x['is_active'] === 1));
$totalBalance = array_sum(array_column($agents, 'current_balance'));
$totalApps = array_sum(array_column($agents, 'total_applications'));
$currentView = $_GET['view'] ?? 'table';
?>

<div class="content-body">
  <?php if ($flash): ?>
    <div class="alert alert-<?= e($flash['type'] === 'danger' ? 'danger' : ($flash['type'] === 'success' ? 'success' : 'warning')) ?> alert-dismissible fade show mb-4 shadow-sm" role="alert">
      <div class="d-flex align-items-center gap-2">
        <i class="fa-solid <?= $flash['type'] === 'danger' ? 'fa-circle-exclamation' : ($flash['type'] === 'success' ? 'fa-circle-check' : 'fa-triangle-exclamation') ?>"></i>
        <span><?= e($flash['message']) ?></span>
      </div>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php endif; ?>

  <!-- Page Header -->
  <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
      <h3 class="fw-bold brand-font mb-0" style="color: #0f172a;">B2B TRAVEL AGENTS &amp; PARTNERS</h3>
      <p class="text-muted small mb-0">Manage partner accounts, credit limits, balances, profile settings, and portal access credentials.</p>
    </div>
    <div class="d-flex align-items-center gap-2 flex-wrap">
      <!-- 3 View Options Switcher (Responsive) -->
      <div class="btn-group btn-group-sm bg-white shadow-sm border rounded-pill p-1 view-switcher-pill-group" role="group" aria-label="View Mode">
        <button type="button" class="btn btn-sm rounded-pill px-2.5 px-sm-3 fw-semibold agent-view-btn <?= $currentView === 'table' ? 'btn-primary shadow-sm' : 'btn-light text-muted' ?>" onclick="switchAgentView('table')" id="btnAgentViewTable" title="Table View">
          <i class="fa-solid fa-table-list me-1"></i> <span>Table</span>
        </button>
        <button type="button" class="btn btn-sm rounded-pill px-2.5 px-sm-3 fw-semibold agent-view-btn <?= $currentView === 'grid' ? 'btn-primary shadow-sm' : 'btn-light text-muted' ?>" onclick="switchAgentView('grid')" id="btnAgentViewGrid" title="Grid Cards View">
          <i class="fa-solid fa-grip me-1"></i> <span>Cards</span>
        </button>
        <button type="button" class="btn btn-sm rounded-pill px-2.5 px-sm-3 fw-semibold agent-view-btn <?= $currentView === 'compact' ? 'btn-primary shadow-sm' : 'btn-light text-muted' ?>" onclick="switchAgentView('compact')" id="btnAgentViewCompact" title="Compact List View">
          <i class="fa-solid fa-list-ul me-1"></i> <span>List</span>
        </button>
      </div>

      <button type="button" class="btn btn-outline-success btn-sm px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#recordAgentPayModal">
        <i class="fa-solid fa-hand-holding-dollar me-1"></i> Record Settlement
      </button>
      <button type="button" class="btn btn-primary btn-sm px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#newAgentModal">
        <i class="fa-solid fa-plus-circle me-1"></i> Add New Agent
      </button>
    </div>
  </div>

  <!-- Summary Statistics Cards -->
  <div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
      <div class="card card-enterprise p-3 h-100 border-start border-4 border-primary">
        <div class="text-muted small fw-semibold text-uppercase" style="font-size: 0.72rem;">Total Agents</div>
        <div class="fs-4 fw-bold text-dark mt-1"><?= $totalAgents ?></div>
        <div class="small text-muted mt-1"><i class="fa-solid fa-handshake me-1 text-primary"></i> Registered Partners</div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card card-enterprise p-3 h-100 border-start border-4 border-success">
        <div class="text-muted small fw-semibold text-uppercase" style="font-size: 0.72rem;">Active Accounts</div>
        <div class="fs-4 fw-bold text-success mt-1"><?= $activeAgents ?></div>
        <div class="small text-muted mt-1"><i class="fa-solid fa-circle-check me-1 text-success"></i> Operational Partners</div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card card-enterprise p-3 h-100 border-start border-4 border-danger">
        <div class="text-muted small fw-semibold text-uppercase" style="font-size: 0.72rem;">Total Outstanding Balance</div>
        <div class="fs-4 fw-bold text-danger mt-1"><?= format_currency($totalBalance) ?></div>
        <div class="small text-muted mt-1"><i class="fa-solid fa-file-invoice-dollar me-1 text-danger"></i> Net Agent Receivables</div>
      </div>
    </div>
    <div class="col-6 col-md-3">
      <div class="card card-enterprise p-3 h-100 border-start border-4 border-info">
        <div class="text-muted small fw-semibold text-uppercase" style="font-size: 0.72rem;">Linked Applications</div>
        <div class="fs-4 fw-bold text-info mt-1"><?= $totalApps ?></div>
        <div class="small text-muted mt-1"><i class="fa-solid fa-passport me-1 text-info"></i> Visa Files Processed</div>
      </div>
    </div>
  </div>

  <!-- Filter & Search Toolbar -->
  <div class="card card-enterprise mb-4">
    <div class="card-body p-3">
      <form method="GET" action="/agents" class="row g-2 align-items-center">
        <div class="col-md-9 col-lg-10">
          <div class="input-group">
            <span class="input-group-text bg-white border-end-0 text-muted"><i class="fa-solid fa-magnifying-glass"></i></span>
            <input type="text" name="search" class="form-control border-start-0" placeholder="Search by company name, agent code, email, contact person..." value="<?= e($_GET['search'] ?? '') ?>">
          </div>
        </div>
        <div class="col-md-3 col-lg-2 d-flex gap-2">
          <button type="submit" class="btn btn-primary btn-sm flex-grow-1"><i class="fa-solid fa-filter me-1"></i> Filter</button>
          <a href="/agents" class="btn btn-outline-secondary btn-sm" title="Clear Filters"><i class="fa-solid fa-rotate-left"></i></a>
        </div>
      </form>
    </div>
  </div>

  <!-- ================================================================= -->
  <!-- VIEW OPTION 1: AGENTS DATA TABLE VIEW -->
  <!-- ================================================================= -->
  <div id="agentViewTable" class="agent-view-container <?= $currentView === 'table' ? '' : 'd-none' ?>">
    <div class="card card-enterprise card-table-enterprise shadow-sm">
      <!-- Mobile Scroll Hint -->
      <div class="d-md-none px-3 py-2 bg-light border-bottom d-flex align-items-center justify-content-between text-muted" style="font-size: 0.74rem;">
        <span><i class="fa-solid fa-arrows-left-right text-primary me-1"></i> Swipe table sideways &bull; Agent code pinned</span>
        <span class="badge bg-primary-subtle text-primary border" style="font-size: 0.68rem;">Table View</span>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive mb-0">
          <table class="table table-hover table-mobile-sticky-first align-middle mb-0" id="agentsTable">
            <thead class="table-light">
              <tr>
                <th style="min-width: 110px;">Agent Code</th>
                <th style="min-width: 200px;">Agency / Company</th>
                <th style="min-width: 170px;">Contact Person</th>
                <th style="min-width: 120px;">Location</th>
                <th style="min-width: 110px;">Credit Limit</th>
                <th style="min-width: 110px;">Current Balance</th>
                <th style="min-width: 90px;">Comm. Rate</th>
                <th style="min-width: 80px;">Visas</th>
                <th style="min-width: 90px;">Status</th>
                <th class="text-end" style="min-width: 140px;">Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($agents)): ?>
                <tr>
                  <td colspan="10" class="text-center py-5 text-muted">
                    <i class="fa-solid fa-handshake-slash fa-2x mb-2 d-block opacity-25"></i>
                    No B2B agent records found matching your query.
                  </td>
                </tr>
              <?php else: ?>
                <?php foreach ($agents as $a): 
                  $active = (int)$a['is_active'] === 1;
                  $balance = (float)$a['current_balance'];
                  $creditLimit = (float)$a['credit_limit'];
                  
                  // Pack agent JSON data safely for modal population
                  $agentJson = htmlspecialchars(json_encode($a), ENT_QUOTES, 'UTF-8');
                ?>
                  <tr>
                    <td>
                      <span class="badge bg-dark font-monospace px-2.5 py-1.5"><?= e($a['agent_code']) ?></span>
                    </td>
                    <td>
                      <div class="fw-bold text-dark"><?= e($a['company_name']) ?></div>
                      <div class="text-muted small" style="font-size: 0.72rem;">
                        <i class="fa-solid fa-envelope me-1"></i><?= e($a['email'] ?: 'No email') ?>
                      </div>
                    </td>
                    <td>
                      <div class="fw-semibold text-dark"><?= e($a['contact_person'] ?: '—') ?></div>
                      <div class="text-muted small" style="font-size: 0.72rem;">
                        <i class="fa-solid fa-phone me-1"></i><?= e($a['mobile'] ?: '—') ?>
                        <?php if (!empty($a['whatsapp'])): ?>
                          &bull; <a href="https://wa.me/<?= preg_replace('/\D/', '', $a['whatsapp']) ?>" target="_blank" class="text-success text-decoration-none"><i class="fa-brands fa-whatsapp"></i></a>
                        <?php endif; ?>
                      </div>
                    </td>
                    <td class="small">
                      <span class="text-truncate d-inline-block" style="max-width: 140px;" title="<?= e($a['city'] ?: '') ?><?= (!empty($a['city']) && !empty($a['country'])) ? ', ' : '' ?><?= e($a['country'] ?: '—') ?>">
                        <?= e($a['city'] ?: '') ?><?= (!empty($a['city']) && !empty($a['country'])) ? ', ' : '' ?><?= e($a['country'] ?: '—') ?>
                      </span>
                    </td>
                    <td class="text-nowrap"><?= format_currency($creditLimit) ?></td>
                    <td class="text-nowrap">
                      <span class="fw-bold <?= $balance > 0 ? 'text-danger' : 'text-success' ?>">
                        <?= format_currency($balance) ?>
                      </span>
                    </td>
                    <td class="text-nowrap"><span class="badge bg-light text-dark border"><?= (float)$a['commission_rate'] ?>%</span></td>
                    <td class="text-nowrap"><span class="badge bg-primary rounded-pill px-2.5 py-1"><?= (int)$a['total_applications'] ?></span></td>
                    <td class="text-nowrap">
                      <span class="badge <?= $active ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' ?> fw-bold px-2 py-1">
                        <?= $active ? 'Active' : 'Suspended' ?>
                      </span>
                    </td>
                    <td class="text-end text-nowrap">
                      <div class="dropdown d-inline-block">
                        <button class="btn btn-outline-secondary btn-sm dropdown-toggle shadow-sm px-2.5 py-1" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                          <i class="fa-solid fa-gear me-1"></i> Options
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0 small" style="z-index: 1060;">
                          <!-- 1. Edit Profile -->
                          <li>
                            <button type="button" class="dropdown-item py-2 text-primary" onclick="openEditAgentModal(<?= $agentJson ?>)">
                              <i class="fa-solid fa-pen-to-square me-2 text-primary"></i> Edit Profile &amp; Terms
                            </button>
                          </li>
                          <!-- 2. Top Up / Debit Balance -->
                          <li>
                            <button type="button" class="dropdown-item py-2 text-success fw-semibold" onclick="openAgentAdjustModal(<?= (int)$a['id'] ?>, '<?= e(addslashes($a['company_name'])) ?>', '<?= e(addslashes($a['agent_code'])) ?>', <?= $balance ?>)">
                              <i class="fa-solid fa-money-bill-transfer me-2 text-success"></i> Top Up / Debit Balance
                            </button>
                          </li>
                          <!-- 3. Reset Password -->
                          <li>
                            <button type="button" class="dropdown-item py-2 text-warning fw-semibold" onclick="openResetPasswordModal(<?= (int)$a['id'] ?>, '<?= e(addslashes($a['company_name'])) ?>', '<?= e(addslashes($a['agent_code'])) ?>', '<?= e(addslashes($a['email'] ?? '')) ?>')">
                              <i class="fa-solid fa-key me-2 text-warning"></i> Reset Portal Password
                            </button>
                          </li>
                          <!-- 4. Send Activation Email -->
                          <li>
                            <form action="/agents/send-activation" method="POST" class="d-inline">
                              <?= csrf_field() ?>
                              <input type="hidden" name="agent_id" value="<?= $a['id'] ?>">
                              <button type="submit" class="dropdown-item py-2 text-info">
                                <i class="fa-solid fa-paper-plane me-2 text-info"></i> Send Activation Link
                              </button>
                            </form>
                          </li>
                          <li><hr class="dropdown-divider my-1"></li>
                          <!-- 5. Toggle Status -->
                          <li>
                            <form action="/agents/toggle-status" method="POST" class="d-inline">
                              <?= csrf_field() ?>
                              <input type="hidden" name="agent_id" value="<?= $a['id'] ?>">
                              <button type="submit" class="dropdown-item py-2 text-secondary">
                                <i class="fa-solid <?= $active ? 'fa-ban text-warning' : 'fa-check text-success' ?> me-2"></i>
                                <?= $active ? 'Suspend Account' : 'Activate Account' ?>
                              </button>
                            </form>
                          </li>
                          <!-- 6. Delete Partner -->
                          <li>
                            <form action="/agents/delete" method="POST" class="d-inline" onsubmit="return confirm('Permanently delete agent \'<?= e(addslashes($a['company_name'])) ?>\' (<?= e($a['agent_code']) ?>)? This cannot be undone.');">
                              <?= csrf_field() ?>
                              <input type="hidden" name="agent_id" value="<?= $a['id'] ?>">
                              <button type="submit" class="dropdown-item py-2 text-danger">
                                <i class="fa-solid fa-trash-can me-2 text-danger"></i> Delete Agent
                              </button>
                            </form>
                          </li>
                        </ul>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- ================================================================= -->
  <!-- VIEW OPTION 2: AGENTS GRID CARDS VIEW -->
  <!-- ================================================================= -->
  <div id="agentViewGrid" class="agent-view-container <?= $currentView === 'grid' ? '' : 'd-none' ?>">
    <div class="row g-3">
      <?php if (empty($agents)): ?>
        <div class="col-12 text-center py-5 text-muted">
          <i class="fa-solid fa-handshake-slash fa-2x mb-2 d-block opacity-25"></i>
          No B2B agent records found matching your query.
        </div>
      <?php else: ?>
        <?php foreach ($agents as $a): 
          $active = (int)$a['is_active'] === 1;
          $balance = (float)$a['current_balance'];
          $creditLimit = (float)$a['credit_limit'];
          $agentJson = htmlspecialchars(json_encode($a), ENT_QUOTES, 'UTF-8');
        ?>
          <div class="col-12 col-md-6 col-lg-4">
            <div class="card card-enterprise h-100 shadow-sm border">
              <div class="card-body p-3.5 d-flex flex-column justify-content-between">
                <div>
                  <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="badge bg-dark font-monospace px-2.5 py-1">
                      <?= e($a['agent_code']) ?>
                    </span>
                    <span class="badge <?= $active ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' ?> fw-bold px-2 py-0.5" style="font-size: 0.72rem;">
                      <i class="fa-solid <?= $active ? 'fa-circle-check' : 'fa-ban' ?> me-1"></i><?= $active ? 'Active' : 'Suspended' ?>
                    </span>
                  </div>

                  <h6 class="fw-bold text-dark mb-1 text-truncate">
                    <?= e($a['company_name']) ?>
                  </h6>
                  <div class="text-muted small mb-3 text-truncate" style="font-size: 0.76rem;">
                    <i class="fa-solid fa-envelope me-1"></i><?= e($a['email'] ?: 'No email') ?>
                  </div>

                  <div class="p-2.5 bg-light rounded-3 border mb-3">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                      <span class="text-muted small">Contact Person:</span>
                      <strong class="text-dark small text-truncate" style="max-width: 160px;"><?= e($a['contact_person'] ?: '—') ?></strong>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-1">
                      <span class="text-muted small">Phone / Mobile:</span>
                      <span class="small fw-semibold text-secondary text-truncate" style="max-width: 160px;"><?= e($a['mobile'] ?: '—') ?></span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center">
                      <span class="text-muted small">Location:</span>
                      <span class="small text-dark text-truncate" style="max-width: 160px;"><?= e($a['city'] ?: '') ?><?= (!empty($a['city']) && !empty($a['country'])) ? ', ' : '' ?><?= e($a['country'] ?: '—') ?></span>
                    </div>
                  </div>

                  <div class="row g-2 mb-3 text-center">
                    <div class="col-4">
                      <div class="p-2 border rounded bg-white">
                        <div class="text-muted text-uppercase" style="font-size: 0.65rem;">Limit</div>
                        <div class="fw-bold small text-dark"><?= format_currency($creditLimit) ?></div>
                      </div>
                    </div>
                    <div class="col-4">
                      <div class="p-2 border rounded bg-white">
                        <div class="text-muted text-uppercase" style="font-size: 0.65rem;">Balance</div>
                        <div class="fw-bold small <?= $balance > 0 ? 'text-danger' : 'text-success' ?>"><?= format_currency($balance) ?></div>
                      </div>
                    </div>
                    <div class="col-4">
                      <div class="p-2 border rounded bg-white">
                        <div class="text-muted text-uppercase" style="font-size: 0.65rem;">Comm.</div>
                        <div class="fw-bold small text-dark"><?= (float)$a['commission_rate'] ?>%</div>
                      </div>
                    </div>
                  </div>

                  <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="badge bg-primary rounded-pill px-2.5 py-1 small">
                      <i class="fa-solid fa-passport me-1"></i><?= (int)$a['total_applications'] ?> Visas
                    </span>
                    <span class="badge bg-light text-secondary border px-2 py-1 small">
                      <?= e($a['payment_terms'] ?? 'Net 30') ?>
                    </span>
                  </div>
                </div>

                <div class="pt-2 border-top d-flex align-items-center justify-content-between gap-1 flex-wrap">
                  <div class="d-flex align-items-center gap-1">
                    <button type="button" class="btn btn-sm btn-outline-success fw-semibold px-2.5" onclick="openAgentAdjustModal(<?= (int)$a['id'] ?>, '<?= e(addslashes($a['company_name'])) ?>', '<?= e(addslashes($a['agent_code'])) ?>', <?= $balance ?>)">
                      <i class="fa-solid fa-money-bill-transfer me-1"></i> Balance
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-primary py-1 px-2" onclick="openEditAgentModal(<?= $agentJson ?>)" title="Edit Profile">
                      <i class="fa-solid fa-pen-to-square"></i>
                    </button>
                  </div>

                  <div class="d-flex align-items-center gap-1">
                    <form action="/agents/send-activation" method="POST" class="d-inline" onsubmit="return confirm('Send portal activation link to <?= e($a['company_name']) ?>?');">
                      <?= csrf_field() ?>
                      <input type="hidden" name="agent_id" value="<?= $a['id'] ?>">
                      <button type="submit" class="btn btn-sm btn-outline-info py-1 px-2" title="Send Password Setup & Activation Link">
                        <i class="fa-solid fa-paper-plane"></i>
                      </button>
                    </form>
                    <button type="button" class="btn btn-sm btn-outline-warning py-1 px-2" onclick="openResetPasswordModal(<?= (int)$a['id'] ?>, '<?= e(addslashes($a['company_name'])) ?>', '<?= e(addslashes($a['agent_code'])) ?>', '<?= e(addslashes($a['email'] ?? '')) ?>')" title="Reset Password">
                      <i class="fa-solid fa-key"></i>
                    </button>
                    <form action="/agents/delete" method="POST" class="d-inline" onsubmit="return confirm('Permanently delete agent \'<?= e(addslashes($a['company_name'])) ?>\'?');">
                      <?= csrf_field() ?>
                      <input type="hidden" name="agent_id" value="<?= $a['id'] ?>">
                      <button type="submit" class="btn btn-sm btn-outline-danger py-1 px-2" title="Delete">
                        <i class="fa-solid fa-trash-can"></i>
                      </button>
                    </form>
                  </div>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

  <!-- ================================================================= -->
  <!-- VIEW OPTION 3: AGENTS COMPACT LIST VIEW -->
  <!-- ================================================================= -->
  <div id="agentViewCompact" class="agent-view-container <?= $currentView === 'compact' ? '' : 'd-none' ?>">
    <div class="card card-enterprise shadow-sm border">
      <ul class="list-group list-group-flush mb-0">
        <?php if (empty($agents)): ?>
          <li class="list-group-item text-center py-5 text-muted">
            <i class="fa-solid fa-handshake-slash fa-2x mb-2 d-block opacity-25"></i>
            No B2B agent records found matching your query.
          </li>
        <?php else: ?>
          <?php foreach ($agents as $a): 
            $active = (int)$a['is_active'] === 1;
            $balance = (float)$a['current_balance'];
            $agentJson = htmlspecialchars(json_encode($a), ENT_QUOTES, 'UTF-8');
          ?>
            <li class="list-group-item px-3 py-2.5 hover-bg-light transition">
              <div class="row align-items-center g-2">
                <div class="col-12 col-md-4">
                  <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-dark font-monospace px-2 py-1"><?= e($a['agent_code']) ?></span>
                    <div class="min-w-0">
                      <div class="fw-bold text-dark text-truncate small"><?= e($a['company_name']) ?></div>
                      <div class="text-muted text-truncate" style="font-size: 0.72rem;"><?= e($a['contact_person'] ?: '—') ?> &bull; <?= e($a['city'] ?: 'Global') ?></div>
                    </div>
                  </div>
                </div>

                <div class="col-6 col-md-3">
                  <div class="small text-muted" style="font-size: 0.72rem;">Outstanding Balance:</div>
                  <span class="fw-bold small <?= $balance > 0 ? 'text-danger' : 'text-success' ?>">
                    <?= format_currency($balance) ?>
                  </span>
                </div>

                <div class="col-6 col-md-2 text-center text-md-start">
                  <span class="badge bg-primary rounded-pill px-2.5 py-1 small">
                    <?= (int)$a['total_applications'] ?> Visas
                  </span>
                  <span class="badge <?= $active ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' ?> fw-bold ms-1" style="font-size: 0.7rem;">
                    <?= $active ? 'Active' : 'Suspended' ?>
                  </span>
                </div>

                <div class="col-12 col-md-3 text-end">
                  <div class="d-inline-flex align-items-center gap-1">
                    <button type="button" class="btn btn-sm btn-outline-success py-1 px-2" onclick="openAgentAdjustModal(<?= (int)$a['id'] ?>, '<?= e(addslashes($a['company_name'])) ?>', '<?= e(addslashes($a['agent_code'])) ?>', <?= $balance ?>)" title="Adjust Balance">
                      <i class="fa-solid fa-money-bill-transfer"></i>
                    </button>
                    <form action="/agents/send-activation" method="POST" class="d-inline" onsubmit="return confirm('Send portal activation link to <?= e($a['company_name']) ?>?');">
                      <?= csrf_field() ?>
                      <input type="hidden" name="agent_id" value="<?= $a['id'] ?>">
                      <button type="submit" class="btn btn-sm btn-outline-info py-1 px-2" title="Send Activation Link">
                        <i class="fa-solid fa-paper-plane"></i>
                      </button>
                    </form>
                    <button type="button" class="btn btn-sm btn-outline-primary py-1 px-2" onclick="openEditAgentModal(<?= $agentJson ?>)" title="Edit">
                      <i class="fa-solid fa-pen-to-square"></i>
                    </button>
                    <form action="/agents/delete" method="POST" class="d-inline" onsubmit="return confirm('Delete agent \'<?= e(addslashes($a['company_name'])) ?>\'?');">
                      <?= csrf_field() ?>
                      <input type="hidden" name="agent_id" value="<?= $a['id'] ?>">
                      <button type="submit" class="btn btn-sm btn-outline-danger py-1 px-2" title="Delete">
                        <i class="fa-solid fa-trash-can"></i>
                      </button>
                    </form>
                  </div>
                </div>
              </div>
            </li>
          <?php endforeach; ?>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</div>
</div>

<!-- ==========================================================================
     SHARED MODALS (PLACED OUTSIDE TABLE FOR VALID HTML AND RELIABLE RENDERING)
     ========================================================================== -->

<!-- 1. Modal: Edit Agent Profile -->
<div class="modal fade" id="editAgentModal" tabindex="-1" aria-labelledby="editAgentModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title fw-bold fs-6" id="editAgentModalLabel"><i class="fa-solid fa-pen-to-square me-2"></i>Edit Agent Partner Profile</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="/agents/update" method="POST" id="editAgentForm">
        <?= csrf_field() ?>
        <input type="hidden" name="agent_id" id="edit_agent_id" value="">

        <div class="modal-body p-4 text-start">
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label small fw-semibold text-secondary">Agent Code</label>
              <input type="text" id="edit_agent_code" class="form-control font-monospace bg-light" readonly>
            </div>
            <div class="col-md-8">
              <label class="form-label small fw-semibold text-secondary">Company / Agency Name <span class="text-danger">*</span></label>
              <input type="text" name="company_name" id="edit_company_name" class="form-control" required>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-semibold text-secondary">Contact Person <span class="text-danger">*</span></label>
              <input type="text" name="contact_person" id="edit_contact_person" class="form-control" required>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-semibold text-secondary">Email (Login / Alerts) <span class="text-danger">*</span></label>
              <input type="email" name="email" id="edit_email" class="form-control" required>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-semibold text-secondary">Mobile Number <span class="text-danger">*</span></label>
              <input type="text" name="mobile" id="edit_mobile" class="form-control" required>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-semibold text-secondary">WhatsApp Number</label>
              <input type="text" name="whatsapp" id="edit_whatsapp" class="form-control">
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-semibold text-secondary">City</label>
              <input type="text" name="city" id="edit_city" class="form-control">
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-semibold text-secondary">Country</label>
              <input type="text" name="country" id="edit_country" class="form-control">
            </div>
            <div class="col-md-4">
              <label class="form-label small fw-semibold text-secondary">Credit Limit ($)</label>
              <input type="number" step="0.01" name="credit_limit" id="edit_credit_limit" class="form-control">
            </div>
            <div class="col-md-4">
              <label class="form-label small fw-semibold text-secondary">Commission Rate (%)</label>
              <input type="number" step="0.1" name="commission_rate" id="edit_commission_rate" class="form-control">
            </div>
            <div class="col-md-4">
              <label class="form-label small fw-semibold text-secondary">Payment Terms</label>
              <select name="payment_terms" id="edit_payment_terms" class="form-select">
                <option value="Prepaid">Prepaid</option>
                <option value="Net 7">Net 7 Days</option>
                <option value="Net 15">Net 15 Days</option>
                <option value="Net 30">Net 30 Days</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-semibold text-secondary">Update Portal Password <small class="text-muted">(Leave blank to keep current)</small></label>
              <input type="password" name="password" class="form-control" placeholder="Optional new password">
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-semibold text-secondary">Account Status</label>
              <select name="is_active" id="edit_is_active" class="form-select">
                <option value="1">Active</option>
                <option value="0">Suspended</option>
              </select>
            </div>
            <div class="col-12">
              <label class="form-label small fw-semibold text-secondary">Bank / Wire Details</label>
              <input type="text" name="bank_details" id="edit_bank_details" class="form-control" placeholder="Bank Name, Account / IBAN, Swift code...">
            </div>
            <div class="col-12">
              <label class="form-label small fw-semibold text-secondary">Office Address</label>
              <textarea name="address" id="edit_address" class="form-control" rows="2"></textarea>
            </div>
            <div class="col-12">
              <label class="form-label small fw-semibold text-secondary">Internal Operational Notes</label>
              <textarea name="notes" id="edit_notes" class="form-control" rows="2"></textarea>
            </div>
          </div>
        </div>

        <div class="modal-footer bg-light border-top">
          <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary fw-semibold"><i class="fa-solid fa-save me-1"></i>Save Profile Changes</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- 2. Modal: Top Up / Debit Balance -->
<div class="modal fade" id="adjustBalanceModal" tabindex="-1" aria-labelledby="adjustBalanceModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-success text-white" id="adjustBalanceHeader">
        <h5 class="modal-title fw-bold fs-6" id="adjustBalanceModalLabel"><i class="fa-solid fa-money-bill-transfer me-2"></i>Top Up / Debit Agent Balance</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="/agents/adjust-balance" method="POST" id="adjustBalanceForm">
        <?= csrf_field() ?>
        <input type="hidden" name="agent_id" id="adj_agent_id" value="">

        <div class="modal-body p-4">
          <div class="p-3 bg-light rounded border mb-3">
            <div class="small text-muted">Selected Agent Partner:</div>
            <div class="fw-bold fs-6 text-dark" id="adj_agent_name">Skyline Travel</div>
            <div class="d-flex justify-content-between align-items-center mt-2 pt-2 border-top">
              <span class="small text-muted">Current Ledger Balance:</span>
              <span class="fw-bold font-monospace fs-6" id="adj_agent_balance">$0.00</span>
            </div>
          </div>

          <!-- Transaction Type: Top Up vs Debit -->
          <div class="mb-3">
            <label class="form-label small fw-semibold text-secondary d-block">Select Balance Action <span class="text-danger">*</span></label>
            <div class="btn-group w-100" role="group">
              <input type="radio" class="btn-check" name="action_type" id="adj_type_top_up" value="top_up" checked autocomplete="off" onchange="onAdjustTypeChange('top_up')">
              <label class="btn btn-outline-success fw-bold py-2" for="adj_type_top_up">
                <i class="fa-solid fa-circle-arrow-up me-1"></i> Top Up / Credit Funds
              </label>

              <input type="radio" class="btn-check" name="action_type" id="adj_type_debit" value="debit" autocomplete="off" onchange="onAdjustTypeChange('debit')">
              <label class="btn btn-outline-danger fw-bold py-2" for="adj_type_debit">
                <i class="fa-solid fa-circle-arrow-down me-1"></i> Debit / Deduct Balance
              </label>
            </div>
            <div class="form-text small mt-1" id="adj_help_text">
              Top Up records payments received from agent, reducing outstanding balance.
            </div>
          </div>

          <div class="row g-2 mb-3">
            <div class="col-7">
              <label class="form-label small fw-semibold text-secondary">Amount ($) <span class="text-danger">*</span></label>
              <div class="input-group">
                <span class="input-group-text">$</span>
                <input type="number" step="0.01" min="0.01" name="amount" class="form-control fw-bold" placeholder="0.00" required>
              </div>
            </div>
            <div class="col-5">
              <label class="form-label small fw-semibold text-secondary">Transaction Date <span class="text-danger">*</span></label>
              <input type="date" name="payment_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label small fw-semibold text-secondary">Payment Method</label>
            <select name="payment_method" class="form-select">
              <option value="Bank Transfer">Bank Transfer</option>
              <option value="Cash">Cash</option>
              <option value="Online Gateway / Stripe">Online Gateway / Card</option>
              <option value="Cheque">Cheque</option>
              <option value="Credit Adjustment">Manual Adjustment</option>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label small fw-semibold text-secondary">Reference / Receipt Number</label>
            <input type="text" name="transaction_reference" class="form-control" placeholder="e.g. Wire confirmation, slip #...">
          </div>

          <div class="mb-0">
            <label class="form-label small fw-semibold text-secondary">Notes / Reason</label>
            <textarea name="notes" class="form-control" rows="2" placeholder="Describe the reason for top up or debit..."></textarea>
          </div>
        </div>

        <div class="modal-footer bg-light border-top">
          <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" id="adj_submit_btn" class="btn btn-success fw-semibold"><i class="fa-solid fa-check me-1"></i>Confirm Top Up</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- 3. Modal: Reset Agent Portal Password -->
<div class="modal fade" id="resetAgentPasswordModal" tabindex="-1" aria-labelledby="resetAgentPasswordModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-warning text-dark">
        <h5 class="modal-title fw-bold fs-6" id="resetAgentPasswordModalLabel"><i class="fa-solid fa-key me-2"></i>Reset Agent Portal Password</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="/agents/reset-password" method="POST" id="resetAgentPasswordForm">
        <?= csrf_field() ?>
        <input type="hidden" name="agent_id" id="reset_agent_id" value="">

        <div class="modal-body p-4 text-start">
          <div class="p-3 bg-light rounded border mb-3">
            <div class="small text-muted">Agent Partner:</div>
            <div class="fw-bold fs-6 text-dark" id="reset_agent_name">Company Name</div>
            <div class="small text-primary mt-1"><i class="fa-solid fa-envelope me-1"></i><span id="reset_agent_email">email@domain.com</span></div>
          </div>

          <div class="mb-3">
            <label class="form-label small fw-semibold text-secondary">New Password</label>
            <input type="text" name="new_password" class="form-control font-monospace" placeholder="Leave blank to auto-generate temporary password">
            <div class="form-text small text-muted">
              If left blank, the system will generate a secure temporary password (e.g. <code>AGENT@...</code>) and email it to the partner.
            </div>
          </div>
        </div>

        <div class="modal-footer bg-light border-top">
          <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-warning fw-bold"><i class="fa-solid fa-arrows-rotate me-1"></i>Reset Password Now</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- 4. Modal: Register New Agent -->
<div class="modal fade" id="newAgentModal" tabindex="-1" aria-labelledby="newAgentModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title fw-bold fs-6" id="newAgentModalLabel"><i class="fa-solid fa-plus-circle me-2"></i>Register New B2B Agent</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="/agents/store" method="POST">
        <?= csrf_field() ?>
        <div class="modal-body p-4">
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label small fw-semibold text-secondary">Agent Code <span class="text-danger">*</span></label>
              <input type="text" name="agent_code" class="form-control font-monospace" placeholder="e.g. AGT-001" required>
            </div>
            <div class="col-md-8">
              <label class="form-label small fw-semibold text-secondary">Company / Agency Name <span class="text-danger">*</span></label>
              <input type="text" name="company_name" class="form-control" required placeholder="e.g. Skyline Travel Agency">
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-semibold text-secondary">Contact Person <span class="text-danger">*</span></label>
              <input type="text" name="contact_person" class="form-control" required placeholder="e.g. Sarah Khan">
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-semibold text-secondary">Email (Portal Login) <span class="text-danger">*</span></label>
              <input type="email" name="email" class="form-control" required placeholder="sarah@skylinetravel.com">
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-semibold text-secondary">Mobile Number <span class="text-danger">*</span></label>
              <input type="text" name="mobile" class="form-control" required placeholder="+971 50 111 2233">
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-semibold text-secondary">WhatsApp</label>
              <input type="text" name="whatsapp" class="form-control" placeholder="+971 50 111 2233">
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-semibold text-secondary">City</label>
              <input type="text" name="city" class="form-control" placeholder="Dubai">
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-semibold text-secondary">Country</label>
              <input type="text" name="country" class="form-control" placeholder="United Arab Emirates">
            </div>
            <div class="col-md-4">
              <label class="form-label small fw-semibold text-secondary">Credit Limit ($)</label>
              <input type="number" step="0.01" name="credit_limit" class="form-control" value="0.00">
            </div>
            <div class="col-md-4">
              <label class="form-label small fw-semibold text-secondary">Commission Rate (%)</label>
              <input type="number" step="0.1" name="commission_rate" class="form-control" value="10.0">
            </div>
            <div class="col-md-4">
              <label class="form-label small fw-semibold text-secondary">Payment Terms</label>
              <select name="payment_terms" class="form-select">
                <option value="Prepaid">Prepaid</option>
                <option value="Net 7">Net 7 Days</option>
                <option value="Net 15">Net 15 Days</option>
                <option value="Net 30" selected>Net 30 Days</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-semibold text-secondary">Bank / Wire Details</label>
              <input type="text" name="bank_details" class="form-control" placeholder="Bank Name, IBAN...">
            </div>
            <div class="col-12">
              <div class="alert alert-info py-2 px-3 small mb-0 border-0 rounded-3">
                <i class="fa-solid fa-envelope-circle-check me-1.5 text-info"></i>
                <strong>Password-Free Onboarding:</strong> An email with a secure, single-use activation link will be automatically sent to the partner's work email to set their own password upon registration.
              </div>
            </div>
            <div class="col-12">
              <label class="form-label small fw-semibold text-secondary">Office Address</label>
              <textarea name="address" class="form-control" rows="2" placeholder="Full agency address..."></textarea>
            </div>
            <div class="col-12">
              <label class="form-label small fw-semibold text-secondary">Internal Operational Notes</label>
              <textarea name="notes" class="form-control" rows="2" placeholder="Agreements, contract terms..."></textarea>
            </div>
          </div>
        </div>
        <div class="modal-footer bg-light border-top">
          <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-success fw-semibold"><i class="fa-solid fa-save me-1"></i>Create Agent</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- 5. Modal: Record Agent Settlement -->
<div class="modal fade" id="recordAgentPayModal" tabindex="-1" aria-labelledby="recordAgentPayModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow">
      <div class="modal-header bg-light border-bottom">
        <h5 class="modal-title fw-bold fs-6" id="recordAgentPayModalLabel"><i class="fa-solid fa-receipt text-success me-2"></i>Record General Agent Settlement</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="/agents/pay" method="POST">
        <?= csrf_field() ?>
        <div class="modal-body p-4">
          <div class="mb-3">
            <label class="form-label small fw-semibold text-secondary">Select Agent <span class="text-danger">*</span></label>
            <select name="agent_id" class="form-select" required>
              <option value="">-- Choose Agent --</option>
              <?php foreach ($agents as $a): ?>
                <option value="<?= $a['id'] ?>"><?= e($a['company_name']) ?> (<?= e($a['agent_code']) ?>) — Balance: <?= format_currency((float)$a['current_balance']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="row g-2 mb-3">
            <div class="col-6">
              <label class="form-label small fw-semibold text-secondary">Amount ($) <span class="text-danger">*</span></label>
              <input type="number" step="0.01" min="0.01" name="amount" class="form-control fw-bold" placeholder="0.00" required>
            </div>
            <div class="col-6">
              <label class="form-label small fw-semibold text-secondary">Payment Date <span class="text-danger">*</span></label>
              <input type="date" name="payment_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold text-secondary">Payment Method</label>
            <select name="payment_method" class="form-select">
              <option value="Bank Transfer">Bank Transfer</option>
              <option value="Cash">Cash</option>
              <option value="Credit Card">Credit Card</option>
              <option value="Cheque">Cheque</option>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold text-secondary">Transaction Reference</label>
            <input type="text" name="transaction_reference" class="form-control" placeholder="Bank ref / deposit slip #">
          </div>
          <div class="mb-0">
            <label class="form-label small fw-semibold text-secondary">Notes</label>
            <input type="text" name="notes" class="form-control" placeholder="Settlement remarks...">
          </div>
        </div>
        <div class="modal-footer bg-light border-top">
          <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-success fw-semibold"><i class="fa-solid fa-check me-1"></i>Record Settlement</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function switchAgentView(viewMode) {
  // Hide all view containers
  document.querySelectorAll('.agent-view-container').forEach(el => el.classList.add('d-none'));

  // Reset button states
  document.querySelectorAll('.agent-view-btn').forEach(btn => {
    btn.classList.remove('btn-primary', 'shadow-sm');
    btn.classList.add('btn-light', 'text-muted');
  });

  if (viewMode === 'grid') {
    const el = document.getElementById('agentViewGrid');
    if (el) el.classList.remove('d-none');
    const btn = document.getElementById('btnAgentViewGrid');
    if (btn) { btn.classList.remove('btn-light', 'text-muted'); btn.classList.add('btn-primary', 'shadow-sm'); }
  } else if (viewMode === 'compact') {
    const el = document.getElementById('agentViewCompact');
    if (el) el.classList.remove('d-none');
    const btn = document.getElementById('btnAgentViewCompact');
    if (btn) { btn.classList.remove('btn-light', 'text-muted'); btn.classList.add('btn-primary', 'shadow-sm'); }
  } else {
    const el = document.getElementById('agentViewTable');
    if (el) el.classList.remove('d-none');
    const btn = document.getElementById('btnAgentViewTable');
    if (btn) { btn.classList.remove('btn-light', 'text-muted'); btn.classList.add('btn-primary', 'shadow-sm'); }
  }

  try {
    localStorage.setItem('vt_agent_view', viewMode);
  } catch(e) {}
}

document.addEventListener('DOMContentLoaded', function() {
  const urlParams = new URLSearchParams(window.location.search);
  const paramView = urlParams.get('view');
  if (paramView) {
    switchAgentView(paramView);
  } else {
    try {
      const saved = localStorage.getItem('vt_agent_view');
      if (window.innerWidth < 768 && (!saved || saved === 'table')) {
        switchAgentView('grid');
      } else if (saved) {
        switchAgentView(saved);
      } else if (window.innerWidth < 768) {
        switchAgentView('grid');
      }
    } catch(e) {}
  }
});

function openEditAgentModal(data) {
  if (!data) return;
  document.getElementById('edit_agent_id').value = data.id || '';
  document.getElementById('edit_agent_code').value = data.agent_code || '';
  document.getElementById('edit_company_name').value = data.company_name || '';
  document.getElementById('edit_contact_person').value = data.contact_person || '';
  document.getElementById('edit_email').value = data.email || '';
  document.getElementById('edit_mobile').value = data.mobile || '';
  document.getElementById('edit_whatsapp').value = data.whatsapp || '';
  document.getElementById('edit_city').value = data.city || '';
  document.getElementById('edit_country').value = data.country || '';
  document.getElementById('edit_credit_limit').value = data.credit_limit || '0.00';
  document.getElementById('edit_commission_rate').value = data.commission_rate || '0.0';
  document.getElementById('edit_payment_terms').value = data.payment_terms || 'Net 30';
  document.getElementById('edit_is_active').value = (data.is_active !== undefined) ? data.is_active : 1;
  document.getElementById('edit_bank_details').value = data.bank_details || '';
  document.getElementById('edit_address').value = data.address || '';
  document.getElementById('edit_notes').value = data.notes || '';

  window.openModalById('editAgentModal');
}

function openAgentAdjustModal(id, name, code, balance) {
  document.getElementById('adj_agent_id').value = id;
  document.getElementById('adj_agent_name').innerText = name + ' (' + code + ')';
  const balEl = document.getElementById('adj_agent_balance');
  balEl.innerText = '$' + Number(balance).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  balEl.className = 'fw-bold font-monospace fs-6 ' + (balance > 0 ? 'text-danger' : 'text-success');

  // Reset to top up by default
  document.getElementById('adj_type_top_up').checked = true;
  onAdjustTypeChange('top_up');

  window.openModalById('adjustBalanceModal');
}

function onAdjustTypeChange(type) {
  const header = document.getElementById('adjustBalanceHeader');
  const btn = document.getElementById('adj_submit_btn');
  const help = document.getElementById('adj_help_text');

  if (type === 'top_up') {
    header.className = 'modal-header bg-success text-white';
    btn.className = 'btn btn-success fw-semibold';
    btn.innerHTML = '<i class="fa-solid fa-circle-arrow-up me-1"></i> Confirm Top Up';
    help.innerText = 'Top Up records funds received from agent partner, decreasing their outstanding balance.';
  } else {
    header.className = 'modal-header bg-danger text-white';
    btn.className = 'btn btn-danger fw-semibold';
    btn.innerHTML = '<i class="fa-solid fa-circle-arrow-down me-1"></i> Confirm Debit';
    help.innerText = 'Debit applies fees, service charges, or deductions, increasing their outstanding balance.';
  }
}

function openResetPasswordModal(id, name, code, email) {
  document.getElementById('reset_agent_id').value = id;
  document.getElementById('reset_agent_name').innerText = name + ' (' + code + ')';
  document.getElementById('reset_agent_email').innerText = email || 'No email registered';
  window.openModalById('resetAgentPasswordModal');
}
</script>

<?php require_once dirname(__DIR__) . '/layouts/footer.php'; ?>
