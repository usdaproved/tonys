<?php require APP_ROOT . "/views/includes/header.php" ?>
<link href="<?=$this->getFile('css', __FILE__);?>" rel="stylesheet">
<header>Tony's Taco House</header>
<a href="/Dashboard">Dashboard</a>
<a href="/Dashboard/menu">Menu</a>
<?php $this->printOneTimeMessages(USER_SUCCESS); ?>
<form method="post">
    <label for="category">Add category</label>
    <input type="text" name="category" id="category" required>
    <input type="hidden" name="CSRFToken" value="<?= $this->sessionManager->getCSRFToken(); ?>">
    <input type="submit" value="Add">
</form>
<form method="post">
    <?php foreach($this->menuStorage as $category): ?>
	<label for="<?=$category['id'];?>"><?=$this->escapeForHTML($category['name']);?></label>
	<input type="text" name="<?=$category['id'];?>" id="<?=$category['id'];?>" value="<?=$this->escapeForAttributes($category['name']);?>">
    <?php endforeach; ?>
    <input type="hidden" name="CSRFToken" value="<?= $this->sessionManager->getCSRFToken(); ?>">
<input type="submit" value="Update">
</form>

<?php require APP_ROOT . "/views/includes/footer.php" ?>
