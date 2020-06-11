<?php require APP_ROOT . "/views/includes/header.php" ?>
<link href="<?=$this->getFile('css', 'components');?>" rel="stylesheet">
<link href="<?=$this->getFile('css', __FILE__);?>" rel="stylesheet">
<?php if(!empty($orders)): ?>
    <div class="orders-container">
	<?php foreach($orders as $order): ?>
	    <div class="order-container no-pointer">
		<?=$order["date"]?>
		<?='$' . $this->intToCurrency($order["cost"]["total"])?>
		<?=$this->formatOrderForHTML($order)?>
	    </div>
	<?php endforeach; ?>
    </div>
<?php else: ?>
    <h3>No orders to show yet.</h3>
<?php endif; ?>

<script src="<?=$this->getFile('js', __FILE__);?>" type="module"></script>
<?php require APP_ROOT . "/views/includes/footer.php" ?>
