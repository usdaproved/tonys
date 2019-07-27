<?php require APP_ROOT . "/views/includes/header.php" ?>
<link rel="stylesheet" type="text/css" href="<?= $this->getFile("css", __FILE__); ?>">

<?php $this->printOneTimeMessages(USER_ALERT); ?>
<form id="form-order-selection" method="post">
   
    <?php foreach($this->menu as $menuItem): ?>
	<?php
	$value = NULL;
	// TODO: Might need to put this somewhere else.
	if(!is_null($this->orderStorage)){
	    foreach($this->orderStorage['order_line_items'] as $lineItem){
		if($lineItem['name'] === $menuItem['name']) $value = $lineItem['quantity'];
	    }
	}

	?>
	<label for="<?=$menuItem['name']?>"><?= $menuItem['name']; ?></label>
	<input type="number" id="<?=$menuItem['name']?>" name="<?=$menuItem['name']?>" value="<?=$value?>">
    <?php endforeach; ?>
    <input type="hidden" name="CSRFToken" value="<?= $this->sessionManager->getCSRFToken(); ?>">
    <input type="submit" value="submit">
</form>

<?php require APP_ROOT . "/views/includes/footer.php" ?>
