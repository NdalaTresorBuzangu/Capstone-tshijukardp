<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($L) || !is_array($L)) {
    require_once __DIR__ . '/../config/lang.php';
}
$L = $L ?? [];
$loggedIn = !empty($_SESSION['user_id']);
$showLogout = !empty($showLogout);
$navBase = isset($baseUrl) ? $baseUrl : ((strpos($_SERVER['SCRIPT_NAME'] ?? '', 'views/') !== false) ? '../' : '');
?>
<nav class="navbar">
    <img src="<?php echo $navBase ? $navBase . 'assets/logo.jpg' : 'assets/logo.jpg'; ?>" alt="Tshijuka RDP" class="logo">
    <ul class="nav-links">
        <li><a href="<?php echo $navBase ? $navBase . 'index.php' : 'index.php'; ?>"><?php echo htmlspecialchars($L['home'] ?? 'Home'); ?></a></li>
        <li><a href="<?php echo $navBase; ?>index.php?controller=Page&action=about"><?php echo htmlspecialchars($L['about'] ?? 'About'); ?></a></li>
        <li><a href="<?php echo $navBase; ?>index.php?controller=Page&action=terms"><?php echo htmlspecialchars($L['terms_short'] ?? 'Terms'); ?></a></li>
        <li><a href="<?php echo $navBase; ?>index.php?controller=Page&action=privacy"><?php echo htmlspecialchars($L['privacy_short'] ?? 'Privacy'); ?></a></li>
        <?php if ($loggedIn && $showLogout): ?>
            <li><a href="<?php echo $navBase; ?>index.php?controller=Auth&action=logout" class="nav-logout"><?php echo htmlspecialchars($L['logout'] ?? 'Log out'); ?></a></li>
        <?php elseif (!$loggedIn): ?>
            <li><a href="<?php echo $navBase; ?>index.php?controller=Auth&action=login_form"><?php echo htmlspecialchars($L['login'] ?? 'Log in'); ?></a></li>
            <li><a href="<?php echo $navBase; ?>index.php?controller=Auth&action=signup_form"><?php echo htmlspecialchars($L['signup'] ?? 'Sign up'); ?></a></li>
        <?php endif; ?>
    </ul>
</nav>
