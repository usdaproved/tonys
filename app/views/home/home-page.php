<?php require APP_ROOT . "/views/includes/header.php" ?>
<link href="<?= $this->getFile('css', __FILE__); ?>" rel="stylesheet">
<header>Tony's Taco House</header>
<?php if(!is_null($this->user)): ?>
    <h3>Welcome, <?= $this->user['email']; ?>.</h3>
<?php endif; ?>
<?php if(is_null($this->user)):?>
    <a href="/Register">Register</a>
    <a href="/Login">Log in</a>
<?php else: ?>
    <a href="/Logout">Log out</a>
<?php endif; ?>
<a href="/Order">Order</a>

<?php require APP_ROOT . "/views/includes/footer.php" ?>
