<?php require APP_ROOT . "/views/includes/header.php" ?>
<link href="<?=$this->getFile('css', 'components');?>" rel="stylesheet">
<link href="<?=$this->getFile('css', __FILE__);?>" rel="stylesheet">

<div class="center-container">
    <div class="shadow text-form-inner-container">
	<div class="text-form-header">
	    Delivery Schedule
	</div>
	<div class="schedule-container" id="delivery-schedule">
	    <?php foreach($settings["delivery_schedule"] as $day): ?>
		<div id="<?=$day['day']?>-day" class="day-container">
		    <h3><?=$week[$day['day']]?></h3>
		    <div class="input-shared-line">
			<div class="input-container">
			    <label for="<?=$day['day']?>-start-time">Start</label>
			    <input type="time" id="<?=$day['day']?>-start-time" value="<?=$day['start_time']?>">
			</div>
			<div class="input-container">
			    <label for="<?=$day['day']?>-end-time">End</label>
			    <input type="time" id="<?=$day['day']?>-end-time" value="<?=$day['end_time']?>">
			</div>
		    </div>
		</div>
	    <?php endforeach; ?>
	</div>
	<div class="wide-button-container">
	    <button type="submit" id="delivery-submit" class="svg-button wide-button">
		Update
	    </button>
	</div>
    </div>
</div>

<div class="center-container">
    <div class="shadow text-form-inner-container">
	<div class="text-form-header">
	    Pickup Schedule
	</div>
	<div class="schedule-container" id="pickup-schedule">
	    <?php foreach($settings["pickup_schedule"] as $day): ?>
		<div id="<?=$day['day']?>-day" class="day-container">
		    <h3><?=$week[$day['day']]?></h3>
		    <div class="input-shared-line">
			<div class="input-container">
			    <label for="<?=$day['day']?>-start-time">Start Time:</label>
			    <input type="time" id="<?=$day['day']?>-start-time" value="<?=$day['start_time']?>">
			</div>
			<div class="input-container">
			    <label for="<?=$day['day']?>-end-time">End Time:</label>
			    <input type="time" id="<?=$day['day']?>-end-time" value="<?=$day['end_time']?>">
			</div>
		    </div>
		</div>
	    <?php endforeach; ?>
	</div>
	<button type="submit" id="pickup-submit" class="svg-button wide-button">
	    Update
	</button>
    </div>
</div>


<input type="hidden" name="CSRFToken" id="CSRFToken" value="<?= $this->sessionManager->getCSRFToken(); ?>">

<script src="<?=$this->getFile('js', __FILE__);?>" type="module"></script>
<?php require APP_ROOT . "/views/includes/footer.php" ?>
