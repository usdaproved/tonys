<?php require APP_ROOT . "/views/includes/header.php" ?>
<link href="<?=$this->getFile('css', __FILE__);?>" rel="stylesheet">
<header>Tony's Taco House</header>
<a href="/Dashboard">Dashboard</a>
<a href="/Dashboard/menu">Menu</a>
<form method="post">
    <label for="active">Active</label>
    <input type="checkbox" id="active" name="active" <?php if((int)($this->menuStorage['active'] ?? 0) === 1) echo 'checked'; ?>>
    <label for="category">Category</label>
    <select name="category" required>
	<?php foreach($this->menuStorage['categories'] as $category): ?>
	    <option value="<?=$category['id']?>" <?php if($category['id'] === ($this->menuStorage['category_id'] ?? NULL)) echo 'selected';?>><?=$this->escapeForHTML($category['name'])?></option>
	<?php endforeach; ?>
    </select> 
    <label for="item-name">Name</label>
    <input type="text" id="item-name" name="name" value="<?=$this->escapeForAttributes($this->menuStorage['name'] ?? NULL);?>" required>
    <label for="item-price">Price</label>
    <input type="number" id="item-price" name="price" value="<?=$this->intToCurrency($this->menuStorage['price'] ?? NULL);?>" step="0.01" min="0" max="99.99" required>
    <label for="item-description">Description</label>
    <textarea id="item-description" name="description" cols="40" rows="5" required><?=$this->escapeForAttributes($this->menuStorage['description'] ?? NULL);?></textarea>
    <input type="hidden" id="menu-item-id" name="id" value="<?=$this->menuStorage['id'] ?? NULL?>">
    <input type="hidden" id="CSRFToken" name="CSRFToken" value="<?= $this->sessionManager->getCSRFToken(); ?>">
    <input type="submit" value="Submit">
</form>
<h2>Choices:</h2>
<input type="button" id="add-group-button" value="Add Group">
<input type="button" id="update-choices" value="Update Choices">
<input type="button" id="choice-toggle-order-edit" value="Edit Order">
<div id="choices-container" class="choices-container">
    <?php foreach($this->menuStorage['choices'] as $groupID => $choiceGroup): ?>
	<div id="<?=$groupID;?>-choice-group" class="choice-group">
	    <input type="button" id="<?=$groupID;?>-remove-group" class="remove-group-button" value="Remove Group">
	    <input type="button" id="<?=$groupID;?>-add-option" class="add-option-button" value="Add Option">
	    <label for="<?=$groupID;?>-group-name">Name</label>
	    <input type="text" id="<?=$groupID;?>-group-name" class="group-name" name="name" value="<?=$this->escapeForAttributes($choiceGroup['name']);?>" required>
	    <label for="<?=$groupID;?>-group-min-picks">Minumum Picks</label>
	    <input type="number" id="<?=$groupID;?>-group-min-picks" class="group-min-picks" name="max-picks" value="<?=$this->escapeForAttributes($choiceGroup['min_picks']);?>" step="1" min="0" required>
	    <label for="<?=$groupID;?>-group-max-picks">Maximum Picks</label>
	    <input type="number" id="<?=$groupID;?>-group-max-picks" class="group-max-picks" name="max-picks" value="<?=$this->escapeForAttributes($choiceGroup['max_picks']);?>" step="1" min="0" required>
	    <?php foreach($choiceGroup['options'] ?? array() as $choiceID => $choice): ?>
		<div id="<?=$choiceID;?>-choice-option" class="choice-option">
		    <input type="button" class="remove-option-button" id="<?=$choiceID;?>-remove-choice" value="Remove Option">
		    <label for="<?=$choiceID;?>-option-name">Name</label>
		    <input type="text" id="<?=$choiceID;?>-option-name" class="option-name" name="name" value="<?=$this->escapeForAttributes($choice['name']);?>" required>
		    <label for="<?=$choiceID;?>-option-price">Price Modifier</label>
		    <input type="number" id="<?=$choiceID;?>-option-price" class="option-price" name="price" value="<?=$this->intToCurrency($choice['price_modifier']);?>" step="0.01" min="0" max="99.99" required>
		</div>
	    <?php endforeach; ?>
	</div>
    <?php endforeach; ?>
</div>
<h2>Additions:</h2>
<a href="/Dashboard/menu/additions">Edit Additions</a>
<br>
<select name="additions" id="addition-select-list" required>
    <?php foreach($this->menuStorage['all_additions'] as $additions): ?>
	<option value="<?=$additions['id']?>">
	    <?=$this->escapeForHTML($additions['name']) . ' - ' . $this->intToCurrency($additions['price_modifier'])?>
	</option>
    <?php endforeach; ?>
</select>
<input type="button" id="add-addition-button" value="Add">
<input type="button" id="addition-toggle-order-edit" value="Edit Order">
<div id="additions-container" class="additions-container">
    <?php foreach($this->menuStorage['additions'] as $addition): ?>
	<div class="addition" id="<?=$addition['id']?>-addition">
	    <p>
		<?=$this->escapeForHTML($addition['name']) . ' - ' . $this->intToCurrency($addition['price_modifier']);?>
	    </p>
	    <input type="button" class="remove-addition-button" id="<?=$addition['id']?>-remove-addition-button" value="Remove">
	</div>
    <?php endforeach; ?>
</ul>

<script src="<?=$this->getFile('js', __FILE__);?>" type="module"></script>
<?php require APP_ROOT . "/views/includes/footer.php" ?>
