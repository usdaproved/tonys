<?php require APP_ROOT . "/views/includes/header.php" ?>
<link href="<?=$this->getFile('css', 'components');?>" rel="stylesheet">
<link href="<?=$this->getFile('css', __FILE__);?>" rel="stylesheet">
<?php $this->printOneTimeMessages(USER_SUCCESS); ?>


<div class="text-form-inner-container shadow">
    <div class="text-form-header">
	Current Printers
    </div>
    <?php foreach($printers as $printer): ?>
	<?php $printerConnected = ($printer['connected'] == 1) ? 'connected' : 'disconnected';?>
	<div class="printer input-shared-line" id="<?=bin2hex($printer['selector'])?>">
	    <div class="printer-delete-name">
		<button type="button" class="svg-button remove-printer">
		    <svg xmlns="http://www.w3.org/2000/svg" fill="red" height="24" viewBox="0 0 24 24" width="24">
			<path d="M0 0h24v24H0z" fill="none"/>
			<path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/>
		    </svg>
		</button>
		<p class="printer-name"><?=$this->escapeForHTML($printer['name'])?></p>
	    </div>
	    <p class="<?=$printerConnected?>"><?=$printerConnected?></p>
	</div>
    <?php endforeach; ?>
</div>

<div class="shadow text-form-inner-container">
    <div class="text-form-header">
	New Printer
    </div>
    <div class="input-container">
	<label for="add-printer-name">Name</label>
	<input type="text" id="add-printer-name" />
    </div>
    <div class="wide-button-container">
	<button class="add-printer svg-button wide-button" id="add-printer">
	    Add
	</button>
    </div>
</div>

<input type="hidden" name="CSRFToken" id="CSRFToken" value="<?= $this->sessionManager->getCSRFToken(); ?>">

<script src="<?=$this->getFile('js', __FILE__);?>" type="module"></script>
<?php require APP_ROOT . "/views/includes/footer.php" ?>
