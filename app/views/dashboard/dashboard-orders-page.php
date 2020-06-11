<?php require APP_ROOT . "/views/includes/header.php" ?>
<link href="<?=$this->getFile('css', __FILE__);?>" rel="stylesheet">
<div id="user_info">
    <a href="/Dashboard/customers?uuid=<?=UUID::orderedBytesToArrangedString($this->orderStorage["user_uuid"] ?? NULL)?>">
	<?=$this->escapeForHTML($this->orderStorage["user_info"]["name_first"] ?? NULL)
	 . ' ' .
	   $this->escapeForHTML($this->orderStorage["user_info"]["name_last"] ?? NULL);?>
    </a>
</div>
<div class="order-container" id="<?=UUID::orderedBytesToArrangedString($this->orderStorage["uuid"]);?>">
    <?=$this->formatOrderForHTML($this->orderStorage);?>
</div>

<p>subtotal: $<?=$this->intToCurrency($this->orderStorage["cost"]["subtotal"])?></p>
<p>fee: $<?=$this->intToCurrency($this->orderStorage["cost"]["fee"])?></p>
<p>tax: $<?=$this->intToCurrency($this->orderStorage["cost"]["tax"])?></p>
<p>total: $<span id="cost_total"><?=$this->intToCurrency($this->orderStorage["cost"]["total"])?></span></p>

<div class="payments-container">
    <h3>Payment(s)</h3>
    <?php foreach($this->orderStorage["payments"] as $payment): ?>
	<div class="payment-container" id="payment-<?=$payment['id']?>">
	    <p>Method: <?=PAYMENT_ARRAY[$payment["method"]];?></p>
	    <p>Amount: $<span class="payment-amount"><?=$this->intToCurrency($payment["amount"])?></span></p>
	    <p>Refund total: $<span class="refund-total"><?=$this->intToCurrency($payment["refund_total"])?></span></p>
	    <?php if($payment["refund_total"] != $payment["amount"]): ?>
		<input type="number" step="0.01" max="<?=$this->intToCurrency($payment["amount"] - $payment["refund_total"])?>" autocomplete="off">
		<input type="button" class="refund-button" value="Refund" data-payment-id="<?=$payment["id"]?>">
	    <?php endif; ?>
	</div>
    <?php endforeach;?>
</div>

<input type="hidden" name="CSRFToken"  id="CSRFToken" value="<?= $this->sessionManager->getCSRFToken(); ?>">

<script src="<?=$this->getFile('js', __FILE__);?>" type="module"></script>
<?php require APP_ROOT . "/views/includes/footer.php" ?>
