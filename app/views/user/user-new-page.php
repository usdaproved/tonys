<?php require APP_ROOT . "/views/includes/header.php" ?>
<link href="<?= $this->getFile("css", "components"); ?>" rel="stylesheet">
<link href="<?= $this->getFile("css", __FILE__); ?>" rel="stylesheet">

<?php $this->printOneTimeMessages(USER_ALERT); ?>
<div class="center-container">
    <div class="shadow text-form-inner-container">
	<form method="post">
	    <div class="text-form-header">
		Checkout as a Guest
	    </div>
	    <div class="text-form-subheader-container">
		<div class="center-container">
		    <div>
			Or <a class="no-text-decorate" href="/register?redirect=/Order/submit">create an account</a>
		    </div>
		</div>
		<div class="text-form-subheader">
		</div>
	    </div>
	    <div class="input-container">
		<label for="email">Email</label>
		<input type="email" id="email" name="email" value="<?=$this->escapeForAttributes($this->user["email"] ?? NULL);?>" autocomplete="email" required>
	    </div>
	    <div class="input-container">
		<label for="name_first">First name</label>
		<input type="text" id="name_first" name="name_first" value="<?=$this->escapeForAttributes($this->user["name_first"] ?? NULL);?>" autocomplete="given-name" required>
	    </div>
	    <div class="input-container">
		<label for="name_last">Last name</label>
		<input type="text" id="name_last" name="name_last" value="<?=$this->escapeForAttributes($this->user["name_last"] ?? NULL);?>" autocomplete="family-name" required>
	    </div>
	    <div class="input-container">
		<label for="phone">Phone number</label>
		<input type="text" id="phone" name="phone" value="<?=$this->escapeForAttributes($this->user["phone_number"] ?? NULL);?>" autocomplete="tel" required>
	    </div>
	    <?php if($this->getAddress):?>
		<div class="input-container">
		    <label for="address_line">Street address</label>
		    <input type="text" id="address_line" name="address_line" value="<?=$this->escapeForAttributes($this->user["address"]["line"] ?? NULL);?>" autocomplete="street-address" required>
		</div>
		<div class="input-shared-line">
		<div class="input-container">
		    <label for="city">City</label>
		    <input type="text" id="city" name="city" value="<?=$this->escapeForAttributes($this->user["address"]["city"] ?? NULL);?>" autocomplete="address-level2" required>
		</div>
		<div class="input-container">
		    <label for="state">State</label>
		    <input type="text" id="state" name="state" minlength="2" maxlength="2" style="width: 2rem;" value="<?=$this->escapeForAttributes($this->user["address"]["state"] ?? NULL);?>" autocomplete="address-level1" required>
		</div>
		</div>
		<div class="input-container">
		    <label for="zip_code">Zip code</label>
		    <input type="text" id="zip_code" name="zip_code" value="<?=$this->escapeForAttributes($this->user["address"]["zip_code"] ?? NULL);?>" autocomplete="postal-code" required>
		</div>
	    <?php endif;?>
	    <input type="hidden" name="CSRFToken" value="<?= $this->sessionManager->getCSRFToken(); ?>">
	    <div class="wide-button-container">
		<button type="submit" class="wide-button svg-button">
		    Continue
		</button>
	    </div>
	</form>
	<div class="center-container margin-top-1">
	    <div>
		Already a user? <a class="no-text-decorate" href="/login?redirect=/Order/submit">Log in</a>
	    </div>
	</div>
    </div>
</div>

<?php require APP_ROOT . "/views/includes/footer.php" ?>
