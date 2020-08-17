<?php require APP_ROOT . "/views/includes/header.php" ?>
<link href="<?= $this->getFile("css", "components"); ?>" rel="stylesheet">
<link href="<?= $this->getFile("css", __FILE__); ?>" rel="stylesheet">

<?php $this->printOneTimeMessages(USER_ALERT); ?>

<div class="center-container">
    <div class="shadow text-form-inner-container">
	<form method="post">
	    <div class="input-container">
		<label for="email">Email</label>
		<input type="email" id="email" name="email" autocomplete="email">
	    </div>
	    <div class="input-container">
		<label for="password">Password</label>
		<input type="password" id="password" name="password" autocomplete="current-password">
	    </div>
	    <div class="remember-container">
		<input type="checkbox" id="remember_me" name="remember_me">
		<label for="remember_me">Remember me</label>
	    </div>
	    <input type="hidden" name="CSRFToken" value="<?= $this->sessionManager->getCSRFToken(); ?>">
	    <div class="wide-button-container">
		<button type="submit" class="wide-button svg-button">
		    Log in
		</button>
	    </div>
	</form>
	<div class="center-container margin-top-1">
	    <a href="/User/forgot" class="no-text-decorate"> Forgot Password?</a>
	</div>
    </div>
</div>

<?php require APP_ROOT . "/views/includes/footer.php" ?>
