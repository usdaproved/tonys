<?php require APP_ROOT . "/views/includes/header.php" ?>
<link rel="stylesheet" type="text/css" href="<?= $this->getFile("css", __FILE__); ?>">
<h3>Order has been submitted.</h3>
<?php if($this->orderStorage['order_type'] == DELIVERY): ?>
<strong>Delivery address</strong>:
<br>
<?= $this->formatAddressForHTML($this->orderStorage['delivery_address']); ?>
<br>
<?php endif; ?>
<strong>Order</strong>:
<?=$this->formatOrderForHTML($this->orderStorage)?>
<p><strong>Total</strong>: <?= "$" . $this->intToCurrency($this->orderStorage['cost']['total']); ?></p>


<?php require APP_ROOT . "/views/includes/footer.php" ?>
