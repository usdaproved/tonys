<?php require APP_ROOT . "/views/includes/header.php" ?>
<link href="<?=$this->getFile('css', 'components');?>" rel="stylesheet">
<link href="<?=$this->getFile('css', __FILE__);?>" rel="stylesheet">
<header>Tony's Taco House</header>
<a href="/Dashboard">Dashboard</a>
<br>
<div id="order-type-filters">
    Filter Orders:
    <label for="view-delivery">Delivery</label>
    <input type="checkbox" id="view-delivery" checked>
    <label for="view-pickup">Pickup</label>
    <input type="checkbox" id="view-pickup" checked>
    <label for="view-in-restaurant">Restaurant</label>
    <input type="checkbox" id="view-in-restaurant" checked>
</div>
<input type="submit" id="update-status-button" value="Update status">
<h3 id="order-type-name-delivery" class="order-type-name">
    DELIVERY:
</h3>
<div id="delivery-orders" class="orders-container">
</div>
<h3 id="order-type-name-pickup" class="order-type-name">
    PICKUP:
</h3>
<div id="pickup-orders" class="orders-container">
</div>
<h3 id="order-type-name-in-restaurant" class="order-type-name">
    IN RESTAURANT:
</h3>
<div id="in-restaurant-orders" class="orders-container">
</div>
<input type="hidden" name="CSRFToken"  id="CSRFToken" value="<?= $this->sessionManager->getCSRFToken(); ?>">

<script src="<?=$this->getFile('js', __FILE__);?>" type="module"></script>
<?php require APP_ROOT . "/views/includes/footer.php" ?>
