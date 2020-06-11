<?php require APP_ROOT . "/views/includes/header.php" ?>
<link href="<?=$this->getFile('css', 'components');?>" rel="stylesheet">
<link href="<?=$this->getFile('css', __FILE__);?>" rel="stylesheet">
<div class="user-info">
    <p><?=$this->escapeForHTML($this->userStorage["name_first"] . " " . $this->userStorage["name_last"])?></p>
    <p><?=$this->escapeForHTML($this->userStorage["email"])?></p>
    <p><?=$this->escapeForHTML($this->userStorage["phone_number"])?></p>
</div>

<h3>Orders:</h3>
<div id="order-table" class="orders-container">
    <?php foreach($this->orderStorage as $order): ?>
	<a href="/Dashboard/orders?uuid=<?=UUID::orderedBytesToArrangedString($order["uuid"])?>" class="ignore-link-styling">
	    <div class="order-container">
		<?=$this->formatOrderForHTML($order)?>
		<div><?=$order["date"]?></div>
	    </div>
	</a>
    <?php endforeach; ?>
</div>

<input type="hidden" name="CSRFToken"  id="CSRFToken" value="<?= $this->sessionManager->getCSRFToken(); ?>">

<script src="<?=$this->getFile('js', __FILE__);?>" type="module"></script>
<?php require APP_ROOT . "/views/includes/footer.php" ?>
