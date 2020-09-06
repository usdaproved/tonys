<?php require APP_ROOT . "/views/includes/header.php" ?>
<link href="<?=$this->getFile('css', 'components');?>" rel="stylesheet">
<link href="<?=$this->getFile('css', __FILE__);?>" rel="stylesheet">

<div class="scrolling-container shadow">
    <div class="action-bar">
	<div id="order-type-filters">
	    <button id="view-delivery" class="svg-button order-type-button">
		<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="white" width="24px" height="24px">
		    <path d="M0 0h24v24H0z" fill="none"/>
		    <path d="M18.92 6.01C18.72 5.42 18.16 5 17.5 5h-11c-.66 0-1.21.42-1.42 1.01L3 12v8c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h12v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-8l-2.08-5.99zM6.5 16c-.83 0-1.5-.67-1.5-1.5S5.67 13 6.5 13s1.5.67 1.5 1.5S7.33 16 6.5 16zm11 0c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zM5 11l1.5-4.5h11L19 11H5z"/>
		</svg>
	    </button>
	    <button id="view-pickup" class="svg-button order-type-button">
		<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="white" width="24px" height="24px">
		    <path d="M0 0h24v24H0z" fill="none"/>
		    <path d="M13.49 5.48c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm-3.6 13.9l1-4.4 2.1 2v6h2v-7.5l-2.1-2 .6-3c1.3 1.5 3.3 2.5 5.5 2.5v-2c-1.9 0-3.5-1-4.3-2.4l-1-1.6c-.4-.6-1-1-1.7-1-.3 0-.5.1-.8.1l-5.2 2.2v4.7h2v-3.4l1.8-.7-1.6 8.1-4.9-1-.4 2 7 1.4z"/>
		</svg>
	    </button>
	    <button id="view-in-restaurant" class="svg-button order-type-button">
		<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="white" width="24px" height="24px">
		    <path d="M0 0h24v24H0z" fill="none"/>
		    <path d="M11 9H9V2H7v7H5V2H3v7c0 2.12 1.66 3.84 3.75 3.97V22h2.5v-9.03C11.34 12.84 13 11.12 13 9V2h-2v7zm5-3v8h2.5v8H21V2c-2.76 0-5 2.24-5 4z"/>
		</svg>
	    </button>
	</div>
	<div class="update-status-button-container">
	    <button type="button" id="update-status-button" class="svg-button update-status-button inactive" disabled>
		Update status
	    </button>
	</div>
    </div>
</div>
<h3 id="order-type-name-delivery" class="text-center order-type-name">
    DELIVERY:
</h3>
<div id="delivery-orders" class="center-container active-order-container">
</div>
<h3 id="order-type-name-pickup" class="text-center order-type-name">
    PICKUP:
</h3>
<div id="pickup-orders" class="center-container active-order-container">
</div>
<h3 id="order-type-name-in-restaurant" class="text-center order-type-name">
    IN RESTAURANT:
</h3>
<div id="in-restaurant-orders" class="center-container active-order-container">
</div>
<input type="hidden" name="CSRFToken"  id="CSRFToken" value="<?= $this->sessionManager->getCSRFToken(); ?>">

<script src="<?=$this->getFile('js', __FILE__);?>" type="module"></script>
<?php require APP_ROOT . "/views/includes/footer.php" ?>
