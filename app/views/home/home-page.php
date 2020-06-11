<?php require APP_ROOT . "/views/includes/header.php" ?>
<link href="<?= $this->getFile('css', __FILE__); ?>" rel="stylesheet">
<?php if(!is_null($this->activeOrderStatus)): ?>
    <div class="order_alert">Your order is being <?=STATUS_ARRAY[$this->activeOrderStatus];?></div>
<?php endif; ?>

<?php require APP_ROOT . "/views/includes/footer.php" ?>
