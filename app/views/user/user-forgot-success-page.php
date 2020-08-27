<?php require APP_ROOT . "/views/includes/header.php" ?>
<link href="<?=$this->getFile('css', 'components');?>" rel="stylesheet">

<?php $this->printOneTimeMessages(USER_ALERT); ?>
<?php $this->printOneTimeMessages(USER_SUCCESS); ?>

<div class="center-container">
    <div class="shadow text-form-inner-container">
	<form method="post">
	    <div class="text-form-header">
		Enter New Password
	    </div>
	    <div class="input-container">
		<label for="password">Password</label>
		<input type="password" id="password" name="password" required>
	    </div>
	    <div class="wide-button-container">
		<button type="submit" id="add-address" class="wide-button svg-button">
		    Set Password
		</button>
	    </div>
	    <input type="hidden" name="reset"  id="reset-token" value="<?= $resetToken ?>">
	    <input type="hidden" name="CSRFToken"  id="CSRFToken" value="<?= $this->sessionManager->getCSRFToken(); ?>">
	</form>
    </div>
</div>

<?php require APP_ROOT . "/views/includes/footer.php" ?>
