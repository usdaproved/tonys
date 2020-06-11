<?php require APP_ROOT . "/views/includes/header.php" ?>
<link href="<?=$this->getFile('css', 'components');?>" rel="stylesheet">
<link href="<?=$this->getFile('css', __FILE__);?>" rel="stylesheet">
<p>Use as many filters as necessary.</p>
<div id="search-filters">
    <!-- Perhaps make some toggle to search ON date and BETWEEN dates -->
    <!-- Same would go for amount. -->
    <label for="start_date">Start Date: </label>
    <input type="date" id="start_date" autocomplete="off">
    <label for="end_date">End Date: </label>
    <input type="date" id="end_date" autocomplete="off">
    <label for="start_amount">Start Amount: </label>
    <input type="number" step="0.01" id="start_amount" autocomplete="off">
    <label for="end_amount">End Amount: </label>
    <input type="number" step="0.01" id="end_amount" autocomplete="off">
    <label for="name_first">First Name: </label>
    <input type="text"  id="name_first" autocomplete="off">
    <label for="name_last">Last Name: </label>
    <input type="text"  id="name_last" autocomplete="off">
    <label for="email">Email: </label>
    <input type="email" id="email"  autocomplete="off">
    <label for="phone_number">Phone Number: </label>
    <input type="text" id="phone_number" autocomplete="off">
    <div id="order-type-container">
	<label for="checkbox-delivery">Delivery: </label>
	<input type="checkbox" id="checkbox-delivery" name="order_type" value="0">
	<label for="checkbox-pickup">Pickup: </label>
	<input type="checkbox" id="checkbox-pickup" name="order_type" value="1">
	<label for="checkbox-in-restaurant">Restaurant: </label>
	<input type="checkbox" id="checkbox-in-restaurant" name="order_type" value="2">
    </div>
</div>
<input type="submit" id="order-search-button" value="Search">

<div id="order-table" class="orders-container">
</div>

<input type="hidden" name="CSRFToken"  id="CSRFToken" value="<?= $this->sessionManager->getCSRFToken(); ?>">

<script src="<?=$this->getFile('js', __FILE__);?>" type="module"></script>
<?php require APP_ROOT . "/views/includes/footer.php" ?>
