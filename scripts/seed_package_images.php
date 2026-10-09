<?php
require_once dirname(__DIR__) . '/app/autoload.php';
$pdo = App\Config\Database::getConnection();

$imageMap = [
    1 => '/assets/images/destinations/uae-tourist.jpg',
    2 => '/assets/images/destinations/uae-tourist.jpg',
    3 => '/assets/images/destinations/uae-golden.jpg',
    4 => '/assets/images/destinations/uae-golden.jpg',
    5 => '/assets/images/destinations/uk-visitor.jpg',
    6 => '/assets/images/destinations/uk-visitor.jpg',
    7 => '/assets/images/destinations/us-visitor.jpg',
    8 => '/assets/images/destinations/france-schengen.jpg',
    9 => '/assets/images/destinations/saudi-umrah.jpg',
    10 => '/assets/images/destinations/canada-visitor.jpg',
];

foreach ($imageMap as $id => $path) {
    $stmt = $pdo->prepare("UPDATE visa_services SET image_url = ? WHERE id = ?");
    $stmt->execute([$path, $id]);
}

echo "SUCCESS: Mapped default destination images to visa_services\n";
