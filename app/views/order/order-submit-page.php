<?php require APP_ROOT . "/views/includes/header.php" ?>

<link href="<?=$this->getFile('css', 'components');?>" rel="stylesheet">
<link href="<?= $this->getFile("css", __FILE__); ?>" rel="stylesheet">

<?php $this->printOneTimeMessages(USER_ALERT); ?>
<article class="split-layout">
    <div class="split-left-section">
	<section class="width-full max-width-768 shadow text-form-inner-container">
	    <h3 class="order-header">
		Your Order For <?=$this->orderStorage["order_type"] == DELIVERY ? "Delivery" : "Pickup" ?>
	    </h3>
	    <div class="order-info">
		<?=$this->formatCartForHTML($this->orderStorage)?>
	    </div>
	</section>
    </div>
    <div class="split-right-section">
	<div class="max-width-768 width-full background-gray">
	    <div class="personal-info">
		<div class="personal-line personal-name">
		    <span id="name_first">
			<?= $this->escapeForHTML($this->user['name_first'] ?? NULL) ?>
		    </span>
		    <span id="name_last">
			<?= $this->escapeForHTML($this->user['name_last'] ?? NULL); ?>
		    </span>
		</div>
		<div class="personal-line">
		    <?= $this->escapeForHTML($this->user['email'] ?? NULL); ?>
		</div>
		<div class="personal-line">
		    <?= $this->escapeForHTML($this->user['phone_number'] ?? NULL); ?>
		</div>
		<?php if($this->orderStorage["order_type"] == DELIVERY): ?>
		    <div class="delivery-address-container">
			<div class="center-container">
			    <?= $this->formatAddressForHTML($this->user['delivery_address'] ?? NULL); ?>
			    <input type="button" id="change-address-button" class="svg-button" value="Change">
			</div>
			<div class="center-container address-select-container" id="address-select-container" hidden>
			    <?php foreach($this->user["other_addresses"] as $address): ?>
				<button type="button" class="svg-button selectable-address" id="<?=UUID::orderedBytesToArrangedString($address['uuid'])?>">
				    <?=$this->formatAddressForHTML($address)?>
				</button>
			    <?php endforeach; ?>
			</div>
		    </div>
		<?php endif; ?>
	    </div>
	    <div class="payment-info-center">
		<div class="payment-info-container">
		    <div class="payment-info-line">
			<span><span class="payment-descriptor">Subtotal</span>:</span> <span class="payment-amount">$<?=$this->intToCurrency($this->orderStorage['cost']['subtotal'])?></span>
		    </div>
		    <?php if($this->orderStorage['cost']['fee'] > 0): ?>
			<div class="payment-info-line">
			    <span><span class="payment-descriptor">Fees</span>:</span> <span class="payment-amount">$<?=$this->intToCurrency($this->orderStorage['cost']['fee'])?></span>
			</div>
		    <?php endif; ?>
		    <div class="payment-info-line">
			<span><span class="payment-descriptor">Tax</span>:</span> <span class="payment-amount">$<?=$this->intToCurrency($this->orderStorage['cost']['tax'])?></span>
		    </div>
		    <div class="payment-info-line payment-total">
			<span><span class="payment-descriptor">Total</span>:</span> <span class="payment-amount">$<?=$this->intToCurrency($this->orderStorage['cost']['total'])?></span>
		    </div>
		</div>
	    </div>

	    <div class="stripe-payment-container">
		<div id="stripe-card-element">
		</div>
		<div id="stripe-card-errors" role="alert">
		</div>
		<button type="button" class="svg-button stripe-submit-button" id="stripe-payment-submit" data-secret="<?=$this->user['stripe_client_secret']?>" data-orderuuid="<?=UUID::orderedBytesToArrangedString($this->orderStorage['uuid'])?>">
		    <span id="stripe-submit-text">Pay</span>
		    <div id="stripe-submit-loading" class="spinner" hidden>
			<div class="bounce1"></div>
			<div class="bounce2"></div>
			<div class="bounce3"></div>
		    </div>
		    <div id="stripe-submit-success" hidden>
			<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="white" width="18px" height="18px">
			    <path d="M0 0h24v24H0z" fill="none"/>
			    <path d="M9 16.2L4.8 12l-1.4 1.4L9 19 21 7l-1.4-1.4L9 16.2z"/>
			</svg>
		    </div>
		</button>
	    </div>

	    <!-- TODO(Trystan): Remove this when we go live. -->
	    <div class="demo-info">
		<h3>Demo only</h3>
		<p>Demo card number: 4242 4242 4242 4242</p>
	    </div>
	</div>
    </div>
</article>

<input type="hidden" id="CSRFToken" name="CSRFToken" value="<?= $this->sessionManager->getCSRFToken(); ?>">

<script src="https://js.stripe.com/v3/"></script>
<script src="<?=$this->getFile('js', __FILE__);?>" type="module"></script>
<?php require APP_ROOT . "/views/includes/footer.php" ?>
