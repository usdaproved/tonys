<?php require APP_ROOT . "/views/includes/header.php" ?>
<link href="<?=$this->getFile('css', 'components');?>" rel="stylesheet">
<link href="<?=$this->getFile('css', __FILE__);?>" rel="stylesheet">

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
	<div class="registered-only-container">
	    <label for="registered-only">Registered Users Only</label>
	    <input type="checkbox" id="registered-only">
	</div>
	<div class="wide-button-container">
	    <button type="button" class="wide-button svg-button" id="user-search-button">
		Search
	    </button>
	</div>
	<div id="user-table" class="search-result-container"></div>
    </div>
</div>

<input type="hidden" name="CSRFToken"  id="CSRFToken" value="<?= $this->sessionManager->getCSRFToken(); ?>">

<script src="<?=$this->getFile('js', __FILE__);?>" type="module"></script>
<?php require APP_ROOT . "/views/includes/footer.php" ?>
