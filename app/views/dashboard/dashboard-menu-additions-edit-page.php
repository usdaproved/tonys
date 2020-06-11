<?php require APP_ROOT . "/views/includes/header.php" ?>
<link href="<?=$this->getFile('css', __FILE__);?>" rel="stylesheet">
<?php $this->printOneTimeMessages(USER_SUCCESS); ?>
<form method="post">
    <label for="new-addition-name">New addition name:</label>
    <input type="text" name="name" id="new-addition-name" required>
    <label for="new-addition-price">New addition price:</label>
    <input type="number" id="new-addition-price" name="price" step="0.01" min="0" max="99.99" required>
    <input type="hidden" id="CSRFToken" name="CSRFToken" value="<?=$this->sessionManager->getCSRFToken();?>">
    <input type="submit" value="Add">
</form>
<div id="additions-container" class="additions-container">
<?php foreach($this->menuStorage as $addition): ?>
    <div id="<?=$addition['id'];?>-addition" class="addition">
	<input type="button" class="addition-update-button" value="Update">
	<input type="button" class="addition-remove-button" value="Remove">
	<label for="<?=$addition['id']?>-addition-name">Name:</label>
	<input type="text" name="name" id="<?=$addition['id']?>" class="addition-name" value="<?=$this->escapeForAttributes($addition['name'])?>"required>
	<label for="<?=$addition['id']?>-addition-price">Price:</label>
	<input type="number" name="price" id="<?=$addition['id']?>-addition-price" class="addition-price" step="0.01" min="0" max="99.99" value="<?=$addition['price_modifier']?>" required>
    </div>
<?php endforeach; ?>
</div>

<script src="<?=$this->getFile('js', __FILE__);?>"></script>
<?php require APP_ROOT . "/views/includes/footer.php" ?>
