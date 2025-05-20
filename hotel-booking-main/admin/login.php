<?php
session_start();
include '../includes/config.php';

$error = "";

// Generate new math CAPTCHA if needed
if (!isset($_SESSION['captcha_answer']) || $_SERVER['REQUEST_METHOD'] !== 'POST') {
  $num1 = rand(1, 10);
  $num2 = rand(1, 10);
  $_SESSION['captcha_question'] = "$num1 + $num2";
  $_SESSION['captcha_answer'] = $num1 + $num2;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $email = $_POST['email'];
  $password = $_POST['password'];
  $captcha_input = (int) $_POST['captcha'];

  // Check CAPTCHA
  if ($captcha_input !== $_SESSION['captcha_answer']) {
    $error = "Incorrect captcha answer. Please try again.";
  } else {
    // Basic SQL injection prevention (ideally use prepared statements!)
    $email = $conn->real_escape_string($email);
    $password = $conn->real_escape_string($password);

    $query = "SELECT * FROM admins WHERE email = '$email' AND password = '$password'";
    $result = $conn->query($query);

    if ($result->num_rows > 0) {
      $admin = $result->fetch_assoc();

      $_SESSION['admin_id'] = $admin['admin_id'];
      $_SESSION['admin_email'] = $admin['email'];
      $_SESSION['admin_name'] = $admin['admin_name'];

      unset($_SESSION['captcha_answer']); // Clear CAPTCHA on success

      header('Location: index.php');
      exit();
    } else {
      $error = "Invalid email or password.";
    }
  }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <link rel="stylesheet" href="../css/adminlogin.css" />
  <title>Admin Login</title>
</head>
<body>
  <div class="login-container">
    <h2>Admin Login</h2>
    <?php if (!empty($error)) echo "<p class='error'>$error</p>"; ?>
    <form action="login.php" method="POST">
      <input type="email" name="email" placeholder="Email" required />
      <input type="password" name="password" placeholder="Password" required />

      <label for="captcha">What is <?= $_SESSION['captcha_question'] ?>?</label>
      <input type="number" name="captcha" id="captcha" placeholder="Enter your answer" required />

      <button type="submit">Login</button>
    </form>
  </div>
</body>
</html>

