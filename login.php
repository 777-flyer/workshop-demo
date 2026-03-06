<?php
/**
 * login.php
 *
 * Client authentication.
 * POST: email, password
 */

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

start_session();

if (client_logged_in()) {
 redirect('dashboard.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
 $email = trim($_POST['email'] ?? '');
 $password = $_POST['password'] ?? '';

 if ($email === '' || $password === '') {
 $error = 'Please fill in both fields.';
 } else {
 $client = authenticate_client($email, $password);
 if ($client) {
 login_client($client);
 // Redirect to originally requested page or dashboard
 $next = filter_var($_GET['next'] ?? '', FILTER_SANITIZE_URL);
 $next = ($next && strpos($next, 'http') !== 0) ? $next : 'dashboard.php';
 redirect($next, 'Welcome back, ' . $client['name'] . '!', 'success');
 } else {
 $error = 'Invalid email or password.';
 }
 }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
 <meta charset="UTF-8">
 <meta name="viewport" content="width=device-width, initial-scale=1.0">
 <title>Sign In — AutoCare Workshop</title>
 <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="auth-page">
 <div class="auth-box">
 <div class="auth-logo">
 <h1> AutoCare</h1>
 <p>Sign in to your account</p>
 </div>

 <?= render_flash() ?>
 <?php if ($error): ?>
 <div class="flash flash-error"><?= e($error) ?></div>
 <?php endif; ?>

 <div class="card">
 <form method="POST" action="login.php" data-validate novalidate>

 <div class="form-group">
 <label class="form-label" for="email">Email Address</label>
 <input
 type="email"
 id="email"
 name="email"
 class="form-control"
 required
 data-label="Email"
 value="<?= e($_POST['email'] ?? '') ?>"
 placeholder="you@example.com"
 autocomplete="email"
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
 data-label="Password"
 placeholder="Your password"
 autocomplete="current-password"
 >
 </div>

 <button type="submit" class="btn btn-primary btn-block btn-lg">
 Sign In
 </button>

 </form>
 </div>

 <p class="text-center mt-12 text-muted" style="font-size:.9rem;">
 Don't have an account? <a href="register.php">Register</a>
 </p>
 <p class="text-center mt-12 text-muted" style="font-size:.85rem;">
 Admin? <a href="admin/login.php">Admin login →</a>
 </p>
 </div>
</div>
<script src="assets/js/main.js"></script>
</body>
</html>
