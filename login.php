<?php
session_start();

// Redirect if already logged in
if (isset($_SESSION['admin_id'])) {
    header('Location: library.php');
    exit;
}

require_once 'db_connect.php';

$error    = '';
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Please enter both username and password.';
    } else {
        $stmt = $conn->prepare('SELECT id, username, password_hash, name FROM admins WHERE username = ?');
        if ($stmt) {
            $stmt->bind_param('s', $username);
            $stmt->execute();
            $admin = $stmt->get_result()->fetch_assoc();

            if ($admin && password_verify($password, $admin['password_hash'])) {
                $_SESSION['admin_id']       = $admin['id'];
                $_SESSION['admin_name']     = $admin['name'];
                $_SESSION['admin_username'] = $admin['username'];
                header('Location: library.php');
                exit;
            } else {
                $error = 'Invalid username or password.';
            }
        } else {
            $error = 'System error. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In &mdash; Library of Alexandria</title>
    <link rel="stylesheet" href="heisenberg.css">
</head>
<body class="login-page">
  <div class="login-container">
    <div class="login-card">
      <div class="login-header">
        <img src="pic.jpg" alt="Library logo">
        <h1>Library of Alexandria</h1>
        <p>Management System</p>
      </div>

      <?php if ($error !== ''): ?>
        <div class="flash flash-error"><?php echo htmlspecialchars($error); ?></div>
      <?php endif; ?>

      <form action="login.php" method="post" autocomplete="on">
        <div class="field">
          <label for="username">Username</label>
          <input id="username" name="username" type="text" placeholder="Enter username" 
                 value="<?php echo htmlspecialchars($username); ?>" required autofocus>
        </div>
        <div class="field">
          <label for="password">Password</label>
          <input id="password" name="password" type="password" placeholder="Enter password" required>
        </div>
        <button class="btn primary login-btn" type="submit">Sign In</button>
      </form>
    </div>
  </div>
</body>
</html>
