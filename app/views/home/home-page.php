<?php require APP_ROOT . "/views/includes/header.php" ?>
<link href="<?= $this->getFile('css', __FILE__); ?>" rel="stylesheet">
<header>Tony's Taco House</header>
<?php if(!is_null($this->activeOrderStatus)): ?>
    <div class="order_alert">Your order is being <?=STATUS_ARRAY[$this->activeOrderStatus];?></div>
<?php endif; ?>
<?php if(isset($this->user['name_first'])): ?>
    <h3>Welcome, <?= $this->escapeForHTML($this->user['name_first']); ?>.</h3>
<?php endif; ?>
<?php if($this->isLoggedIn):?>
    <a href="/logout">Log out</a>
<?php else: ?>
    <a href="/register">Register</a>
    <a href="/login">Log in</a>
<?php endif; ?>
<a href="/Order">Order</a>

<?php require APP_ROOT . "/views/includes/footer.php" ?>
