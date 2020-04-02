<?php require APP_ROOT . "/views/includes/header.php" ?>
<link rel="stylesheet" type="text/css" href="<?= $this->getFile("css", __FILE__); ?>">
<header>Tony's Taco House</header>
<a href="/">Home</a>

<h3>Order has been submitted.</h3>
<?php if($this->orderStorage['order_type'] == DELIVERY): ?>
<strong>Delivery address</strong>:
<br>
<?= $this->formatAddressForHTML($this->user['address']); ?>
<br>
<?php endif; ?>
<strong>Order</strong>:
<ul>
    <?php foreach($this->orderStorage['line_items'] as $lineItem): ?>
	<li><?= $lineItem['quantity'] . ' ' . $lineItem['name']; ?></li>
    <?php endforeach; ?>
</ul>
<p><strong>Total</strong>: <?= "$" . $this->intToCurrency($this->orderStorage['cost']['total']); ?></p>


<?php require APP_ROOT . "/views/includes/footer.php" ?>
