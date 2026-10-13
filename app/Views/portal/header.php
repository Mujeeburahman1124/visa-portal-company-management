<?php
$pageTitle = $pageTitle ?? 'Applicant Portal — MS TRAVEL HUB';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <!-- Enterprise SEO & Open Graph Engine -->
  <?= \App\Services\SeoService::renderTags([
      'title' => $pageTitle,
      'description' => 'Official MS Travel Hub Applicant Self-Service Portal. Real-time visa application tracking, checklist document uploads, and consular appointment management.',
      'robots' => 'noindex, nofollow'
  ]) ?>
  <?= \App\Services\SeoService::renderStructuredData() ?>
  
  <!-- Favicons -->
  <link rel="icon" type="image/png" href="/assets/images/favicon.png?v=2">
  <link rel="shortcut icon" type="image/x-icon" href="/favicon.ico?v=2">
  <link rel="apple-touch-icon" href="/assets/images/logo.png">

  <!-- Typography -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Outfit:wght@600;700;800;900&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

  <!-- Frameworks & Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
  <link rel="stylesheet" href="/assets/css/main.css?v=7.0.0">

  <!-- Bento Styling -->
  <link rel="stylesheet" href="/assets/css/pages/portal-bento.css?v=<?= time() ?>">
</head>
<body class="portal-body">
