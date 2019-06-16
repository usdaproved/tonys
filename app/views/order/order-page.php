<?php require APP_ROOT . "/views/includes/header.php" ?>

<link rel="stylesheet" type="text/css" href="css/views/order/order-page.css">
<script async src="js/views/order.js"></script>

<div class="grid-container">
    <form method="post" class="flex-container">
	<?php foreach($this->menu as $menuItem): ?>
	    <div class="flex-item"><?= $menuItem["name"]; ?></div>
	<?php endforeach ?>
    </form>
</div>

<?php require APP_ROOT . "/views/includes/footer.php" ?>
