<?php require APP_ROOT . "/views/includes/header.php" ?>
<link href="<?= $this->getFile("css", __FILE__); ?>" rel="stylesheet">
<header>Tony's Taco House</header>

<?php $this->printOneTimeMessages(USER_ALERT); ?>

<form method="post">
    <label for="email">Email</label>
    <input type="email" id="email" name="email" autocomplete="email">
    <label for="password">Password</label>
    <input type="password" id="password" name="password" autocomplete="current-password">
    <label for="remember_me">Remember me</label>
    <input type="checkbox" id="remember_me" name="remember_me">
    <input type="hidden" name="CSRFToken" value="<?= $this->sessionManager->getCSRFToken(); ?>">
    <input type="submit" value="Log in">
</form>

<?php require APP_ROOT . "/views/includes/footer.php" ?>
