<?php require APP_ROOT . "/views/includes/header.php" ?>
<link href="<?=$this->getFile('css', 'components');?>" rel="stylesheet">
<link href="<?=$this->getFile('css', __FILE__);?>" rel="stylesheet">
<label for="delivery-on">Delivery Status: </label>
<input type="checkbox" id="delivery-on" <?=($settings['delivery_on'] == 1) ? 'checked' : NULL?>>
<label for="pickup-on">Pickup Status: </label>
<input type="checkbox" id="pickup-on" <?=($settings['pickup_on'] == 1) ? 'checked' : NULL?>>

<h3>Delivery Schedule</h3>
<input type="submit" id="delivery-submit" value="Update Delivery">
<div class="orders-container" id="delivery-schedule">
    <?php foreach($settings["delivery_schedule"] as $day): ?>
	<div id="<?=$day['day']?>-day" class="order-container">
	    <h4><?=$week[$day['day']]?></h4>
	    <label for="<?=$day['day']?>-start-time">Start Time:</label>
	    <input type="time" id="<?=$day['day']?>-start-time" value="<?=$day['start_time']?>">
	    <label for="<?=$day['day']?>-end-time">End Time:</label>
	    <input type="time" id="<?=$day['day']?>-end-time" value="<?=$day['end_time']?>">
	</div>
    <?php endforeach; ?>
</div>

<h3>Pickup Schedule</h3>
<input type="submit" id="pickup-submit" value="Update Pickup">
<div class="orders-container" id="pickup-schedule">
    <?php foreach($settings["pickup_schedule"] as $day): ?>
	<div id="<?=$day['day']?>-day" class="order-container">
	    <h4><?=$week[$day['day']]?></h4>
	    <label for="<?=$day['day']?>-start-time">Start Time:</label>
	    <input type="time" id="<?=$day['day']?>-start-time" value="<?=$day['start_time']?>">
	    <label for="<?=$day['day']?>-end-time">End Time:</label>
	    <input type="time" id="<?=$day['day']?>-end-time" value="<?=$day['end_time']?>">
	</div>
    <?php endforeach; ?>
</div>

<input type="hidden" name="CSRFToken" id="CSRFToken" value="<?= $this->sessionManager->getCSRFToken(); ?>">

<script src="<?=$this->getFile('js', __FILE__);?>" type="module"></script>
<?php require APP_ROOT . "/views/includes/footer.php" ?>
