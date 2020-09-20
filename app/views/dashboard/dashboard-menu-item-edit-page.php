<?php require APP_ROOT . "/views/includes/header.php" ?>
<link href="<?=$this->getFile('css', 'components');?>" rel="stylesheet">
<link href="<?=$this->getFile('css', __FILE__);?>" rel="stylesheet">
<div class="text-form-inner-container shadow">
    <form method="post">
	<div class="text-form-header">
	    Menu Item Settings
	</div>
	<div class="remember-container center-container">
	    <input type="checkbox" id="active" name="active" <?php if((int)($this->menuStorage['active'] ?? 0) === 1) echo 'checked'; ?>>
	    <label for="active">Active</label>
	</div>
	<div class="input-container">
	    <label for="category">Category</label>
	    <select name="category" required>
		<?php foreach($this->menuStorage['categories'] as $category): ?>
		    <option value="<?=$category['id']?>" <?php if($category['id'] === ($this->menuStorage['category_id'] ?? NULL)) echo 'selected';?>><?=$this->escapeForHTML($category['name'])?></option>
		<?php endforeach; ?>
	    </select>
	</div>
	<div class="input-container">
	    <label for="item-name">Name</label>
	    <input type="text" id="item-name" name="name" value="<?=$this->escapeForAttributes($this->menuStorage['name'] ?? NULL);?>" required>
	</div>
	<div class="input-container">
	    <label for="item-price">Price</label>
	    <input type="number" id="item-price" name="price" value="<?=$this->intToCurrency($this->menuStorage['price'] ?? NULL);?>" step="0.01" min="0" max="99.99" required>
	</div>
	<div class="input-container">
	    <label for="item-description">Description</label>
	    <textarea id="item-description" name="description" cols="40" rows="5" required><?=$this->escapeForAttributes($this->menuStorage['description'] ?? NULL);?></textarea>
	</div>
	<input type="hidden" id="menu-item-id" name="id" value="<?=$this->menuStorage['id'] ?? NULL?>">
	<input type="hidden" id="CSRFToken" name="CSRFToken" value="<?= $this->sessionManager->getCSRFToken(); ?>">
	<div class="double-button-container">
	    <div class="wide-button-container">
		<button type="submit" class="wide-button svg-button">
		    Submit
		</button>
	    </div>
	    <div class="delete-button-container margin-left-1">
		<button type="button" class="wide-button svg-button cancel" id="delete-item" data-id="<?=$this->menuStorage['id']?>">
		    Delete
		</button>
	    </div>
	</div>
    </form>
</div>
<div class="scrolling-container shadow">
    <div class="center-container">
	<h2>Choices</h2>
    </div>
    <div class="action-bar">
	<button type="button" id="add-group-button" class="svg-button edit-order-button">
	    Add
	</button>
	<button type="button" id="update-choices" class="svg-button edit-order-button update">
	    Update
	</button>
	<button type="button" id="choice-toggle-order-edit" class="svg-button edit-order-button">
	    Edit Order
	</button>
    </div>
</div>
<div id="choices-container" class="choices-container">
    <?php foreach($this->menuStorage['choices'] as $groupID => $choiceGroup): ?>
	<div id="<?=$groupID;?>-choice-group" class="choice-group text-form-inner-container shadow">
	    <div class="text-form-header">
		<?=$this->escapeForHTML($choiceGroup['name']);?>
	    </div>
	    <div class="input-container">
		<label for="<?=$groupID;?>-group-name">Name</label>
		<input type="text" id="<?=$groupID;?>-group-name" class="group-name" name="name" value="<?=$this->escapeForAttributes($choiceGroup['name']);?>" required>
	    </div>
	    <div class="input-shared-line">
		<div class="input-container">
		    <label for="<?=$groupID;?>-group-min-picks">Min Picks</label>
		    <input type="number" id="<?=$groupID;?>-group-min-picks" class="group-min-picks" name="max-picks" value="<?=$this->escapeForAttributes($choiceGroup['min_picks']);?>" step="1" min="0" style="width:3rem;" required>
		</div>
		<div class="input-container">
		    <label for="<?=$groupID;?>-group-max-picks">Max Picks</label>
		    <input type="number" id="<?=$groupID;?>-group-max-picks" class="group-max-picks" name="max-picks" value="<?=$this->escapeForAttributes($choiceGroup['max_picks']);?>" step="1" min="0" style="width:3rem;" required>
		</div>
	    </div>
	    <div class="choice-option-container">
	    <?php foreach($choiceGroup['options'] ?? array() as $choiceID => $choice): ?>
		<div id="<?=$choiceID;?>-choice-option" class="choice-option">
		    <div class="input-shared-line">
			<button type="button" class="remove-option-button svg-button" id="<?=$choiceID;?>-remove-choice">
			    <svg xmlns="http://www.w3.org/2000/svg" fill="red" height="24" viewBox="0 0 24 24" width="24">
				<path d="M0 0h24v24H0z" fill="none"/>
				<path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/>
			    </svg>
			</button>
			<div class="input-container">
			    <label for="<?=$choiceID;?>-option-name">Name</label>
			    <input type="text" id="<?=$choiceID;?>-option-name" class="option-name" name="name" value="<?=$this->escapeForAttributes($choice['name']);?>" style="max-width:8rem;" required>
			</div>
			<div class="input-container">
			    <label for="<?=$choiceID;?>-option-price">Price Modifier</label>
			    <input type="number" id="<?=$choiceID;?>-option-price" class="option-price" name="price" value="<?=$this->intToCurrency($choice['price_modifier']);?>" step="0.01" min="0" max="99.99" style="width:4rem;" required>
			</div>
		    </div>
		</div>
	    <?php endforeach; ?>
	    </div>
	    <div class="input-shared-line">
		<button type="button" id="<?=$groupID;?>-remove-group" class="remove-group-button svg-button edit-order-button cancel">
		    Delete Choice
		</button>
		<button type="button" id="<?=$groupID;?>-add-option" class="add-option-button svg-button edit-order-button">
		    Add Option
		</button>
	    </div>
	</div>
    <?php endforeach; ?>
</div>

<script src="<?=$this->getFile('js', __FILE__);?>" type="module"></script>
<?php require APP_ROOT . "/views/includes/footer.php" ?>
