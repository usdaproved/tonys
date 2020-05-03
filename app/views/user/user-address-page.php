<?php require APP_ROOT . "/views/includes/header.php" ?>
<link href="<?=$this->getFile('css', 'components');?>" rel="stylesheet">
<link href="<?=$this->getFile('css', __FILE__);?>" rel="stylesheet">
<header>Tony's Taco House</header>
<a href="/">Home</a>

<?php $this->printOneTimeMessages(USER_ALERT); ?>
<?php $this->printOneTimeMessages(USER_SUCCESS); ?>

<?php if(isset($this->user["address"])): ?>
    <h3>Default Address:</h3>
    <div class="address-container">
	<?=$this->formatAddressForHTML($this->user["address"])?>
    </div>
<?php endif; ?>
<?php if(isset($this->user["other_addresses"])): ?>
    <h3>Other Addresses:</h3>
    <div class="orders-container">
	<?php foreach($this->user["other_addresses"] as $address):?>
	    <div class="order-container" id="address-<?=$address['id']?>">
		<?=$this->formatAddressForHTML($address)?>
	    </div>
	<?php endforeach; ?>
    </div>
    <input type="button" id="set-default-button" value="Set Default" hidden>
<?php endif; ?>

<h3>Add New Address:</h3>
<form method="post">
    <label for="address_line">Street address</label>
    <input type="text" id="address_line" name="address_line" autocomplete="street-address" required>
    <label for="city">City</label>
    <input type="text" id="city" name="city" autocomplete="address-level2" required>
    <label for="state">State</label>
    <input type="text" id="state" name="state" autocomplete="address-level1" required>
    <label for="zip_code">Zip code</label>
    <input type="text" id="zip_code" name="zip_code" autocomplete="postal-code" required>
    <label for="check-default">Set as default address</label>
    <input type="checkbox" id="check-default" name="set_default">
    <input type="submit" id="add-address" value="Add">

    <input type="hidden" name="CSRFToken"  id="CSRFToken" value="<?= $this->sessionManager->getCSRFToken(); ?>">
</form>

<script src="<?=$this->getFile('js', __FILE__);?>" type="module"></script>
<?php require APP_ROOT . "/views/includes/footer.php" ?>
