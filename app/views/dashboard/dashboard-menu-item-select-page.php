<?php require APP_ROOT . "/views/includes/header.php" ?>
<link href="<?=$this->getFile('css', __FILE__);?>" rel="stylesheet">
<header>Tony's Taco House</header>
<a href="/Dashboard">Dashboard</a>
<?php $this->printOneTimeMessages(USER_SUCCESS); ?>
<div>
    Edit categories <a href="/Dashboard/menu/categories">here</a>.
</div>
<div>
    Create new menu item <a href="/Dashboard/menu/item?id=0">here</a>.
</div>
<input type="hidden" id="CSRFToken" value="<?=$this->sessionManager->getCSRFToken();?>">
<input type="button" id="update-menu" value="Update Menu Order">
<div id="result"></div>
<div id="menu">
    <?php foreach($this->menuStorage as $category): ?>
	<div class="menu-category" id="<?=$category['id'];?>-category" draggable="true">
	    <h3><?=$this->escapeForHTML($category['name']);?></h3>	
	    <ul name="<?=$this->escapeForAttributes($category['name']);?>">
		<?php foreach($category['items'] as $menuItem): ?>
		    <li id="<?=$menuItem['id'];?>-menu-item" class="menu-item" draggable="true">
			<?=$menuItem['name'];?>
			<a href="/Dashboard/menu/item?id=<?=$menuItem['id'];?>">edit</a>
		    </li>
		<?php endforeach; ?>
	    </ul>
	</div>
    <?php endforeach; ?>
</div>

<script src="<?=$this->getFile('js', __FILE__);?>"></script>
<?php require APP_ROOT . "/views/includes/footer.php" ?>
