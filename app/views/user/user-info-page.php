<?php require APP_ROOT . "/views/includes/header.php" ?>

<link href="<?=$this->getFile('css', 'components');?>" rel="stylesheet">

<?php $this->printOneTimeMessages(USER_ALERT); ?>
<?php $this->printOneTimeMessages(USER_SUCCESS); ?>

<div class="center-container">
    <div class="shadow text-form-inner-container">
	<form id="update-info" method="post">
	    <div class="text-form-header">
		Personal Information
	    </div>
	    <div class="text-form-subheader-container">
		<div class="center-container">
		    <?=$this->escapeForHTML($this->user["email"]);?>
		</div>
	    </div>
	    <div class="input-container">
		<label for="name_first">First name</label>
		<input type="text" id="name_first" name="name_first" value="<?=$this->escapeForAttributes($this->user["name_first"] ?? NULL);?>" autocomplete="given-name">
	    </div>
	    <div class="input-container">
		<label for="name_last">Last name</label>
		<input type="text" id="name_last" name="name_last" value="<?=$this->escapeForAttributes($this->user["name_last"] ?? NULL);?>" autocomplete="family-name">
	    </div>
	    <div class="input-container">
		<label for="phone">Phone number</label>
		<input type="text" id="phone" name="phone" value="<?=$this->escapeForAttributes($this->user["phone_number"] ?? NULL);?>" autocomplete="tel">
	    </div>
	    <div class="wide-button-container">
		<button type="submit" id="update-info" class="wide-button svg-button">
		    Update
		</button>
	    </div>
	    <?php if($this->sessionManager->isUserLoggedIn()): ?>
	    <div class="center-container margin-top-1">
		<a href="/User/password" class="no-text-decorate">Change Password</a>
	    </div>
	    <?php endif; ?>
	    <input type="hidden" name="CSRFToken"  id="CSRFToken" value="<?= $this->sessionManager->getCSRFToken(); ?>">
	</form>
    </div>
</div>

<?php require APP_ROOT . "/views/includes/footer.php" ?>
