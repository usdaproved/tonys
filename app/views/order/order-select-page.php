<?php require APP_ROOT . "/views/includes/header.php" ?>
<link rel="stylesheet" type="text/css" href="<?= $this->getFile("css", __FILE__); ?>">
<header>Tony's Taco House</header>

<?php $this->printOneTimeMessages(USER_ALERT); ?>

<?php foreach($this->menuStorage ?? array() as $category): ?>
    <h3 class="category-name">
	<?=$this->escapeForHTML($category['name'] ?? NULL);?>
    </h3>
    <div class="category-container">
	<?php foreach($category['items'] ?? array() as $item): ?>
	    <div class="item-container" id="<?=$item['id']?? NULL?>-item-container">
		<a href="/Order?id=<?=$item['id'] ?? NULL?>" rel="nofollow" class="item-link">
		    <div class="item-info-container">
			<div class="item-name">
			    <?=$this->escapeForHTML($item['name'] ?? NULL);?>
			</div>
			<div class="item-description">
			    <?=$this->escapeForHTML($item['description'] ?? NULL);?>
			</div>
			<div class="item-price">
			    <?='$' . $this->escapeForHTML($item['price'] ?? NULL);?>
			</div>
		    </div>
		</a>
	    </div>
	<?php endforeach; ?>
    </div>
<?php endforeach; ?>

<input type="hidden" name="CSRFToken" id="CSRFToken" value="<?= $this->sessionManager->getCSRFToken(); ?>">

<script src="<?=$this->getFile('js', __FILE__);?>" type="module"></script>
<?php require APP_ROOT . "/views/includes/footer.php" ?>
