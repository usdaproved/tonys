<?php require APP_ROOT . "/views/includes/header.php" ?>
<link href="<?= $this->getFile("css", __FILE__); ?>" rel="stylesheet">
<header>Tony's Taco House</header>

<form method="post">
    <?php echo $this->printOneTimeMessage(); ?>
    <label for="email">Email</label>
    <input type="email" id="email" name="email">
    <label for="password">Password</label>
    <input type="password" id="password" name="password">
    <input type="hidden" name="CSRFToken" value="<?= $this->sessionManager->getCSRFToken(); ?>">
    <input type="submit" value="Log in">
</form>

<?php require APP_ROOT . "/views/includes/footer.php" ?>
