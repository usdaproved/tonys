<?php require APP_ROOT . "/views/includes/header.php" ?>
<link href="<?=$this->getFile('css', 'components');?>" rel="stylesheet">
<link href="<?=$this->getFile('css', __FILE__);?>" rel="stylesheet">
<?php $this->printOneTimeMessages(USER_ALERT); ?>
<?php $this->printOneTimeMessages(USER_SUCCESS); ?>

<div class="split-layout">
    <div class="split-left-section">
	<?php if(isset($this->user["default_address"])): ?>
	    <div class="left-section-wrapper width-full max-width-768 shadow text-form-inner-container">
		<div class="addresses-container">
		    <div class="default-address">
			<div class="text-form-header">Default Address</div>
			<?=$this->formatAddressForHTML($this->user["default_address"])?>
		    </div>
		    <?php if(isset($this->user["other_addresses"])): ?>
			<?php foreach($this->user["other_addresses"] as $address):?>
			    <button type="button" class="svg-button selectable-address" id="<?=UUID::orderedBytesToArrangedString($address['uuid'])?>">
				<?=$this->formatAddressForHTML($address)?>
			    </button>
			<?php endforeach; ?>
			<div class="address-action-buttons wide-button-container">
			    <button type="button" id="set-default-button" class="svg-button wide-button inactive" disabled>
				Set Default
			    </button>
			    <button type="button" id="delete-button" class="svg-button wide-button inactive" disabled>
				Delete
			    </button>
			</div>
		    <?php endif; ?>
		</div>
	    </div>
	<?php endif; ?>
    </div>
    <div class="split-right-section">
	<div class="new-address-container width-full max-width-768 shadow text-form-inner-container">
	    <form method="post">
		<div class="text-form-header">
		    Add New Address
		</div>
		<div class="input-container">
		    <label for="address_line">Street address</label>
		    <input type="text" id="address_line" name="address_line" autocomplete="street-address" required>
		</div>
		<div class="input-container">
		    <label for="city">City</label>
		    <input type="text" id="city" name="city" autocomplete="address-level2" required>
		</div>
		<div class="input-container">
		    <label for="state">State</label>
		    <input type="text" id="state" name="state" autocomplete="address-level1" required>
		</div>
		<div class="input-container">
		    <label for="zip_code">Zip code</label>
		    <input type="text" id="zip_code" name="zip_code" autocomplete="postal-code" required>
		</div>
		<div class="default-container">
		    <input type="checkbox" id="check-default" name="set_default">
		    <label for="check-default">Set as default address</label>
		</div>
		<div class="wide-button-container">
		    <button type="submit" id="add-address" class="wide-button svg-button">
			Add
		    </button>
		</div>
		<input type="hidden" name="CSRFToken"  id="CSRFToken" value="<?= $this->sessionManager->getCSRFToken(); ?>">
	    </form>
	</div>
    </div>
</div>

<script src="<?=$this->getFile('js', __FILE__);?>" type="module"></script>
<?php require APP_ROOT . "/views/includes/footer.php" ?>
