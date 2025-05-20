<?php
session_start();

// Clear all session data
$_SESSION = array();
session_destroy();

// Clear the remember me cookies
setcookie('remember_me', '', time() - 3600, "/");
setcookie('user_id', '', time() - 3600, "/");

// Redirect to home page (or login page)
header('Location: ../home.php');  // adjust this path if logout.php is in root use 'Location: home.php' or another path
exit();
?>
