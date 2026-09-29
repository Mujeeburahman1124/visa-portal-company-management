<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Config\App;
use App\Config\Database;
use App\Middleware\AuthMiddleware;
use App\Middleware\RoleMiddleware;
use App\Services\AuditService;
use PDO;

class InventoryController
{
    public function index(): void
    {
        AuthMiddleware::handle();
        RoleMiddleware::authorize(['super-admin', 'admin', 'branch-manager', 'accounts', 'visa-manager']);
        $pdo = Database::getConnection();

        $search = trim($_GET['search'] ?? '');
        $categoryId = (int)($_GET['category_id'] ?? 0);
        $supplierId = (int)($_GET['supplier_id'] ?? 0);
        $status = trim($_GET['status'] ?? '');
        $branchId = get_scoped_branch_id((int)($_GET['branch_id'] ?? 0));

        $sql = "SELECT i.*, 
                       c.name as category_name, c.code as category_code,
                       s.company_name as supplier_name, s.supplier_code,
                       b.name as branch_name,
                       (SELECT COUNT(*) FROM inventory_transactions it WHERE it.item_id = i.id) as total_movements
                FROM inventory_items i
                JOIN inventory_categories c ON i.category_id = c.id
                LEFT JOIN suppliers s ON i.supplier_id = s.id
                LEFT JOIN branches b ON i.branch_id = b.id
                WHERE 1=1";

        $params = [];
        if ($search !== '') {
            $sql .= " AND (i.name LIKE ? OR i.item_code LIKE ? OR i.location LIKE ?)";
            $term = "%{$search}%";
            $params = [$term, $term, $term];
        }
        if ($categoryId > 0) {
            $sql .= " AND i.category_id = ?";
            $params[] = $categoryId;
        }
        if ($supplierId > 0) {
            $sql .= " AND i.supplier_id = ?";
            $params[] = $supplierId;
        }
        if ($branchId > 0) {
            $sql .= " AND i.branch_id = ?";
            $params[] = $branchId;
        }
        if ($status !== '') {
            $sql .= " AND i.status = ?";
            $params[] = $status;
        }

        $sql .= " ORDER BY i.current_stock <= i.minimum_stock DESC, i.name ASC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Overall Metrics
        $totalItems = count($items);
        $totalUnits = array_reduce($items, fn($s, $i) => $s + (int)$i['current_stock'], 0);
        $lowStockCount = count(array_filter($items, fn($i) => (int)$i['current_stock'] <= (int)$i['minimum_stock']));
        $totalValuation = array_reduce($items, fn($s, $i) => $s + ((int)$i['current_stock'] * (float)$i['purchase_price']), 0.0);
        $totalTransactionsCount = (int)$pdo->query("SELECT COUNT(*) FROM inventory_transactions")->fetchColumn();

        $categories = $pdo->query("SELECT id, name, code FROM inventory_categories WHERE is_active = 1 ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
        $suppliers = $pdo->query("SELECT id, company_name, supplier_code FROM suppliers WHERE is_active = 1 ORDER BY company_name ASC")->fetchAll(PDO::FETCH_ASSOC);
        $branches = $pdo->query("SELECT id, name FROM branches ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);

        require_once dirname(__DIR__) . '/Views/inventory/index.php';
    }

    public function store(): void
    {
        AuthMiddleware::handle();
        RoleMiddleware::authorize(['super-admin', 'admin', 'branch-manager']);
        $pdo = Database::getConnection();
        $currentUser = auth_user();

        $code = strtoupper(trim($_POST['item_code'] ?? ''));
        $name = trim($_POST['name'] ?? '');
        $categoryId = (int)($_POST['category_id'] ?? 1);
        $supplierId = !empty($_POST['supplier_id']) ? (int)$_POST['supplier_id'] : null;
        $branchId = get_scoped_branch_id(!empty($_POST['branch_id']) ? (int)$_POST['branch_id'] : 0) ?: 1;
        $unit = trim($_POST['unit'] ?? 'Pcs');
        $openingStock = max(0, (int)($_POST['opening_stock'] ?? 0));
        $minStock = max(1, (int)($_POST['minimum_stock'] ?? 10));
        $purchasePrice = max(0.00, (float)($_POST['purchase_price'] ?? 0.00));
        $sellingPrice = max(0.00, (float)($_POST['selling_price'] ?? 0.00));
        $currency = strtoupper(trim($_POST['currency'] ?? 'AED'));
        $location = trim($_POST['location'] ?? 'Main Storage');
        $notes = trim($_POST['notes'] ?? '');

        if (empty($code) || empty($name)) {
            redirect('/inventory', 'Item SKU code and item name are required.', 'danger');
        }

        // Check duplicate code
        $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM inventory_items WHERE item_code = ?");
        $checkStmt->execute([$code]);
        if ((int)$checkStmt->fetchColumn() > 0) {
            redirect('/inventory', "Item code '{$code}' already exists.", 'danger');
        }

        $status = $openingStock <= 0 ? 'Out of Stock' : ($openingStock <= $minStock ? 'Low Stock' : 'In Stock');

        $stmt = $pdo->prepare("INSERT INTO inventory_items (
            item_code, name, category_id, supplier_id, branch_id, unit, opening_stock, current_stock,
            minimum_stock, purchase_price, selling_price, currency, location, status, notes
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $code, $name, $categoryId, $supplierId, $branchId, $unit, $openingStock, $openingStock,
            $minStock, $purchasePrice, $sellingPrice, $currency, $location, $status, $notes
        ]);
        $newId = (int)$pdo->lastInsertId();

        // Record Initial Intake Transaction
        if ($openingStock > 0) {
            $txnCode = 'TXN-IN-' . date('Ymd') . '-' . rand(1000, 9999);
            $stmtTxn = $pdo->prepare("INSERT INTO inventory_transactions (
                transaction_code, item_id, transaction_type, quantity, prev_stock, new_stock, unit_price, total_price, currency, supplier_id, reason_notes, performed_by, transaction_date
            ) VALUES (?, ?, 'STOCK_IN', ?, 0, ?, ?, ?, ?, ?, 'Initial opening stock registered', ?, ?)");
            $stmtTxn->execute([$txnCode, $newId, $openingStock, $openingStock, $purchasePrice, ($openingStock * $purchasePrice), $currency, $supplierId, (int)($currentUser['id'] ?? 1), date('Y-m-d')]);
        }

        AuditService::log('INVENTORY_ITEM_CREATED', 'Inventory', $newId, "Created item {$name} ({$code}) with opening stock {$openingStock} {$unit}");

        redirect('/inventory', "Item '{$name}' registered successfully.", 'success');
    }

    public function update(): void
    {
        AuthMiddleware::handle();
        RoleMiddleware::authorize(['super-admin', 'admin', 'branch-manager']);
        $pdo = Database::getConnection();

        $id = (int)($_POST['id'] ?? 0);
        $code = strtoupper(trim($_POST['item_code'] ?? ''));
        $name = trim($_POST['name'] ?? '');
        $categoryId = (int)($_POST['category_id'] ?? 1);
        $supplierId = !empty($_POST['supplier_id']) ? (int)$_POST['supplier_id'] : null;
        $branchId = !empty($_POST['branch_id']) ? (int)$_POST['branch_id'] : 1;
        $unit = trim($_POST['unit'] ?? 'Pcs');
        $minStock = max(1, (int)($_POST['minimum_stock'] ?? 10));
        $purchasePrice = max(0.00, (float)($_POST['purchase_price'] ?? 0.00));
        $sellingPrice = max(0.00, (float)($_POST['selling_price'] ?? 0.00));
        $currency = strtoupper(trim($_POST['currency'] ?? 'AED'));
        $location = trim($_POST['location'] ?? '');
        $notes = trim($_POST['notes'] ?? '');

        if ($id <= 0 || empty($name) || empty($code)) {
            redirect('/inventory', 'Item ID, SKU and name are required.', 'danger');
        }

        $currStock = (int)$pdo->query("SELECT current_stock FROM inventory_items WHERE id = {$id}")->fetchColumn();
        $status = $currStock <= 0 ? 'Out of Stock' : ($currStock <= $minStock ? 'Low Stock' : 'In Stock');

        $stmt = $pdo->prepare("UPDATE inventory_items SET 
            item_code = ?, name = ?, category_id = ?, supplier_id = ?, branch_id = ?, unit = ?,
            minimum_stock = ?, purchase_price = ?, selling_price = ?, currency = ?, location = ?, status = ?, notes = ?, updated_at = CURRENT_TIMESTAMP 
            WHERE id = ?");
        $stmt->execute([
            $code, $name, $categoryId, $supplierId, $branchId, $unit,
            $minStock, $purchasePrice, $sellingPrice, $currency, $location, $status, $notes, $id
        ]);

        AuditService::log('INVENTORY_ITEM_UPDATED', 'Inventory', $id, "Updated details for item {$name} ({$code})");

        redirect('/inventory', "Item '{$name}' updated successfully.", 'success');
    }

    public function stockIn(): void
    {
        AuthMiddleware::handle();
        RoleMiddleware::authorize(['super-admin', 'admin', 'branch-manager', 'accounts']);
        $pdo = Database::getConnection();
        $currentUser = auth_user();

        $itemId = (int)($_POST['item_id'] ?? 0);
        $qty = (int)($_POST['quantity'] ?? 0);
        $unitPrice = (float)($_POST['unit_price'] ?? 0.00);
        $supplierId = !empty($_POST['supplier_id']) ? (int)$_POST['supplier_id'] : null;
        $invoiceRef = trim($_POST['supplier_invoice_ref'] ?? '');
        $notes = trim($_POST['reason_notes'] ?? 'Stock replenishment');
        $date = trim($_POST['transaction_date'] ?? date('Y-m-d'));

        if ($itemId <= 0 || $qty <= 0) {
            redirect('/inventory', 'Item and valid intake quantity greater than 0 are required.', 'danger');
        }

        $itemStmt = $pdo->prepare("SELECT * FROM inventory_items WHERE id = ?");
        $itemStmt->execute([$itemId]);
        $item = $itemStmt->fetch(PDO::FETCH_ASSOC);

        if (!$item) {
            redirect('/inventory', 'Inventory item not found.', 'danger');
        }

        $prevStock = (int)$item['current_stock'];
        $newStock = $prevStock + $qty;
        $minStock = (int)$item['minimum_stock'];
        $status = $newStock <= 0 ? 'Out of Stock' : ($newStock <= $minStock ? 'Low Stock' : 'In Stock');

        // Update Item Stock
        $pdo->prepare("UPDATE inventory_items SET current_stock = ?, status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?")
            ->execute([$newStock, $status, $itemId]);

        // Record Immutable Audit Log
        $txnCode = 'TXN-IN-' . date('Ymd') . '-' . rand(1000, 9999);
        $stmtTxn = $pdo->prepare("INSERT INTO inventory_transactions (
            transaction_code, item_id, transaction_type, quantity, prev_stock, new_stock, unit_price, total_price, currency, supplier_id, supplier_invoice_ref, reason_notes, performed_by, transaction_date
        ) VALUES (?, ?, 'STOCK_IN', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmtTxn->execute([
            $txnCode, $itemId, $qty, $prevStock, $newStock, $unitPrice, ($qty * $unitPrice), $item['currency'], $supplierId, $invoiceRef, $notes, (int)($currentUser['id'] ?? 1), $date
        ]);

        AuditService::log('STOCK_IN', 'Inventory', $itemId, "Added +{$qty} {$item['unit']} to {$item['name']} (Prev: {$prevStock}, New: {$newStock})");

        redirect('/inventory', "Stock-In of +{$qty} {$item['unit']} recorded for '{$item['name']}' (Current: {$newStock}).", 'success');
    }

    public function stockOut(): void
    {
        AuthMiddleware::handle();
        RoleMiddleware::authorize(['super-admin', 'admin', 'branch-manager', 'accounts']);
        $pdo = Database::getConnection();
        $currentUser = auth_user();

        $itemId = (int)($_POST['item_id'] ?? 0);
        $qty = (int)($_POST['quantity'] ?? 0);
        $notes = trim($_POST['reason_notes'] ?? 'Issued for visa application processing');
        $date = trim($_POST['transaction_date'] ?? date('Y-m-d'));

        if ($itemId <= 0 || $qty <= 0) {
            redirect('/inventory', 'Item and valid quantity are required.', 'danger');
        }

        $itemStmt = $pdo->prepare("SELECT * FROM inventory_items WHERE id = ?");
        $itemStmt->execute([$itemId]);
        $item = $itemStmt->fetch(PDO::FETCH_ASSOC);

        if (!$item) {
            redirect('/inventory', 'Inventory item not found.', 'danger');
        }

        $prevStock = (int)$item['current_stock'];
        if ($qty > $prevStock) {
            redirect('/inventory', "Insufficient stock for '{$item['name']}'. Requested: {$qty}, Available: {$prevStock}.", 'danger');
        }

        $newStock = $prevStock - $qty;
        $minStock = (int)$item['minimum_stock'];
        $status = $newStock <= 0 ? 'Out of Stock' : ($newStock <= $minStock ? 'Low Stock' : 'In Stock');

        // Update Item Stock
        $pdo->prepare("UPDATE inventory_items SET current_stock = ?, status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?")
            ->execute([$newStock, $status, $itemId]);

        // Record Immutable Audit Log
        $txnCode = 'TXN-OUT-' . date('Ymd') . '-' . rand(1000, 9999);
        $stmtTxn = $pdo->prepare("INSERT INTO inventory_transactions (
            transaction_code, item_id, transaction_type, quantity, prev_stock, new_stock, unit_price, total_price, currency, reason_notes, performed_by, transaction_date
        ) VALUES (?, ?, 'STOCK_OUT', ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmtTxn->execute([
            $txnCode, $itemId, $qty, $prevStock, $newStock, (float)$item['purchase_price'], ($qty * (float)$item['purchase_price']), $item['currency'], $notes, (int)($currentUser['id'] ?? 1), $date
        ]);

        AuditService::log('STOCK_OUT', 'Inventory', $itemId, "Issued -{$qty} {$item['unit']} from {$item['name']} (Prev: {$prevStock}, New: {$newStock})");

        redirect('/inventory', "Stock-Out of -{$qty} {$item['unit']} recorded for '{$item['name']}' (Current: {$newStock}).", 'success');
    }

    public function adjust(): void
    {
        AuthMiddleware::handle();
        RoleMiddleware::authorize(['super-admin', 'admin', 'branch-manager']);
        $pdo = Database::getConnection();
        $currentUser = auth_user();

        $itemId = (int)($_POST['item_id'] ?? 0);
        $newStock = max(0, (int)($_POST['new_stock'] ?? 0));
        $notes = trim($_POST['reason_notes'] ?? '');

        if ($itemId <= 0 || empty($notes)) {
            redirect('/inventory', 'Item and mandatory reason for stock adjustment are required.', 'danger');
        }

        $itemStmt = $pdo->prepare("SELECT * FROM inventory_items WHERE id = ?");
        $itemStmt->execute([$itemId]);
        $item = $itemStmt->fetch(PDO::FETCH_ASSOC);

        if (!$item) {
            redirect('/inventory', 'Inventory item not found.', 'danger');
        }

        $prevStock = (int)$item['current_stock'];
        $diff = $newStock - $prevStock;
        $minStock = (int)$item['minimum_stock'];
        $status = $newStock <= 0 ? 'Out of Stock' : ($newStock <= $minStock ? 'Low Stock' : 'In Stock');

        // Update Item Stock
        $pdo->prepare("UPDATE inventory_items SET current_stock = ?, status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?")
            ->execute([$newStock, $status, $itemId]);

        // Record Immutable Audit Log
        $txnCode = 'TXN-ADJ-' . date('Ymd') . '-' . rand(1000, 9999);
        $stmtTxn = $pdo->prepare("INSERT INTO inventory_transactions (
            transaction_code, item_id, transaction_type, quantity, prev_stock, new_stock, unit_price, total_price, currency, reason_notes, performed_by, transaction_date
        ) VALUES (?, ?, 'ADJUSTMENT', ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmtTxn->execute([
            $txnCode, $itemId, abs($diff), $prevStock, $newStock, (float)$item['purchase_price'], (abs($diff) * (float)$item['purchase_price']), $item['currency'], $notes, (int)($currentUser['id'] ?? 1), date('Y-m-d')
        ]);

        AuditService::log('STOCK_ADJUSTMENT', 'Inventory', $itemId, "Adjusted stock for {$item['name']} from {$prevStock} to {$newStock} (Reason: {$notes})");

        redirect('/inventory', "Stock adjustment for '{$item['name']}' saved (New Balance: {$newStock}).", 'success');
    }

    public function transfer(): void
    {
        AuthMiddleware::handle();
        RoleMiddleware::authorize(['super-admin', 'admin', 'branch-manager']);
        $pdo = Database::getConnection();
        $currentUser = auth_user();

        $itemId = (int)($_POST['item_id'] ?? 0);
        $sourceBranch = (int)($_POST['source_branch_id'] ?? 1);
        $destBranch = (int)($_POST['destination_branch_id'] ?? 2);
        $qty = (int)($_POST['quantity'] ?? 0);
        $notes = trim($_POST['reason_notes'] ?? 'Inter-branch stock transfer');

        if ($itemId <= 0 || $qty <= 0 || $sourceBranch === $destBranch) {
            redirect('/inventory', 'Valid item, quantity, and distinct source and destination branches are required.', 'danger');
        }

        $itemStmt = $pdo->prepare("SELECT * FROM inventory_items WHERE id = ?");
        $itemStmt->execute([$itemId]);
        $item = $itemStmt->fetch(PDO::FETCH_ASSOC);

        if (!$item) {
            redirect('/inventory', 'Item not found.', 'danger');
        }

        $prevStock = (int)$item['current_stock'];
        if ($qty > $prevStock) {
            redirect('/inventory', "Cannot transfer {$qty} units. Only {$prevStock} available at source branch.", 'danger');
        }

        $txnCode = 'TXN-TRF-' . date('Ymd') . '-' . rand(1000, 9999);
        $stmtTxn = $pdo->prepare("INSERT INTO inventory_transactions (
            transaction_code, item_id, transaction_type, quantity, prev_stock, new_stock, unit_price, total_price, currency, source_branch_id, destination_branch_id, reason_notes, performed_by, transaction_date
        ) VALUES (?, ?, 'TRANSFER', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmtTxn->execute([
            $txnCode, $itemId, $qty, $prevStock, ($prevStock - $qty), (float)$item['purchase_price'], ($qty * (float)$item['purchase_price']), $item['currency'], $sourceBranch, $destBranch, $notes, (int)($currentUser['id'] ?? 1), date('Y-m-d')
        ]);

        AuditService::log('STOCK_TRANSFER', 'Inventory', $itemId, "Transferred {$qty} {$item['unit']} of {$item['name']} from branch #{$sourceBranch} to branch #{$destBranch}");

        redirect('/inventory', "Transferred {$qty} {$item['unit']} of '{$item['name']}' to destination branch.", 'success');
    }

    public function purchase(): void
    {
        AuthMiddleware::handle();
        RoleMiddleware::authorize(['super-admin', 'admin', 'branch-manager', 'accounts']);
        $pdo = Database::getConnection();
        $currentUser = auth_user();

        $itemId = (int)($_POST['item_id'] ?? 0);
        $supplierId = (int)($_POST['supplier_id'] ?? 0);
        $qty = (int)($_POST['quantity'] ?? 0);
        $unitPrice = (float)($_POST['purchase_price'] ?? 0.00);
        $invoiceRef = trim($_POST['supplier_invoice_ref'] ?? ('SUP-INV-' . date('Ymd') . '-' . rand(100, 999)));
        $date = trim($_POST['purchase_date'] ?? date('Y-m-d'));
        $paymentStatus = trim($_POST['payment_status'] ?? 'Pending');
        $notes = trim($_POST['notes'] ?? 'Supplier stock purchase order');

        if ($itemId <= 0 || $supplierId <= 0 || $qty <= 0 || $unitPrice <= 0) {
            redirect('/inventory', 'Item, Supplier, Quantity and Unit Purchase Price are required.', 'danger');
        }

        $itemStmt = $pdo->prepare("SELECT * FROM inventory_items WHERE id = ?");
        $itemStmt->execute([$itemId]);
        $item = $itemStmt->fetch(PDO::FETCH_ASSOC);

        $suppStmt = $pdo->prepare("SELECT * FROM suppliers WHERE id = ?");
        $suppStmt->execute([$supplierId]);
        $supplier = $suppStmt->fetch(PDO::FETCH_ASSOC);

        if (!$item || !$supplier) {
            redirect('/inventory', 'Item or Supplier not found.', 'danger');
        }

        $prevStock = (int)$item['current_stock'];
        $newStock = $prevStock + $qty;
        $totalCost = round($qty * $unitPrice, 2);
        $minStock = (int)$item['minimum_stock'];
        $status = $newStock <= 0 ? 'Out of Stock' : ($newStock <= $minStock ? 'Low Stock' : 'In Stock');

        // 1. Update Inventory Item Stock & Purchase Price
        $pdo->prepare("UPDATE inventory_items SET current_stock = ?, purchase_price = ?, status = ?, supplier_id = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?")
            ->execute([$newStock, $unitPrice, $status, $supplierId, $itemId]);

        // 2. Record Immutable Inventory Transaction
        $txnCode = 'TXN-PUR-' . date('Ymd') . '-' . rand(1000, 9999);
        $stmtTxn = $pdo->prepare("INSERT INTO inventory_transactions (
            transaction_code, item_id, transaction_type, quantity, prev_stock, new_stock, unit_price, total_price, currency, supplier_id, supplier_invoice_ref, reason_notes, performed_by, transaction_date
        ) VALUES (?, ?, 'SUPPLIER_PURCHASE', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmtTxn->execute([
            $txnCode, $itemId, $qty, $prevStock, $newStock, $unitPrice, $totalCost, $item['currency'], $supplierId, $invoiceRef, $notes, (int)($currentUser['id'] ?? 1), $date
        ]);

        // 3. Update Supplier Financial Ledger
        $payRef = 'SPAY-INV-' . date('Ymd') . '-' . rand(1000, 9999);
        $paidAmount = $paymentStatus === 'Paid' ? $totalCost : 0.00;

        $stmtSupPay = $pdo->prepare("INSERT INTO supplier_payments (
            payment_reference, supplier_id, application_id, payable_amount, paid_amount, currency, supplier_invoice_ref, payment_date, payment_method, transaction_reference, payment_status, notes, created_by
        ) VALUES (?, ?, NULL, ?, ?, ?, ?, ?, 'Bank Transfer', ?, ?, ?, ?)");
        $stmtSupPay->execute([
            $payRef, $supplierId, $totalCost, $paidAmount, $item['currency'], $invoiceRef, $date, ('TXN-PUR-' . rand(100000, 999999)), $paymentStatus, "Inventory purchase: {$qty}x {$item['name']} (Inv: {$invoiceRef})", (int)($currentUser['id'] ?? 1)
        ]);

        AuditService::log('SUPPLIER_INVENTORY_PURCHASE', 'Inventory', $itemId, "Purchased {$qty} {$item['unit']} of {$item['name']} from {$supplier['company_name']} for {$item['currency']} " . number_format($totalCost, 2));

        redirect('/inventory', "Purchase order for {$qty} {$item['unit']} of '{$item['name']}' from {$supplier['company_name']} completed. Stock updated: {$newStock}.", 'success');
    }

    public function history(): void
    {
        AuthMiddleware::handle();
        RoleMiddleware::authorize(['super-admin', 'admin', 'branch-manager', 'accounts', 'visa-manager']);
        $pdo = Database::getConnection();

        $search = trim($_GET['search'] ?? '');
        $txnType = trim($_GET['transaction_type'] ?? '');
        $itemId = (int)($_GET['item_id'] ?? 0);
        $supplierId = (int)($_GET['supplier_id'] ?? 0);
        $dateFrom = trim($_GET['date_from'] ?? '');
        $dateTo = trim($_GET['date_to'] ?? '');

        $sql = "SELECT it.*, 
                       i.name as item_name, i.item_code, i.unit,
                       c.name as category_name,
                       s.company_name as supplier_name,
                       sb.name as source_branch_name,
                       db.name as dest_branch_name,
                       u.name as performed_by_name
                FROM inventory_transactions it
                JOIN inventory_items i ON it.item_id = i.id
                JOIN inventory_categories c ON i.category_id = c.id
                LEFT JOIN suppliers s ON it.supplier_id = s.id
                LEFT JOIN branches sb ON it.source_branch_id = sb.id
                LEFT JOIN branches db ON it.destination_branch_id = db.id
                LEFT JOIN users u ON it.performed_by = u.id
                WHERE 1=1";

        $params = [];
        if ($search !== '') {
            $sql .= " AND (it.transaction_code LIKE ? OR i.name LIKE ? OR i.item_code LIKE ? OR it.supplier_invoice_ref LIKE ? OR it.reason_notes LIKE ?)";
            $term = "%{$search}%";
            $params = array_fill(0, 5, $term);
        }
        if ($txnType !== '') {
            $sql .= " AND it.transaction_type = ?";
            $params[] = $txnType;
        }
        if ($itemId > 0) {
            $sql .= " AND it.item_id = ?";
            $params[] = $itemId;
        }
        if ($supplierId > 0) {
            $sql .= " AND it.supplier_id = ?";
            $params[] = $supplierId;
        }
        if (!empty($dateFrom)) {
            $sql .= " AND it.transaction_date >= ?";
            $params[] = $dateFrom;
        }
        if (!empty($dateTo)) {
            $sql .= " AND it.transaction_date <= ?";
            $params[] = $dateTo;
        }

        $sql .= " ORDER BY it.id DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $items = $pdo->query("SELECT id, name, item_code FROM inventory_items ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC);
        $suppliers = $pdo->query("SELECT id, company_name FROM suppliers WHERE is_active = 1 ORDER BY company_name ASC")->fetchAll(PDO::FETCH_ASSOC);

        require_once dirname(__DIR__) . '/Views/inventory/history.php';
    }

    public function delete(): void
    {
        AuthMiddleware::handle();
        RoleMiddleware::authorize(['super-admin', 'admin']);
        $pdo = Database::getConnection();

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            redirect('/inventory', 'Invalid item identifier.', 'danger');
        }

        $stmt = $pdo->prepare("SELECT * FROM inventory_items WHERE id = ?");
        $stmt->execute([$id]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$item) {
            redirect('/inventory', 'Item not found.', 'danger');
        }

        $pdo->prepare("DELETE FROM inventory_transactions WHERE item_id = ?")->execute([$id]);
        $pdo->prepare("DELETE FROM inventory_items WHERE id = ?")->execute([$id]);

        AuditService::log('INVENTORY_ITEM_DELETED', 'Inventory', $id, "Deleted inventory item {$item['name']} ({$item['item_code']})");

        redirect('/inventory', "Item '{$item['name']}' deleted successfully.", 'success');
    }
}
