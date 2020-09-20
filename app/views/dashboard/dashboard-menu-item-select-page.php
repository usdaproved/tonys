<?php require APP_ROOT . "/views/includes/header.php" ?>
<link href="<?= $this->getFile("css", "components"); ?>" rel="stylesheet">
<link href="<?=$this->getFile('css', __FILE__);?>" rel="stylesheet">
<div class="scrolling-container shadow">
    <div class="action-bar">
	<div class="link-button-container">
	    <a href="/Dashboard/menu/categories" class="link-button">
		<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="white" width="18px" height="18px"><path d="M0 0h24v24H0z" fill="none"/><path d="M3 17.25V21h3.75L17.81 9.94l-3.75-3.75L3 17.25zM20.71 7.04c.39-.39.39-1.02 0-1.41l-2.34-2.34c-.39-.39-1.02-.39-1.41 0l-1.83 1.83 3.75 3.75 1.83-1.83z"/></svg>
		<span class="link-button-text">Edit Categories</span>
	    </a>
	    <a href="/Dashboard/menu/item?id=0" class="link-button">
		<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="white" width="18px" height="18px"><path d="M0 0h24v24H0V0z" fill="none"/><path d="M18 13h-5v5c0 .55-.45 1-1 1s-1-.45-1-1v-5H6c-.55 0-1-.45-1-1s.45-1 1-1h5V6c0-.55.45-1 1-1s1 .45 1 1v5h5c.55 0 1 .45 1 1s-.45 1-1 1z"/></svg>
		<span class="link-button-text">Add item</span>
	    </a>
	</div>
	<div class="edit-order-button-container">
	    <button type="button" id="toggle-edit" class="svg-button edit-order-button">
		Edit Order
	    </button>
	    <button type="button" id="update-menu" class="svg-button edit-order-button update inactive" disabled>
		Update
	    </button>
	</div>
    </div>
</div>

<?php $this->printOneTimeMessages(USER_SUCCESS); ?>

<div class="center-container">
    <div class="shadow text-form-inner-container" id="menu">
	<div class="text-form-header">
	    Menu
	</div>
	<?php foreach($this->menuStorage as $category): ?>
	    <div class="menu-category" id="<?=$category['id'];?>-category" draggable="false">
		<h3 class="center-container"><?=$this->escapeForHTML($category['name']);?></h3>
		<ul name="<?=$this->escapeForAttributes($category['name']);?>">
		    <?php foreach($category['items'] as $menuItem): ?>
			<li id="<?=$menuItem['id'];?>-menu-item" class="menu-item" draggable="false">
			    <a href="/Dashboard/menu/item?id=<?=$menuItem['id'];?>" class="no-text-decorate menu-item-link">
				<?=$this->escapeForHTML($menuItem['name']);?>
			    </a>
			</li>
		    <?php endforeach; ?>
		</ul>
	    </div>
	<?php endforeach; ?>
    </div>
</div>

<input type="hidden" id="CSRFToken" value="<?=$this->sessionManager->getCSRFToken();?>">

<script src="<?=$this->getFile('js', __FILE__);?>" type="module"></script>
<?php require APP_ROOT . "/views/includes/footer.php" ?>
