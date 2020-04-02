<?php require APP_ROOT . "/views/includes/header.php" ?>
<link href="<?=$this->getFile('css', __FILE__);?>" rel="stylesheet">
<header>Tony's Taco House</header>
<a href="/Dashboard">Dashboard</a>
<br>

<p>Optional: associate this order with a customer.</p>
<div id="search-filters">
    <label for="name_first">First Name: </label>
    <input type="text"  id="name_first" autocomplete="off">
    <label for="name_last">Last Name: </label>
    <input type="text"  id="name_last" autocomplete="off">
    <label for="email">Email: </label>
    <input type="email" id="email"  autocomplete="off">
    <label for="phone_number">Phone Number: </label>
    <input type="text" id="phone_number" autocomplete="off">
</div>
<input type="submit" id="customer-search-button" value="Search">

<form id="user-ids">
    
    <input type="radio" id="non-customer-select" name="user_id" value="null" checked>
    <span>Noncustomer</span>
    <div id="user-table">
    </div>
</form>

<input type="submit" id="submit-order-button" value="Submit Order">

<input type="hidden" name="CSRFToken"  id="CSRFToken" value="<?= $this->sessionManager->getCSRFToken(); ?>">

<script src="<?=$this->getFile('js', __FILE__);?>" type="module"></script>
<?php require APP_ROOT . "/views/includes/footer.php" ?>
