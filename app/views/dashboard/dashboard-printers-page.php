<?php require APP_ROOT . "/views/includes/header.php" ?>
<link href="<?=$this->getFile('css', 'components');?>" rel="stylesheet">
<link href="<?=$this->getFile('css', __FILE__);?>" rel="stylesheet">
<?php $this->printOneTimeMessages(USER_SUCCESS); ?>

<label for="add-printer-name">Printer Name:</label>
<input type="text" id="add-printer-name" />
<button class="add-printer" id="add-printer">Add Printer</button>
<div class="orders-container">
    <?php foreach($printers as $printer): ?>
	<div class="order-container" id="<?=bin2hex($printer['selector'])?>">
	    <p class="printer-name"><?=$this->escapeForHTML($printer['name'])?></p>
	    <p><?=($printer['connected'] == 1) ? 'connected' : 'disconnected'; ?></p>
	    <button type="button" class="remove-printer">Remove</button>
	</div>
    <?php endforeach; ?>
</div>

<input type="hidden" name="CSRFToken" id="CSRFToken" value="<?= $this->sessionManager->getCSRFToken(); ?>">

<script src="<?=$this->getFile('js', __FILE__);?>" type="module"></script>
<?php require APP_ROOT . "/views/includes/footer.php" ?>
