<?php require APP_ROOT . "/views/includes/header.php" ?>
<link href="<?=$this->getFile('css', __FILE__);?>" rel="stylesheet">
<header>Tony's Taco House</header>
<a href="/Dashboard">Dashboard</a>
<br>
Show Addresses <input type="checkbox" id="address-view">
<form method="post" id="form-status-update">
    <input type="submit" value="Update status">
    <table id="order-table">

    </table>
    <input type="hidden" name="CSRFToken" value="<?= $this->sessionManager->getCSRFToken(); ?>">
</form>

<script src="<?=$this->getFile('js', __FILE__);?>"></script>
<?php require APP_ROOT . "/views/includes/footer.php" ?>
