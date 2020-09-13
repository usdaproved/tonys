<?php require APP_ROOT . "/views/includes/header.php" ?>
<link href="<?=$this->getFile('css', 'components');?>" rel="stylesheet">
<link href="<?=$this->getFile('css', __FILE__);?>" rel="stylesheet">
<div class="center-container">
    <div id="search-filters" class="shadow text-form-inner-container">
	<div class="text-form-header">
	    Search Orders
	</div>
	<div class="text-form-subheader-container">
	    <div class="center-container">
		Use as many filters as necessary.
	    </div>
	</div>
	<div class="input-shared-line">
	    <div class="input-container">
		<label for="start_date">Start Date</label>
		<input type="date" id="start_date" autocomplete="off">
	    </div>
	    <div class="input-container">
		<label for="end_date">End Date</label>
		<input type="date" id="end_date" autocomplete="off">
	    </div>
	</div>
	<div class="input-shared-line">
	    <div class="input-container">
		<label for="start_amount">Start Amount</label>
		<input type="number" step="0.01" id="start_amount" autocomplete="off" style="width: 5rem;">
	    </div>
	    <div class="input-container">
		<label for="end_amount">End Amount</label>
		<input type="number" step="0.01" id="end_amount" autocomplete="off" style="width: 5rem;">
	    </div>
	</div>
	<div class="input-container">
	    <label for="name_first">First Name</label>
	    <input type="text"  id="name_first" autocomplete="off">
	</div>
	<div class="input-container">
	    <label for="name_last">Last Name</label>
	    <input type="text"  id="name_last" autocomplete="off">
	</div>
	<div class="input-container">
	    <label for="email">Email</label>
	    <input type="email" id="email"  autocomplete="off">
	</div>
	<div class="input-container">
	    <label for="phone_number">Phone Number</label>
	    <input type="text" id="phone_number" autocomplete="off">
	</div>
	<div class="order-type-container center-container" id="order-type-container">
	    <button type="button" class="svg-button order-type-button inactive" id="checkbox-delivery">
		<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="white" width="24px" height="24px">
		    <path d="M0 0h24v24H0z" fill="none"/>
		    <path d="M18.92 6.01C18.72 5.42 18.16 5 17.5 5h-11c-.66 0-1.21.42-1.42 1.01L3 12v8c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-1h12v1c0 .55.45 1 1 1h1c.55 0 1-.45 1-1v-8l-2.08-5.99zM6.5 16c-.83 0-1.5-.67-1.5-1.5S5.67 13 6.5 13s1.5.67 1.5 1.5S7.33 16 6.5 16zm11 0c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5zM5 11l1.5-4.5h11L19 11H5z"/>
		</svg>
		<span class="order-type-text">Delivery</span>
	    </button>
	    <button type="button" class="svg-button order-type-button inactive" id="checkbox-pickup">
		<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="white" width="24px" height="24px">
		    <path d="M0 0h24v24H0z" fill="none"/>
		    <path d="M13.49 5.48c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm-3.6 13.9l1-4.4 2.1 2v6h2v-7.5l-2.1-2 .6-3c1.3 1.5 3.3 2.5 5.5 2.5v-2c-1.9 0-3.5-1-4.3-2.4l-1-1.6c-.4-.6-1-1-1.7-1-.3 0-.5.1-.8.1l-5.2 2.2v4.7h2v-3.4l1.8-.7-1.6 8.1-4.9-1-.4 2 7 1.4z"/>
		</svg>
		<span class="order-type-text">Pickup</span>
	    </button>
	    <button type="button" class="svg-button order-type-button inactive" id="checkbox-in-restaurant">
		<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="white" width="24px" height="24px">
		    <path d="M0 0h24v24H0z" fill="none"/>
		    <path d="M11 9H9V2H7v7H5V2H3v7c0 2.12 1.66 3.84 3.75 3.97V22h2.5v-9.03C11.34 12.84 13 11.12 13 9V2h-2v7zm5-3v8h2.5v8H21V2c-2.76 0-5 2.24-5 4z"/>
		</svg>
		<span class="order-type-text">Restaurant</span>
	    </button>
	</div>
	<div class="wide-button-container">
	    <button type="button" class="wide-button svg-button" id="order-search-button">
		Search
	    </button>
	</div>
	<div class="center-container margin-top-1">
	    Completed orders only.
	</div>
	<div id="order-table" class="search-result-container"></div>
    </div>
</div>

<input type="hidden" name="CSRFToken"  id="CSRFToken" value="<?= $this->sessionManager->getCSRFToken(); ?>">

<script src="<?=$this->getFile('js', __FILE__);?>" type="module"></script>
<?php require APP_ROOT . "/views/includes/footer.php" ?>
