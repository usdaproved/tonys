<?php require APP_ROOT . "/views/includes/header.php" ?>

<link href="<?=$this->getFile('css', 'components');?>" rel="stylesheet">

<?php $this->printOneTimeMessages(USER_ALERT); ?>
<?php $this->printOneTimeMessages(USER_SUCCESS); ?>

<div class="center-container">
    <div class="shadow text-form-inner-container">
	<form id="change-password" method="post">
	    <div class="text-form-header">
		Change Password
	    </div>
	    <div class="input-container">
		<label for="current_password">Current password</label>
		<input type="password" id="current_password" name="current_password" autocomplete="current-password">
	    </div>
	    <div class="input-container">
		<label for="new_password">New password</label>
		<input type="password" id="new_password" name="new_password" autocomplete="new-password">
	    </div>
	    <div class="input-container">
		<label for="repeat_password">Repeat new password</label>
		<input type="password" id="repeat_password" name="repeat_password" autocomplete="new-password">
	    </div>
	    <div class="wide-button-container">
		<button type="submit" id="update-info" class="wide-button svg-button">
		    Update
		</button>
	    </div>
	    <input type="hidden" name="CSRFToken"  id="CSRFToken" value="<?= $this->sessionManager->getCSRFToken(); ?>">
	</form>
    </div>
</div>

<?php require APP_ROOT . "/views/includes/footer.php" ?>
