<?php
session_start();
include 'includes/config.php';

// Generate math CAPTCHA if not POST or captcha answer not set
if (!isset($_SESSION['captcha_answer']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
  $num1 = rand(1, 10);
  $num2 = rand(1, 10);
  $_SESSION['captcha_question'] = "$num1 + $num2";
  $_SESSION['captcha_answer'] = $num1 + $num2;
}

$errorMessage = "";

// Check for remember me cookie and auto-login
if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_me'], $_COOKIE['user_id'])) {
    if ($_COOKIE['remember_me'] === 'true') {
        $_SESSION['user_id'] = (int)$_COOKIE['user_id'];
        header("Location: home.php");
        exit();
    }
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $email = $_POST['userEmail'];
  $password = $_POST['userPassword'];
  $captchaInput = (int)$_POST['captcha'];
  $rememberMe = isset($_POST['rememberMe']);

  if ($captchaInput !== $_SESSION['captcha_answer']) {
    $errorMessage = "⚠ Incorrect CAPTCHA answer.";
  } else {
    // Protect against SQL injection
    $email = $conn->real_escape_string($email);
    $password = $conn->real_escape_string($password);

    $query = "SELECT * FROM users WHERE email = '$email' AND password = '$password'";
    $result = $conn->query($query);

    if ($result->num_rows > 0) {
      $user = $result->fetch_assoc();
      $_SESSION['user_id'] = $user['user_id'];
      unset($_SESSION['captcha_answer']); // clear captcha after success

      if ($rememberMe) {
        setcookie('remember_me', 'true', time() + (86400 * 30), "/"); // 30 days
        setcookie('user_id', $user['user_id'], time() + (86400 * 30), "/");
      } else {
        // Clear cookies if they exist and rememberMe not checked
        setcookie('remember_me', '', time() - 3600, "/");
        setcookie('user_id', '', time() - 3600, "/");
      }

      header('Location: home.php');
      exit();
    } else {
      $errorMessage = "⚠ Invalid email or password.";
    }
  }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="stylesheet" href="css/registerstyle.css" />
  <link rel="icon" type="image/x-icon" href="images/logo/hotellogo.png" />
  <title>Login</title>
</head>
<body>
  <main class="container">
    <form action="login.php" method="POST" class="modern-form">
      <div class="form-title">Login</div>

      <div class="form-body">
        <?php if (!empty($errorMessage)) echo "<div class='invalid'>$errorMessage</div>"; ?>

        <div class="input-group">
          <div class="input-wrapper">
            <i class="fa-solid fa-envelope input-icon"></i>
            <input required placeholder="Email" class="form-input" name="userEmail" type="email" />
          </div>
        </div>

        <div class="input-group">
          <div class="input-wrapper">
            <i class="fa-solid fa-key input-icon"></i>
            <input required placeholder="Password" class="form-input" name="userPassword" type="password" />
          </div>
        </div>

        <div class="input-group">
          <div class="input-wrapper">
            <label for="captcha"><strong>What is <?= $_SESSION['captcha_question'] ?>?</strong></label>
            <input required placeholder="Answer" class="form-input" name="captcha" type="number" />
          </div>
        </div>

        <div class="input-group">
          <label>
            <input type="checkbox" name="rememberMe" />
            Remember Me
          </label>
        </div>
      </div>

      <button id="loginButton" class="submit-button" type="submit">
        <span class="button-text">Login</span>
        <div class="button-glow"></div>
      </button>

      <div class="form-footer">
        <a class="login-link" href="signup.php">
          Don't have an account? <span>Sign up</span>
        </a>
      </div>
    </form>
  </main>

  <script src="https://kit.fontawesome.com/2e5e758ab7.js" crossorigin="anonymous"></script>
</body>
</html>
