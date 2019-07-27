<?php require APP_ROOT . "/views/includes/header.php" ?>
<link href="<?= $this->getFile('css', __FILE__); ?>" rel="stylesheet">
<header>Tony's Taco House</header>
<?php if(isset($this->user['name_first'])): ?>
    <h3>Welcome, <?= $this->escapeForHTML($this->user['name_first']); ?>.</h3>
<?php endif; ?>
<?php if($this->isLoggedIn):?>
    <a href="/Logout">Log out</a>
<?php else: ?>
    <a href="/Register">Register</a>
    <a href="/Login">Log in</a>
<?php endif; ?>
<a href="/Order">Order</a>

<?php require APP_ROOT . "/views/includes/footer.php" ?>
