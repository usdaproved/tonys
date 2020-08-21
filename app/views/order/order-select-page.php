<?php require APP_ROOT . "/views/includes/header.php" ?>
<link href="<?=$this->getFile('css', 'components');?>" rel="stylesheet">
<link rel="stylesheet" type="text/css" href="<?= $this->getFile("css", __FILE__); ?>">
<?php $this->printOneTimeMessages(USER_ALERT); ?>
<?php $submitLinkHidden = NULL; ?>
<?php if(empty($this->orderStorage['line_items']) || (($this->orderStorage['order_type'] ?? NULL) === NULL)): ?>
    <?php $submitLinkHidden = "hidden"; ?>
<?php endif; ?>
<?php $orderTypeButtonsHidden = "hidden"; ?>
<?php $orderTypeSelectionHidden = NULL; ?>
<?php if(($this->orderStorage['order_type'] ?? NULL) === NULL): ?>
    <?php $orderTypeButtonsHidden = NULL; ?>
    <?php $orderTypeSelectionHidden = "hidden"; ?>
<?php endif; ?>
<?php $cartItemCount = 0;?>
<?php foreach($this->orderStorage["line_items"] ?? array() as $lineItem): ?>
    <?php $cartItemCount += $lineItem["quantity"]; ?>
<?php endforeach; ?>

<div class="scrolling-container shadow">
    <div id="order-action-bar">
	<div id="order-type-buttons" class="order-action order-type-buttons-container" <?=$orderTypeButtonsHidden?>>
	    <div class="order-type-button-container">
		<button id="order-type-delivery-button" class="svg-button order-type-button <?=$this->orderStorage['is_delivery_off'] ? 'inactive' : NULL?>" <?=$this->orderStorage['is_delivery_off'] ? 'disabled' : NULL?>>
		    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="white" width="24px" height="24px">
			<path d="M0 0h24v24H0z" fill="none"/>
			<path d="M18.92 6.01C18.72 5.42 18.16 5 17.5 5h-11c-.66 0-1.21.42-1.42 1.01L3 12v8c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h12v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-8l-2.08-5.99zM6.5 16c-.83 0-1.5-.67-1.5-1.5S5.67 13 6.5 13s1.5.67 1.5 1.5S7.33 16 6.5 16zm11 0c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zM5 11l1.5-4.5h11L19 11H5z"/>
		    </svg>
		    <span class="order-type-text">Delivery</span>
		</button>
	    </div>
	    <div class="order-type-button-container">
		<button id="order-type-pickup-button" class="svg-button order-type-button <?=$this->orderStorage['is_pickup_off'] ? 'inactive' : NULL?>" <?=$this->orderStorage['is_pickup_off'] ? 'disabled' : NULL?>>
		    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="white" width="24px" height="24px">
			<path d="M0 0h24v24H0z" fill="none"/>
			<path d="M13.49 5.48c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm-3.6 13.9l1-4.4 2.1 2v6h2v-7.5l-2.1-2 .6-3c1.3 1.5 3.3 2.5 5.5 2.5v-2c-1.9 0-3.5-1-4.3-2.4l-1-1.6c-.4-.6-1-1-1.7-1-.3 0-.5.1-.8.1l-5.2 2.2v4.7h2v-3.4l1.8-.7-1.6 8.1-4.9-1-.4 2 7 1.4z"/>
		    </svg>
		    <span class="order-type-text">Pickup</span>
		</button>
	    </div>
	    <?php if(($this->user['user_type'] ?? 0) >= EMPLOYEE): ?>
		<div class="order-type-button-container">
		    <button id="order-type-restaurant-button" class="svg-button order-type-button">
			<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="white" width="24px" height="24px">
			    <path d="M0 0h24v24H0z" fill="none"/>
			    <path d="M11 9H9V2H7v7H5V2H3v7c0 2.12 1.66 3.84 3.75 3.97V22h2.5v-9.03C11.34 12.84 13 11.12 13 9V2h-2v7zm5-3v8h2.5v8H21V2c-2.76 0-5 2.24-5 4z"/>
			</svg>
			<span class="order-type-text">Restaurant</span>
		    </button>
		</div>
	    <?php endif; ?>
	</div>

	<div class="order-action" id="order-type-selection" <?=$orderTypeSelectionHidden?>>
	    <button id="order-type-change-button" class="svg-button order-type-button">
		<svg id="delivery-selected-svg"xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="white" width="24px" height="24px" <?=((isset($this->orderStorage["order_type"])) && (($this->orderStorage["order_type"] ?? NULL) == DELIVERY)) ? NULL : 'hidden'?>>
		    <path d="M0 0h24v24H0z" fill="none"/>
		    <path d="M18.92 6.01C18.72 5.42 18.16 5 17.5 5h-11c-.66 0-1.21.42-1.42 1.01L3 12v8c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h12v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-8l-2.08-5.99zM6.5 16c-.83 0-1.5-.67-1.5-1.5S5.67 13 6.5 13s1.5.67 1.5 1.5S7.33 16 6.5 16zm11 0c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zM5 11l1.5-4.5h11L19 11H5z"/>
		</svg>
		<svg id="pickup-selected-svg" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="white" width="24px" height="24px" <?=(($this->orderStorage["order_type"] ?? NULL) == PICKUP) ? NULL : 'hidden'?>>
		    <path d="M0 0h24v24H0z" fill="none"/>
		    <path d="M13.49 5.48c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm-3.6 13.9l1-4.4 2.1 2v6h2v-7.5l-2.1-2 .6-3c1.3 1.5 3.3 2.5 5.5 2.5v-2c-1.9 0-3.5-1-4.3-2.4l-1-1.6c-.4-.6-1-1-1.7-1-.3 0-.5.1-.8.1l-5.2 2.2v4.7h2v-3.4l1.8-.7-1.6 8.1-4.9-1-.4 2 7 1.4z"/>
		</svg>
		<?php if(($this->user['user_type'] ?? 0) >= EMPLOYEE): ?>
		    <svg id="restaurant-selected-svg" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="white" width="24px" height="24px" <?=(($this->orderStorage["order_type"] ?? NULL) == IN_RESTAURANT) ? NULL : 'hidden'?>>
			<path d="M0 0h24v24H0z" fill="none"/>
			<path d="M11 9H9V2H7v7H5V2H3v7c0 2.12 1.66 3.84 3.75 3.97V22h2.5v-9.03C11.34 12.84 13 11.12 13 9V2h-2v7zm5-3v8h2.5v8H21V2c-2.76 0-5 2.24-5 4z"/>
		    </svg>
		<?php endif; ?>
	    </button>
	</div>

	<div class="order-action" id="submit-container" <?=$submitLinkHidden?>>
	    <?php $submitLink = "/Order/submit"; ?>
	    <?php if(($this->orderStorage["order_type"] ?? NULL) == IN_RESTAURANT): ?>
		<?php $submitLink = "/Dashboard/orders/submit";?>
	    <?php endif; ?>
	    <a href="<?=$submitLink?>" id="order-submit-link">
		Checkout <span id="order-subtotal">
		$<span id="order-subtotal-value"><?=$this->intToCurrency($this->orderStorage["subtotal"]);?></span>
		</span>
	    </a>
	</div>

	<div class="order-action" id="cart-button-container">
	    <button type="button" class="svg-button cart-button" id="cart-button">
		<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-shopping-bag">
		    <path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z">
		    </path>
		    <line x1="3" y1="6" x2="21" y2="6">
		    </line>
		    <path d="M16 10a4 4 0 0 1-8 0">
		    </path>
		</svg>
		<div id="cart-item-count-container">
		    <span id="cart-item-count" <?=$cartItemCount > 0 ? NULL : "hidden" ?>><?=$cartItemCount?></span>
		</div>
	    </button>
	    <div id="cart-container">
		<div class="shadow">
		    <div id="cart">
			<div class="cart-exit-button-container">
			    <button type="button" class="svg-button cart-exit-button" id="cart-exit-button">
				<svg class="navigation-svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
				    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
				</svg>
			    </button>
			</div>
			<div id="empty-cart-notice" <?=empty($this->orderStorage["line_items"]) ? NULL : 'hidden';?>>
			    <h3>Begin your order.</h3>
			</div>
			<?php // NOTE(Trystan): still call this even if empty as it sets up an empty container.?>
			<?=$this->formatCartForHTML($this->orderStorage)?>
		    </div>
		</div>
	    </div>
	</div>
    </div>
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
