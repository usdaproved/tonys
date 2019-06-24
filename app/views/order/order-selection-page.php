<?php require APP_ROOT . "/views/includes/header.php" ?>

<link rel="stylesheet" type="text/css" href="css/views/order/order-selection-page.css">
<!--<script async src="js/views/order.js"></script>-->

<div class="form-container">
    <form id="form-order-selection" method="post">
	<?php foreach($this->menu as $menuItem): ?>
	    <label for="<?=$menuItem['name']?>"><?= $menuItem['name']; ?></label>
	    <input type="number" id="<?=$menuItem['name']?>" name="<?=$menuItem['name']?>">
	<?php endforeach ?>
	<input type="submit" value="submit">
    </form>
</div>

<?php require APP_ROOT . "/views/includes/footer.php" ?>
