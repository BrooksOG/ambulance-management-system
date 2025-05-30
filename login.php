<?php
// login.php
session_start();
require_once __DIR__ . '/includes/db_connect.php';

$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize & hash
    $username = $conn->real_escape_string(trim($_POST['username']));
    $password = md5(trim($_POST['password']));

    // Check credentials
    $sql    = "SELECT id, role FROM users 
               WHERE username='$username' AND password='$password' LIMIT 1";
    $result = $conn->query($sql);

    if ($result && $result->num_rows === 1) {
        $user = $result->fetch_assoc();
        // Set session
        $_SESSION['user_id']  = $user['id'];
        $_SESSION['username'] = $username;
        $_SESSION['role']     = $user['role'];
        // Redirect based on role
        switch ($user['role']) {
            case 'ADMIN':
              header('Location: admin/dashboard.php'); exit;
            case 'DISPATCHER':
                header('Location: dispatcher/dashboard.php'); exit;
            case 'PARAMEDIC':
                header('Location: paramedic/dashboard.php'); exit;
            case 'DRIVER':
                header('Location: driver/dashboard.php'); exit;
        }
    } else {
        $err = 'Invalid username or password.';
    }
}
?>
<?php include __DIR__ . '/includes/header.php'; ?>

<style>
  /* login.php page-specific CSS */
  .login-container {
    max-width: 400px;
    margin: 3rem auto;
    background: #fff;
    padding: 2rem;
    border-radius: 8px;
    box-shadow: 0 4px 16px rgba(0,0,0,0.1);
  }
  .login-container h2 {
    text-align: center;
    margin-bottom: 1.5rem;
    color: #d32f2f;
  }
  .login-container label {
    display: block;
    margin-top: 1rem;
    font-weight: 500;
  }
  .login-container input {
    width: 100%;
    padding: 0.75rem;
    margin-top: 0.5rem;
    border: 1px solid #ccc;
    border-radius: 4px;
  }
  .login-container .btn-primary {
    width: 100%;
    margin-top: 1.5rem;
  }
  .error {
    color: #b71c1c;
    text-align: center;
    margin-top: 1rem;
  }
</style>

<section class="login-container">
  <h2>Staff Login</h2>
  <?php if ($err): ?>
    <div class="error"><?= htmlspecialchars($err) ?></div>
  <?php endif; ?>
  <form method="POST" action="login.php">
    <label for="username">Username</label>
    <input type="text" id="username" name="username" required autofocus>

    <label for="password">Password</label>
    <input type="password" id="password" name="password" required>

    <button type="submit" class="btn btn-primary">Log In</button>
  </form>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
