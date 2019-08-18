<?php require APP_ROOT . "/views/includes/header.php" ?>
<link href="<?=$this->getFile('css', __FILE__);?>" rel="stylesheet">
<header>Tony's Taco House</header>
<a href="/Dashboard">Dashboard</a>
<?php $this->printOneTimeMessages(USER_SUCCESS); ?>
<?php $this->printOneTimeMessages(USER_ALERT); ?>
<form method="post">
    <label for="email">Add Employee</label>
    <input type="email" name="email" id="email" required>
    <input type="submit" value="Add">
    <input type="hidden" name="CSRFToken" value="<?= $this->sessionManager->getCSRFToken(); ?>">
</form>
<br>
<form method="post">
    <input type="submit" name="delete" value="delete">
    <input type="submit" name="admin" value="toggle admin status">
    <?php foreach($this->employeeStorage as $employee): ?>
	<div>
	    <?=$employee['name_first'];?> - <?=USER_TYPE_ARRAY[$employee['user_type']];?>
	    <input type="checkbox" name="employees[]" value="<?=$employee['id'];?>">
	</div>
    <?php endforeach; ?>
    <input type="hidden" name="CSRFToken" value="<?= $this->sessionManager->getCSRFToken(); ?>">
</form>

<?php require APP_ROOT . "/views/includes/footer.php" ?>
