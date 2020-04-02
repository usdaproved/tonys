<?php require APP_ROOT . "/views/includes/header.php" ?>
<link rel="stylesheet" type="text/css" href="<?= $this->getFile("css", __FILE__); ?>">
<header>Tony's Taco House</header>
<?php $submitLinkHidden = NULL; ?>
<?php if(empty($this->orderStorage['line_items']) || (($this->orderStorage['order_type'] ?? NULL) === NULL)): ?>
    <?php $submitLinkHidden = "hidden"; ?>
<?php endif; ?>
<div id="submit-container" <?=$submitLinkHidden?>>
    <?php $submitLink = "/Order/submit"; ?>
    <?php if($this->orderStorage["order_type"] ?? NULL == IN_RESTAURANT): ?>
	<?php $submitLink = "/Dashboard/orders/submit";?>
    <?php endif; ?>
    <a href="<?=$submitLink?>" id="order-submit-link">Submit Order</a>
</div>
<?php $this->printOneTimeMessages(USER_ALERT); ?>
<?php $orderTypeButtonsHidden = "hidden"; ?>
<?php $orderTypeSelectionHidden = NULL; ?>
<?php if(($this->orderStorage['order_type'] ?? NULL) === NULL): ?>
    <?php $orderTypeButtonsHidden = NULL; ?>
    <?php $orderTypeSelectionHidden = "hidden"; ?>
<?php endif; ?>
<div id="order-type-buttons" <?=$orderTypeButtonsHidden?>>
    Select an option:
    
    <button id="order-type-delivery-button">Delivery</button>
    <button id="order-type-pickup-button">Pickup</button>
    <?php if(($this->user['user_type'] ?? 0) >= EMPLOYEE): ?>
	<button id="order-type-restaurant-button">Restaurant</button>
    <?php endif; ?>
</div>
<div id="order-type-selection" <?=$orderTypeSelectionHidden?>>
    Order type: <span id="order-type-text"><?=ucfirst(ORDER_TYPE_ARRAY[$this->orderStorage['order_type'] ?? 0])?></span>
    <button id="order-type-change-button">Change</button>
</div>

<?php $cartItemCount = 0;?>
<?php foreach($this->orderStorage["line_items"] as $lineItem): ?>
    <?php $cartItemCount += $lineItem["quantity"]; ?>
<?php endforeach; ?>

<div id="cart-button" <?=$cartItemCount > 0 ? NULL : "hidden" ?>>
    Cart Items <span id="cart-item-count"><?=$cartItemCount?></span>
</div>
<div id="cart-container" hidden>
    <?=$this->formatOrderForHTML($this->orderStorage)?>
</div>

<?php foreach($this->menuStorage ?? array() as $category): ?>
    <h3 class="category-name">
	<?=$this->escapeForHTML($category['name'] ?? NULL);?>
    </h3>
    <div class="category-container">
	<!-- TODO(Trystan): Try to remember why we are doing these null coalesces. -->
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
			    <?='$' . $this->intToCurrency($item['price']);?>
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
