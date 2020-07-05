<?php require APP_ROOT . "/views/includes/header.php" ?>
<link href="<?=$this->getFile('css', 'components');?>" rel="stylesheet">
<link rel="stylesheet" type="text/css" href="<?= $this->getFile("css", __FILE__); ?>">
<?php $this->printOneTimeMessages(USER_ALERT); ?>
<?php $submitLinkHidden = NULL; ?>
<?php if(empty($this->orderStorage['line_items']) || (($this->orderStorage['order_type'] ?? NULL) === NULL)): ?>
    <?php $submitLinkHidden = "hidden"; ?>
<?php endif; ?>
<div id="submit-container" <?=$submitLinkHidden?>>
    <?php $submitLink = "/Order/submit"; ?>
    <?php if(($this->orderStorage["order_type"] ?? NULL) == IN_RESTAURANT): ?>
	<?php $submitLink = "/Dashboard/orders/submit";?>
    <?php endif; ?>
    <a href="<?=$submitLink?>" id="order-submit-link">Submit Order</a>
</div>
<?php $orderTypeButtonsHidden = "hidden"; ?>
<?php $orderTypeSelectionHidden = NULL; ?>
<?php if(($this->orderStorage['order_type'] ?? NULL) === NULL): ?>
    <?php $orderTypeButtonsHidden = NULL; ?>
    <?php $orderTypeSelectionHidden = "hidden"; ?>
<?php endif; ?>
<?php if($this->orderStorage['is_closed']):?>
    <h3 class="notice">Not currently accepting orders</h3>
<?php else: ?>
    <?php if($this->orderStorage['is_delivery_off']): ?>
	<h3 class="notice">Not currently accepting orders for delivery</h3>
    <?php elseif($this->orderStorage['is_pickup_off']): ?>
	<h3 class="notice">Not currently accepting orders for pickup</h3>
    <?php endif; ?>
<?php endif; ?>
<div id="order-type-buttons" <?=$orderTypeButtonsHidden?>>
    <button id="order-type-delivery-button" <?=$this->orderStorage['is_delivery_off'] ? 'hidden' : NULL?>>Delivery</button>
    <button id="order-type-pickup-button" <?=$this->orderStorage['is_pickup_off'] ? 'hidden' : NULL?>>Pickup</button>
    <?php if(($this->user['user_type'] ?? 0) >= EMPLOYEE): ?>
	<button id="order-type-restaurant-button">Restaurant</button>
    <?php endif; ?>
</div>
<div id="order-type-selection" <?=$orderTypeSelectionHidden?>>
    Order type: <span id="order-type-text"><?=ucfirst(ORDER_TYPE_ARRAY[$this->orderStorage['order_type'] ?? 0])?></span>
    <button id="order-type-change-button">Change</button>
</div>

<?php $cartItemCount = 0;?>
<?php foreach($this->orderStorage["line_items"] ?? array() as $lineItem): ?>
    <?php $cartItemCount += $lineItem["quantity"]; ?>
<?php endforeach; ?>

<div id="cart-button" <?=$cartItemCount > 0 ? NULL : "hidden" ?>>
    Cart Items <span id="cart-item-count"><?=$cartItemCount?></span>
</div>
<div id="cart-container" hidden>
    <?=$this->formatOrderForHTML($this->orderStorage)?>
</div>

<?php foreach($this->menuStorage as $category): ?>
    <h3 class="category-name">
	<?=$this->escapeForHTML($category['name']);?>
    </h3>
    <div class="orders-container">
	<?php foreach($category['items'] as $item): ?>
	    <?php $inactive = (($item['active'] != 1) || $this->orderStorage['is_closed']) ? 'inactive' : NULL; ?>
	    <div class="order-container <?=$inactive?>" id="<?=$item['id']?>-item-container">
		<?php if(!$inactive): ?>
		    <a href="/Order?id=<?=$item['id']?>" rel="nofollow" class="item-link">
		<?php endif; ?>
		    <div class="item-info-container">
			<div class="item-name">
			    <?=$this->escapeForHTML($item['name']);?>
			</div>
			<div class="item-description">
			    <?=$this->escapeForHTML($item['description']);?>
			</div>
			<div class="item-price">
			    <?='$' . $this->intToCurrency($item['price']);?>
			</div>
		    </div>
		<?php if($item['active'] == 1): ?>
		    </a>
		<?php endif; ?>
	    </div>
	<?php endforeach; ?>
    </div>
<?php endforeach; ?>

<input type="hidden" name="CSRFToken" id="CSRFToken" value="<?= $this->sessionManager->getCSRFToken(); ?>">

<script src="<?=$this->getFile('js', __FILE__);?>" type="module"></script>
<?php require APP_ROOT . "/views/includes/footer.php" ?>
