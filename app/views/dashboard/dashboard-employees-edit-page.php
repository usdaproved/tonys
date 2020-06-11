<?php require APP_ROOT . "/views/includes/header.php" ?>
<link href="<?=$this->getFile('css', 'components');?>" rel="stylesheet">
<link href="<?=$this->getFile('css', __FILE__);?>" rel="stylesheet">
<?php $this->printOneTimeMessages(USER_SUCCESS); ?>
<?php $this->printOneTimeMessages(USER_ALERT); ?>

<h3>Edit Employees</h3>
<input type="submit" id="delete-employee" name="delete" value="Delete" hidden>
<input type="submit" id="toggle-admin" name="admin" value="Toggle Admin Status" hidden>
<div class="orders-container" id="current-employees">
    <?php foreach($this->userStorage as $employee): ?>
	<div class="order-container" id="<?=UUID::orderedBytesToArrangedString($employee['uuid']);?>">
	    <?=$employee['name_first'];?> - <?=USER_TYPE_ARRAY[$employee['user_type']];?>
	</div>
    <?php endforeach; ?>
</div>

<h3>Add Employee</h3>
<input type="submit" id="add-employee" value="Add" hidden>
<?=$this->searchUserComponent(true);?>

<input type="hidden" id="CSRFToken" name="CSRFToken" value="<?= $this->sessionManager->getCSRFToken(); ?>">

<script src="<?=$this->getFile('js', __FILE__);?>" type="module"></script>
<?php require APP_ROOT . "/views/includes/footer.php" ?>
