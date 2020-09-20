<?php require APP_ROOT . "/views/includes/header.php" ?>
<link href="<?= $this->getFile("css", "components"); ?>" rel="stylesheet">
<link href="<?=$this->getFile('css', __FILE__);?>" rel="stylesheet">

<div class="center-container">
    <div class="shadow text-form-inner-container">
	<div class="text-form-header">
	    <?=$this->escapeForHTML($this->orderStorage["user_info"]["name_first"] ?? NULL)
	     . ' ' .
	       $this->escapeForHTML($this->orderStorage["user_info"]["name_last"] ?? NULL);?>
	</div>
	<div class="text-form-subheader">
	    <div class="center-container">
		<a class="no-text-decorate" href="/Dashboard/customers?uuid=<?=UUID::orderedBytesToArrangedString($this->orderStorage["user_uuid"] ?? NULL)?>">
		    Customer Page
		</a>
	    </div>
	</div>
    </div>
</div>
<div class="width-full max-width-768 shadow text-form-inner-container">
    <div class="order-info" id="<?=UUID::orderedBytesToArrangedString($this->orderStorage["uuid"]);?>">
	<?=$this->formatCartForHTML($this->orderStorage);?>
	<p>subtotal: $<?=$this->intToCurrency($this->orderStorage["cost"]["subtotal"])?></p>
	<p>fee: $<?=$this->intToCurrency($this->orderStorage["cost"]["fee"])?></p>
	<p>tax: $<?=$this->intToCurrency($this->orderStorage["cost"]["tax"])?></p>
	<p>total: $<span id="cost_total"><?=$this->intToCurrency($this->orderStorage["cost"]["total"])?></span></p>
    </div>
</div>



<div class="shadow text-form-inner-container">
    <div class="text-form-header">
	Payment(s)
    </div>
    <?php foreach($this->orderStorage["payments"] as $payment): ?>
	<div class="payment-container" id="payment-<?=$payment['id']?>">
	    <p>Method: <?=PAYMENT_ARRAY[$payment["method"]];?></p>
	    <p>Amount: $<span class="payment-amount"><?=$this->intToCurrency($payment["amount"])?></span></p>
	    <p>Refund total: $<span class="refund-total"><?=$this->intToCurrency($payment["refund_total"])?></span></p>
	    <?php if($payment["refund_total"] != $payment["amount"]): ?>
		<div class="center-container">
		    <input type="number" step="0.01" max="<?=$this->intToCurrency($payment["amount"] - $payment["refund_total"])?>" autocomplete="off">
		</div>
		<div class="wide-button-container">
		    <button type="button" class="refund-button svg-button wide-button" data-payment-id="<?=$payment["id"]?>">
			Refund
		    </button>
		</div>
	    <?php endif; ?>
	</div>
    <?php endforeach;?>
</div>

<input type="hidden" name="CSRFToken"  id="CSRFToken" value="<?= $this->sessionManager->getCSRFToken(); ?>">

<script src="<?=$this->getFile('js', __FILE__);?>" type="module"></script>
<?php require APP_ROOT . "/views/includes/footer.php" ?>
