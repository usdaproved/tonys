<?php require APP_ROOT . "/views/includes/header.php" ?>
<link href="<?=$this->getFile('css', 'components');?>" rel="stylesheet">

<?php $this->printOneTimeMessages(USER_ALERT); ?>
<?php $this->printOneTimeMessages(USER_SUCCESS); ?>

<div class="center-container">
    <div class="shadow text-form-inner-container">
	<form method="post">
	    <div class="text-form-header">
		Forgot Password?
	    </div>
	    <div class="text-form-subheader-container">
		<div class="center-container">
		    <div>
			Get a reset link
		    </div>
		</div>
		<div class="text-form-subheader">
		</div>
	    </div>
	    
	    <div class="input-container">
		<label for="email">Email</label>
		<input type="email" id="email" name="email" autocomplete="email" required>
	    </div>
	    <div class="wide-button-container">
		<button type="submit" id="add-address" class="wide-button svg-button">
		    Send email
		</button>
	    </div>
	    <input type="hidden" name="CSRFToken"  id="CSRFToken" value="<?= $this->sessionManager->getCSRFToken(); ?>">
	</form>
    </div>
</div>

<?php require APP_ROOT . "/views/includes/footer.php" ?>
