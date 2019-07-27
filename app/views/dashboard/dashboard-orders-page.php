<?php require APP_ROOT . "/views/includes/header.php" ?>
<link href="<?=$this->getFile('css', __FILE__);?>" rel="stylesheet">
<script src="<?=$this->getFile('js', __FILE__);?>"></script>
<header>Tony's Taco House</header>
<a href="/Dashboard">Dashboard</a>

<form>
    <?php foreach($this->orders as $order): ?>
	<div>
	    <ul>
		<?php foreach($order['order_line_items'] as $lineItem): ?>
		    <li><?= $lineItem['quantity'] . ' ' . $lineItem['name']; ?></li>
		<?php endforeach; ?>
	    </ul>
	    <span><?= $order['status']; ?><input type="checkbox" name="status[]" value="<?=$order['id'];?>" id="<?=$order['id'];?>"></span>
	</div>
    <?php endforeach; ?>
    <input type="hidden" name="CSRFToken" value="<?= $this->sessionManager->getCSRFToken(); ?>">
    <input type="submit" value="update">
</form>

<?php require APP_ROOT . "/views/includes/footer.php" ?>
