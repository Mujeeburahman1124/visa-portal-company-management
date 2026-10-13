<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/autoload.php';

use App\Config\Database;

class CategoryAndPackageCrudTest
{
    public static function run(): void
    {
        $pdo = Database::getConnection();
        echo "=== Running Category & Package Management Tests ===\n";

        // 1. Create a temporary test category
        $stmt = $pdo->prepare("INSERT INTO visa_categories (name, slug, description, icon, is_active, created_at) VALUES (?, ?, ?, ?, 1, CURRENT_TIMESTAMP)");
        $testName = "Automated Test Category " . time();
        $testSlug = "auto-test-" . time();
        $stmt->execute([$testName, $testSlug, 'Test Description', 'fa-solid fa-star']);
        $catId = (int)$pdo->lastInsertId();
        echo "✓ Created test category #$catId\n";

        // 2. Update the test category
        $updStmt = $pdo->prepare("UPDATE visa_categories SET name = ?, description = ?, is_active = 0 WHERE id = ?");
        $updName = $testName . " Updated";
        $updStmt->execute([$updName, 'Updated Description', $catId]);
        
        $fetch = $pdo->query("SELECT * FROM visa_categories WHERE id = $catId")->fetch(PDO::FETCH_ASSOC);
        assert($fetch['name'] === $updName, "Category name update failed");
        assert((int)$fetch['is_active'] === 0, "Category is_active update failed");
        echo "✓ Updated test category #$catId successfully\n";

        // 3. Delete the test category
        $delStmt = $pdo->prepare("DELETE FROM visa_categories WHERE id = ?");
        $delStmt->execute([$catId]);
        $check = $pdo->query("SELECT COUNT(*) FROM visa_categories WHERE id = $catId")->fetchColumn();
        assert((int)$check === 0, "Category delete failed");
        echo "✓ Deleted test category #$catId successfully\n";

        // 4. Test Visa Type creation, update, and deletion
        $typeStmt = $pdo->prepare("INSERT INTO visa_types (name, slug, description, icon, is_active, created_at) VALUES (?, ?, ?, ?, 1, CURRENT_TIMESTAMP)");
        $typeName = "Automated Test Type " . time();
        $typeSlug = "auto-type-" . time();
        $typeStmt->execute([$typeName, $typeSlug, 'Type Description', 'fa-solid fa-file']);
        $typeId = (int)$pdo->lastInsertId();
        echo "✓ Created test visa type #$typeId\n";

        $updType = $pdo->prepare("UPDATE visa_types SET name = ?, is_active = 0 WHERE id = ?");
        $updType->execute([$typeName . " Updated", $typeId]);
        $fetchType = $pdo->query("SELECT * FROM visa_types WHERE id = $typeId")->fetch(PDO::FETCH_ASSOC);
        assert($fetchType['name'] === $typeName . " Updated", "Type update failed");
        echo "✓ Updated test visa type #$typeId successfully\n";

        $delType = $pdo->prepare("DELETE FROM visa_types WHERE id = ?");
        $delType->execute([$typeId]);
        $checkType = $pdo->query("SELECT COUNT(*) FROM visa_types WHERE id = $typeId")->fetchColumn();
        assert((int)$checkType === 0, "Type delete failed");
        echo "✓ Deleted test visa type #$typeId successfully\n";

        echo "=== ALL CATEGORY & PACKAGE CRUD TESTS PASSED ===\n";
    }
}

CategoryAndPackageCrudTest::run();
