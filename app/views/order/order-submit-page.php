<?php require APP_ROOT . "/views/includes/header.php" ?>

<link href="<?=$this->getFile('css', 'components');?>" rel="stylesheet">
<link href="<?= $this->getFile("css", __FILE__); ?>" rel="stylesheet">

<?php $this->printOneTimeMessages(USER_ALERT); ?>
<article>
    <h3>Personal information</h3>
    <strong>Name</strong>:
    <br>
    <span id="name_first">
	<?= $this->escapeForHTML($this->user['name_first'] ?? NULL) ?>
    </span>
    <span id="name_last">
	<?= $this->escapeForHTML($this->user['name_last'] ?? NULL); ?>
    </span>
    <br>
    <strong>Email</strong>:
    <br>
    <?= $this->escapeForHTML($this->user['email'] ?? NULL); ?>
    <br>
    <strong>Phone number</strong>:
    <br>
    <?= $this->escapeForHTML($this->user['phone_number'] ?? NULL); ?>
    <br>
    <?php if($this->orderStorage["order_type"] == DELIVERY): ?>
	<strong>Address</strong>:
	<br>
	<?= $this->formatAddressForHTML($this->user['address'] ?? NULL); ?>
	<input type="button" id="change-address" value="Change">
	<div id="address-select-container" class="orders-container" hidden>
	    <?php foreach($this->user["other_addresses"] as $address): ?>
		<div class="order-container" id="address-<?=$address['id']?>">
		    <?=$this->formatAddressForHTML($address)?>
		</div>
	    <?php endforeach; ?>
	</div>
    <?php endif; ?>
    <h3>Cart</h3>
    <p>
	<ul>
	    <?php foreach($this->orderStorage['line_items'] as $lineItem): ?>
		<li><?= $this->escapeForHTML($lineItem['name']) . ' ' . $lineItem['quantity']; ?></li>
	    <?php endforeach; ?>
	</ul>
    </p>
    <p><strong>Subtotal:</strong> $<?=$this->intToCurrency($this->orderStorage['cost']['subtotal'])?></p>
    <?php if($this->orderStorage['cost']['fee'] > 0): ?>
	<p><strong>Fees:</strong> $<?=$this->intToCurrency($this->orderStorage['cost']['fee'])?></p>
    <?php endif; ?>
    <p><strong>Tax:</strong> $<?=$this->intToCurrency($this->orderStorage['cost']['tax'])?></p>
    <p><strong>Total:</strong> $<?=$this->intToCurrency($this->orderStorage['cost']['total'])?></p>
    
    <div id="stripe-card-element">
    </div>

    <div id="stripe-card-errors" role="alert">
    </div>

    <input type="hidden" id="CSRFToken" name="CSRFToken" value="<?= $this->sessionManager->getCSRFToken(); ?>">
    <button id="stripe-payment-submit" data-secret="<?=$this->user['stripe_client_secret']?>" data-orderid="<?=$this->orderStorage['id']?>">Pay</button>
    
    <div id="paypal-button-container"></div>

</article>
<script src="https://www.paypal.com/sdk/js?client-id=<?=PAYPAL_PUBLIC_KEY?>&disable-funding=credit,card"></script>
<script src="https://js.stripe.com/v3/"></script>
<script src="<?=$this->getFile('js', __FILE__);?>" type="module"></script>
<?php require APP_ROOT . "/views/includes/footer.php" ?>
