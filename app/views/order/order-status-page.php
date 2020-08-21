<?php require APP_ROOT . "/views/includes/header.php" ?>

<link href="<?=$this->getFile('css', 'components');?>" rel="stylesheet">
<link href="<?= $this->getFile("css", __FILE__); ?>" rel="stylesheet">

<div class="center-container">
    <div class="page-wrapper">
<div class="center-container">
<div class="text-form-inner-container shadow order-status <?=($this->orderStorage["status"] == COMPLETE) ? NULL : "active"?>" id="order-status-container">
    <div class="main-order-status">
	<svg viewBox="0 0 100 100" height="16" width="16" id="svg-active-circle" class="blinking" <?=($this->orderStorage["status"] == COMPLETE) ? "hidden" : NULL?>>
	    <circle cx="50%" cy="50%" r="50" fill="red"></circle>
	</svg>
	<span class="status-flex-item">
	    <span class="font-weight-700"> Order Status</span>:
	    <span id="order-status-text"><?=STATUS_ARRAY[$this->orderStorage["status"]]?></span>
	</span>
    </div>
    <div class="order-status-description" id="order-prepared-notice" <?=$this->orderStorage["status"] == PREPARED ? NULL : "hidden"?>>
	Your order is waiting for <?=$this->orderStorage["order_type"] == DELIVERY ? "a delivery driver" : "you"?>.
    </div>
</div>
</div>

<h3 class="center-container thank-you-text">Thank you for placing an order <?=$this->orderStorage["order_type"] != IN_RESTAURANT ? "for" : NULL?> <?=ORDER_TYPE_ARRAY[$this->orderStorage["order_type"]]?>.</h3>

<div class="center-container date">
    <?=date("F d, Y g:i A", strtotime($this->orderStorage["date"]))?>
</div>

<div class="center-container">
<div class="address-payment-info">
    <?php if($this->orderStorage['order_type'] == DELIVERY): ?>
	<div class="delivery-address-container">
	    <div class="font-weight-700">Delivery address</div>
    
	    <?= $this->formatAddressForHTML($this->orderStorage['delivery_address']); ?>
	</div>
    <?php endif; ?>
    

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
</div>
</div>

<div class="order-info" data-uuid="<?=UUID::orderedBytesToArrangedString($this->orderStorage["uuid"])?>" data-status="<?=$this->orderStorage["status"]?>">
    <div class="center-container">
	<div class="order shadow text-form-inner-container">
	    <?=$this->formatCartForHTML($this->orderStorage)?>
	</div>
    </div>
</div>
    </div>
</div>

<input type="hidden" id="CSRFToken" name="CSRFToken" value="<?= $this->sessionManager->getCSRFToken(); ?>">

<script src="<?=$this->getFile('js', __FILE__);?>" type="module"></script>
<?php require APP_ROOT . "/views/includes/footer.php" ?>
