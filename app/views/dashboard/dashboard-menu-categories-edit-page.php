<?php require APP_ROOT . "/views/includes/header.php" ?>
<link href="<?=$this->getFile('css', 'components');?>" rel="stylesheet">
<link href="<?=$this->getFile('css', __FILE__);?>" rel="stylesheet">
<?php $this->printOneTimeMessages(USER_SUCCESS); ?>
<?php $this->printOneTimeMessages(USER_ALERT); ?>
<div class="split-layout">
    <div class="split-left-section">
	<div class="text-form-inner-container shadow">
	    <form method="post">
		<div class="text-form-header">
		    Add Category
		</div>
		<div class="input-container">
		    <label for="category">Name</label>
		    <input type="text" name="category" id="category" required>
		</div>
		<input type="hidden" name="CSRFToken" id="CSRFToken" value="<?= $this->sessionManager->getCSRFToken(); ?>">
		<div class="wide-button-container">
		    <button type="submit" class="wide-button svg-button">
			Add
		    </button>
		</div>
	    </form>
	</div>
    </div>
    <div class="split-right-section">
	<div class="text-form-inner-container shadow">
	    <form method="post">
		<div class="text-form-header">
		    Current Categories
		</div>
		<?php // NOTE(Trystan): Set a message for empty categories, like 'none to show yet'. And don't add submit button if empty. ?>
		<?php foreach($this->menuStorage as $category): ?>
		    <div class="input-shared-line category-line">
			<button type="button" class="remove-category-button svg-button" data-id="<?=$category['id']?>">
			    <svg xmlns="http://www.w3.org/2000/svg" fill="red" height="24" viewBox="0 0 24 24" width="24">
				<path d="M0 0h24v24H0z" fill="none"/>
				<path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/>
			    </svg>
			</button>
			<div class="input-container">
			    <label for="<?=$category['id'];?>"><?=$this->escapeForHTML($category['name']);?></label>
			    <input type="text" name="<?=$category['id'];?>" id="<?=$category['id'];?>" value="<?=$this->escapeForAttributes($category['name']);?>">
			</div>
		    </div>
		<?php endforeach; ?>
		<input type="hidden" name="CSRFToken" value="<?= $this->sessionManager->getCSRFToken(); ?>">
		<div class="wide-button-container">
		    <button type="submit" class="wide-button svg-button">
			Update
		    </button>
		</div>
	    </form>
	</div>
    </div>
</div>

<script src="<?=$this->getFile('js', __FILE__);?>" type="module"></script>
<?php require APP_ROOT . "/views/includes/footer.php" ?>
