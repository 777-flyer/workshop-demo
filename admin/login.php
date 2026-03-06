<?php
/**
 * admin/login.php
 *
 * Admin authentication.
 * POST: username, password
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

start_session();

if (admin_logged_in()) {
 redirect('dashboard.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
 $username = trim($_POST['username'] ?? '');
 $password = $_POST['password'] ?? '';

 if ($username === '' || $password === '') {
 $error = 'Please fill in both fields.';
 } else {
 $admin = authenticate_admin($username, $password);
 if ($admin) {
 login_admin($admin);
 redirect('dashboard.php', 'Welcome, ' . $admin['username'] . '!', 'success');
 } else {
 $error = 'Invalid username or password.';
 }
 }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
 <meta charset="UTF-8">
 <meta name="viewport" content="width=device-width, initial-scale=1.0">
 <title>Admin Login — AutoCare Workshop</title>
 <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<div class="auth-page">
 <div class="auth-box">
 <div class="auth-logo">
 <h1> Admin Panel</h1>
 <p>AutoCare Workshop Management</p>
 </div>

 <?= render_flash() ?>
 <?php if ($error): ?>
 <div class="flash flash-error"><?= e($error) ?></div>
 <?php endif; ?>

 <div class="card">
 <form method="POST" action="login.php" data-validate novalidate>

 <div class="form-group">
 <label class="form-label" for="username">Username</label>
 <input
 type="text"
 id="username"
 name="username"
 class="form-control"
 required
 value="<?= e($_POST['username'] ?? '') ?>"
 placeholder="admin"
 autocomplete="username"
 autofocus
 >
 </div>

 <div class="form-group">
 <label class="form-label" for="password">Password</label>
 <input
 type="password"
 id="password"
 name="password"
 class="form-control"
 required
 placeholder="Password"
 autocomplete="current-password"
 >
 </div>

 <button type="submit" class="btn btn-primary btn-block btn-lg">
 Sign In
 </button>

 </form>
 </div>


 <p class="text-center mt-12" style="font-size:.85rem;">
 <a href="../login.php">← Client login</a>
 </p>
 </div>
</div>
<script src="../assets/js/main.js"></script>
</body>
</html>
