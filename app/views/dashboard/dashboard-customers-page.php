<?php require APP_ROOT . "/views/includes/header.php" ?>
<link href="<?=$this->getFile('css', 'components');?>" rel="stylesheet">
<link href="<?=$this->getFile('css', __FILE__);?>" rel="stylesheet">

<div class="center-container">
    <div class="shadow text-form-inner-container">
	<div class="text-form-header">
	    <?=$this->escapeForHTML($this->userStorage["name_first"] . " " . $this->userStorage["name_last"])?>
	</div>
	<div class="text-form-subheader-container">
	    <div class="center-container">
		<?=$this->escapeForHTML($this->userStorage["email"])?>
	    </div>
	</div>
	<div class="text-form-subheader-container">
	    <div class="center-container">
		<?=$this->escapeForHTML($this->userStorage["phone_number"])?>
	    </div>
	</div>
	
    </div>
</div>

<div class="center-container">
    <h3>Orders</h3>
</div>
<div id="order-table" class="user-orders">
    <?php foreach($this->orderStorage as $order): ?>
	<a href="/Dashboard/orders?uuid=<?=UUID::orderedBytesToArrangedString($order["uuid"])?>" class="ignore-link-styling">
	    <div class="user-order">
		<?=date("F d, Y g:i A", strtotime($order["date"]))?>
		<?=$this->formatBasicOrderForHTML($order)?>
	    </div>
	</a>
    <?php endforeach; ?>
</div>

<input type="hidden" name="CSRFToken"  id="CSRFToken" value="<?= $this->sessionManager->getCSRFToken(); ?>">
<?php require APP_ROOT . "/views/includes/footer.php" ?>
