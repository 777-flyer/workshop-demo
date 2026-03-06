<?php
/**
 * includes/header.php
 *
 * Renders the <head> block and navigation bar.
 *
 * Expected vars (set before including):
 *   $pageTitle  string  - page title (required)
 *   $activeNav  string  - nav link key to mark as active (optional)
 *   $rootPath   string  - path prefix to reach project root, e.g. '' or '../'
 */

$rootPath  = $rootPath  ?? '';
$pageTitle = $pageTitle ?? 'Workshop Appointment System';
$activeNav = $activeNav ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> — AutoCare Workshop</title>
    <link rel="stylesheet" href="<?= $rootPath ?>assets/css/style.css">
</head>
<body>
<div class="page-wrapper">

<nav class="navbar">
    <div class="container">
        <div class="navbar-inner">
            <a class="navbar-brand" href="<?= $rootPath ?>index.php">
                 AutoCare
            </a>
            <ul class="navbar-links">
<?php if (client_logged_in()): ?>
                <li><a href="<?= $rootPath ?>index.php"      class="<?= $activeNav === 'home'         ? 'active' : '' ?>">Calendar</a></li>
                <li><a href="<?= $rootPath ?>book.php"        class="<?= $activeNav === 'book'         ? 'active' : '' ?>">Book</a></li>
                <li><a href="<?= $rootPath ?>my-appointments.php" class="<?= $activeNav === 'appointments'  ? 'active' : '' ?>">My Appointments</a></li>
                <li><a href="<?= $rootPath ?>my-cars.php"     class="<?= $activeNav === 'cars'         ? 'active' : '' ?>">My Cars</a></li>
                <li><a href="<?= $rootPath ?>logout.php" class="btn-nav">Logout</a></li>
<?php elseif (admin_logged_in()): ?>
                <li><a href="<?= $rootPath ?>admin/dashboard.php"     class="<?= $activeNav === 'admin-dash'  ? 'active' : '' ?>">Dashboard</a></li>
                <li><a href="<?= $rootPath ?>admin/appointments.php"  class="<?= $activeNav === 'admin-appts' ? 'active' : '' ?>">Appointments</a></li>
                <li><a href="<?= $rootPath ?>admin/logout.php" class="btn-nav">Logout</a></li>
<?php else: ?>
                <li><a href="<?= $rootPath ?>index.php"  class="<?= $activeNav === 'home'     ? 'active' : '' ?>">Home</a></li>
                <li><a href="<?= $rootPath ?>login.php"  class="<?= $activeNav === 'login'    ? 'active' : '' ?>">Login</a></li>
                <li><a href="<?= $rootPath ?>register.php" class="btn-nav">Register</a></li>
<?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<main>
<div class="container">
