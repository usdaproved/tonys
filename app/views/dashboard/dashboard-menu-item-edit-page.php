<?php require APP_ROOT . "/views/includes/header.php" ?>
<link href="<?=$this->getFile('css', __FILE__);?>" rel="stylesheet">
<header>Tony's Taco House</header>
<a href="/Dashboard">Dashboard</a>
<a href="/Dashboard/menu">Menu</a>
<form method="post">
    <label for="active">Active</label>
    <input type="checkbox" id="active" name="active" <?php if((int)$this->menuStorage['active'] === 1) echo 'checked'; ?>>
    <label for="category">Category</label>
    <select name="category" required>
	<?php foreach($this->menuStorage['categories'] as $categories): ?>
	    <option value="<?=$categories['id']?>" <?php if((int)$categories['id'] === (int)$this->menuStorage['category_id']) echo 'selected';?>><?=$this->escapeForHTML($categories['name'])?></option>
	<?php endforeach; ?>
    </select> 
    <label for="name">Name</label>
    <input type="text" id="name" name="name" value="<?=$this->escapeForAttributes($this->menuStorage['name']);?>" required>
    <label for="price">Price</label>
    <input type="number" id="price" name="price" value="<?=$this->escapeForAttributes($this->menuStorage['price']);?>" step="0.01" min="0" max="99.99" required>
    <label for="description">Description</label>
    <textarea id="description" name="description" cols="40" rows="5" required><?=$this->escapeForAttributes($this->menuStorage['description']);?></textarea>
    <input type="hidden" name="id" value="<?=(int)$this->menuStorage['id'];?>">
    <input type="hidden" name="CSRFToken" value="<?= $this->sessionManager->getCSRFToken(); ?>">
    <input type="submit" value="Submit">
</form>

<?php require APP_ROOT . "/views/includes/footer.php" ?>
