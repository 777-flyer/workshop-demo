<?php
/**
 * setup_admin.php
 * 
 */

require_once __DIR__ . '/config/db.php';

// Admin credentials 
$ADMIN_USERNAME = 'admin';
$ADMIN_EMAIL = '';
$ADMIN_PASSWORD = ''; 
// 

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

 $username = trim($_POST['username'] ?? $ADMIN_USERNAME);
 $email = trim($_POST['email'] ?? $ADMIN_EMAIL);
 $password = $_POST['password'] ?? $ADMIN_PASSWORD;

 if (strlen($password) < 8) {
 $error = 'Password must be at least 8 characters.';
 } else {
 
 $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

 $db = get_db();
 
 $db->prepare('DELETE FROM admins WHERE username = ?')->execute([$username]);
 $stmt = $db->prepare(
 'INSERT INTO admins (username, email, password) VALUES (?, ?, ?)'
 );
 $stmt->execute([$username, $email, $hash]);

 $success = "Admin account created! Username: <strong>{$username}</strong> — Email: <strong>{$email}</strong>";
 }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
 <meta charset="UTF-8">
 <meta name="viewport" content="width=device-width, initial-scale=1.0">
 <title>Admin Setup</title>
 <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="auth-page">
 <div class="auth-box">
 <div class="auth-logo">
 <h1> Admin Setup</h1>
 <p>Creates the admin account with a proper password hash.<br>
 <strong style="color:inherit;">Delete this file after use!</strong>
 </p>
 </div>

 <?php if ($success): ?>
 <div class="flash flash-success"><?= $success ?></div>
 <?php endif; ?>

 <?php if ($error): ?>
 <div class="flash flash-error"><?= htmlspecialchars($error) ?></div>
 <?php endif; ?>

 <?php if (!$success): ?>
 <div class="card">
 <form method="POST">
 <div class="form-group">
 <label class="form-label">Username</label>
 <input type="text" name="username" class="form-control"
 value="admin" required>
 </div>
 <div class="form-group">
 <label class="form-label">Email</label>
 <input type="email" name="email" class="form-control"
 value="admin@workshop.com" required>
 </div>
 <div class="form-group">
 <label class="form-label">Password</label>
 <input type="password" name="password" class="form-control"
 placeholder="Min. 8 characters" required>
 <span class="form-text">This is what you'll use to log into the admin panel.</span>
 </div>
 <button type="submit" class="btn btn-primary btn-block">
 Create Admin Account
 </button>
 </form>
 </div>
 <?php endif; ?>
 </div>
</div>
</body>
</html>
