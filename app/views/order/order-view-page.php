<?php require APP_ROOT . "/views/includes/header.php" ?>
<link href="<?=$this->getFile('css', __FILE__);?>" rel="stylesheet">
<header>Tony's Taco House</header>
<a href="/">Home</a>

<h3>All orders</h3>
<?php foreach($this->orderStorage as $order): ?>
    <div>
	<p><strong>Date</strong>: <?=$order["date"];?></p>
	<ul>
	    <?php foreach($order['order_line_items'] as $lineItem): ?>
		<li><?= $lineItem['quantity'] . ' ' . $lineItem['name']; ?></li>
	    <?php endforeach; ?>
	</ul>
    </div>
<?php endforeach; ?>

<?php require APP_ROOT . "/views/includes/footer.php" ?>
