<?php
declare(strict_types=1);

require_once __DIR__ . '/../app/autoload.php';

$pdo = \App\Config\Database::getConnection();

$packages = $pdo->query("SELECT id, name FROM visa_services")->fetchAll(PDO::FETCH_ASSOC);

foreach ($packages as $pkg) {
    $desc = "Official consular visa pathway with fast-track document pre-check, verified embassy appointment booking, and SLA turnaround guarantee.";
    $name = strtolower($pkg['name']);
    if (str_contains($name, 'golden')) {
        $desc = "Exclusive 10-year residency visa for investors, entrepreneurs, and executive leaders with multiple-entry privileges.";
    } elseif (str_contains($name, 'tourist') && str_contains($name, 'uae')) {
        $desc = "Fast-track 60-day tourist entry visa for leisure and business exploration across Dubai, Abu Dhabi, and the Northern Emirates.";
    } elseif (str_contains($name, 'employment')) {
        $desc = "Full 2-year mainland employment work permit and residency visa sponsorship processing with Ministry approvals.";
    } elseif (str_contains($name, 'visitor') && str_contains($name, 'uk')) {
        $desc = "Standard 6-month UK visitor visa for tourism, family visits, or business meetings with comprehensive biometric assistance.";
    } elseif (str_contains($name, 'schengen') || str_contains($name, 'france')) {
        $desc = "Short-stay Schengen visa granting seamless travel across 29 European countries with premium dossier preparation.";
    } elseif (str_contains($name, 'canada')) {
        $desc = "Temporary resident visa for Canada with biometrics coordination, passport submission, and complete documentation review.";
    } elseif (str_contains($name, 'umrah') || str_contains($name, 'saudi')) {
        $desc = "Electronic Umrah and tourist visa for the Kingdom of Saudi Arabia with instant digital issuance and multi-city validity.";
    }
    
    $pdo->prepare("UPDATE visa_services SET description = ? WHERE id = ?")->execute([$desc, $pkg['id']]);
}

echo "Updated descriptions for " . count($packages) . " visa packages!\n";
