<?php require APP_ROOT . "/views/includes/header.php" ?>
<link href="<?=$this->getFile('css', 'components');?>" rel="stylesheet">
<link href="<?=$this->getFile('css', __FILE__);?>" rel="stylesheet">

<section class="width-full max-width-768 shadow text-form-inner-container">
    <div class="center-container">
	<div class="customer-name" id="customer-name-text">
	    No customer selected.
	</div>
    </div>
    <div class="order-info">
	<?=$this->formatCartForHTML($this->orderStorage)?>
    </div>
    <div class="wide-button-container">
	<button class="wide-button svg-button" type="submit" id="submit-order-button">
	    Submit Order
	</button>
    </div>
    <div class="center-container margin-top-1 text-center">
	<p>Optionally, associate this order with a customer by searching below.</p>
    </div>
</section>

<div class="center-container">
    <div id="search-filters" class="shadow text-form-inner-container">
	<div class="text-form-header">
	    Search Customers
	</div>
	<div class="text-form-subheader-container">
	    <div class="center-container">
		Use as many filters as necessary.
	    </div>
	</div>
	<div class="input-container">
	    <label for="name_first">First Name</label>
	    <input type="text" id="name_first" autocomplete="off">
	</div>
	<div class="input-container">
	    <label for="name_last">Last Name</label>
	    <input type="text" id="name_last" autocomplete="off">
	</div>
	<div class="input-container">
	    <label for="email">Email</label>
	    <input type="email" id="email" autocomplete="off">
	</div>
	<div class="input-container">
	    <label for="phone_number">Phone Number</label>
	    <input type="text" id="phone_number" autocomplete="off">
	</div>
	<input type="checkbox" id="registered-only" checked disabled hidden>
	<div class="wide-button-container">
	    <button type="button" class="wide-button svg-button" id="user-search-button">
		Search
	    </button>
	</div>
	<div class="center-container margin-top-1">
	    Registered users only.
	</div>
	<div id="user-table" class="search-result-container"></div>
    </div>
</div>

<input type="hidden" name="CSRFToken"  id="CSRFToken" value="<?= $this->sessionManager->getCSRFToken(); ?>">

<script src="<?=$this->getFile('js', __FILE__);?>" type="module"></script>
<?php require APP_ROOT . "/views/includes/footer.php" ?>
