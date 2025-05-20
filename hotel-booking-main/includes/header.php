<header>
<?php
$cookieAccepted = isset($_COOKIE['cookie_consent']) && $_COOKIE['cookie_consent'] === '1';
?>

<?php if (!$cookieAccepted): ?>
<style>
  #cookie-consent-banner {
    position: fixed;
    bottom: 0;
    left: 0; right: 0;
    background: #222;
    color: #eee;
    padding: 1rem;
    text-align: center;
    z-index: 10000;
    font-size: 14px;
  }
  #cookie-consent-banner button {
    margin-left: 10px;
    background: #4CAF50;
    border: none;
    color: white;
    padding: 6px 12px;
    cursor: pointer;
  }
</style>

<div id="cookie-consent-banner">
  We use cookies to improve your experience. 
  <a href="cookies.php" style="color: #8ecae6; text-decoration: underline;">Learn more</a>
  <button id="accept-cookies-btn">Accept</button>
</div>

<script>
  document.getElementById('accept-cookies-btn').addEventListener('click', function() {
    document.cookie = "cookie_consent=1; path=/; max-age=" + 60*60*24*365 + ";"; // 1 year
    document.getElementById('cookie-consent-banner').style.display = 'none';
  });
</script>
<?php endif; ?>

    <h1 class="logo">CCT Hotel</h1>
    <button class="hamburger">
        <i class="fa-solid fa-bars"></i>
    </button>
    
    <nav>
        <ul class="nav-links">
            <li><a href="home.php">Home</a></li>
            <li><a href="rooms.php">Rooms</a></li>
            <li><a href="services.php">Services</a></li>
            <li><a href="contact.php">Contact</a></li>
            <li><a href="about.php">About</a></li>
        </ul>

        <div class='mobile-cta'>
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href='account.php'><button><i class='fa-solid fa-user input-icon'></i> Account</button></a>
            <?php else: ?>
                <a href='login.php'><button><i class='fa-solid fa-user input-icon'></i> Login</button></a>
            <?php endif; ?>
        </div>
    </nav>

    <a class='cta' href='<?php echo isset($_SESSION['user_id']) ? "account.php" : "login.php"; ?>'>
        <button><i class='fa-solid fa-user input-icon'></i> 
            <?php echo isset($_SESSION['user_id']) ? "Account" : "Login"; ?>
        </button>
    </a>
</header>
