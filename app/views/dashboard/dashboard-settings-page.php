<?php require APP_ROOT . "/views/includes/header.php" ?>
<link href="<?=$this->getFile('css', 'components');?>" rel="stylesheet">
<link href="<?=$this->getFile('css', __FILE__);?>" rel="stylesheet">

<div class="center-container">
    <div class="shadow text-form-inner-container">
	<div class="text-form-header">
	    Settings
	</div>
	<div class="checkbox-container">
	    <input type="checkbox" id="delivery-on" <?=($settings['delivery_on'] == 1) ? 'checked' : NULL?>>
	    <label for="delivery-on">Delivery Status</label>
	</div>
	<div class="checkbox-container">
	    <input type="checkbox" id="pickup-on" <?=($settings['pickup_on'] == 1) ? 'checked' : NULL?>>
	    <label for="pickup-on">Pickup Status</label>
	</div>
	<div class="setting-link-container">
	    <a href="/Dashboard/settings/schedule" class="setting-link">Schedule</a>
	</div>
	<div class="setting-link-container">
	    <a href="/Dashboard/settings/printers" class="setting-link">Printers</a>
	</div>
    </div>
</div>

<input type="hidden" name="CSRFToken" id="CSRFToken" value="<?= $this->sessionManager->getCSRFToken(); ?>">

<script src="<?=$this->getFile('js', __FILE__);?>" type="module"></script>
<?php require APP_ROOT . "/views/includes/footer.php" ?>
