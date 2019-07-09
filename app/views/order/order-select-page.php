<?php require APP_ROOT . "/views/includes/header.php" ?>

<link rel="stylesheet" type="text/css" href="<?= $this->getFile("css", __FILE__); ?>">
<!--<script async src="js/views/order.js"></script>-->

<div class="form-container">
    <form id="form-order-selection" method="post">
	<?php foreach($this->menu as $menuItem): ?>
	    <?php
	    $value = NULL;
	    // TODO: Might need to put this somewhere else.
	    if(!is_null($this->cart)){
		foreach($this->cart['order_line_items'] as $lineItem){
		    if($lineItem['name'] === $menuItem['name']) $value = $lineItem['quantity'];
		}
	    }

	    ?>
	    <label for="<?=$menuItem['name']?>"><?= $menuItem['name']; ?></label>
	    <input type="number" id="<?=$menuItem['name']?>" name="<?=$menuItem['name']?>" value="<?=$value?>">
	<?php endforeach ?>
	<input type="submit" value="submit">
    </form>
</div>

<?php require APP_ROOT . "/views/includes/footer.php" ?>
