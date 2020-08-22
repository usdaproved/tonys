<?php require APP_ROOT . "/views/includes/header.php" ?>
<link href="<?=$this->getFile('css', 'components');?>" rel="stylesheet">
<link href="<?= $this->getFile('css', __FILE__); ?>" rel="stylesheet">

<?php $this->printOneTimeMessages(USER_ALERT); ?>

<div class="hero-container">
    <div class="hero-text-container">
	<div class="hero-text">
	    <svg class="slanted-separator" fill="currentColor" viewBox="0 0 100 100" preserveAspectRatio="none">
		<polygon class="slanted-shadow" points="0,0 100,0 50,100 0,100" />
	    </svg>
	    <main class="main-container">
		<div class="main-header-container">
		    <h2 class="main-header">
			Place an order for <span class="blue-text">Pickup</span> or <span class="blue-text">Delivery</span>.
		    </h2>
		    <div class="header-link-button-container">
			<div class="header-link-button">
			    <a href="/Order" class="header-link">
				Get started
			    </a>
			</div>
		    </div>
		</div>
	    </main>
	</div>
    </div>
    <div class="hero-image-container">
	<img class="hero-image" src="/image/home/hero.jpg" alt="restaurant-exterior">
    </div>
</div>
<div class="info-schedule-container">
    <div class="info-container">
	<div class="info-layout-container">
	<div class="address-container">
	    <div class="address-line"> 7528 Cedarwood St</div>
	    <div class="address-line">Carter Lake, IA 51510</div>
	</div>
	<div class="phone-number-container">
	    <a href="tel:202-555-0166" class="phone-number">202.555.0166</a>
	</div>
	</div>
    </div>
    <div class="section-container">
	<div class="schedules-container">
	    <h3 class="schedule-header">Delivery Schedule</h3>
	    <?php foreach($schedule["delivery"] as $day): ?>
		<div class="schedule-line">
		    <span class="schedule-day blue-text"><?=$week[$day['day']]?></span>
		    <span class="schedule-time">
			<?=date('g:i A', strtotime($day['start_time'])) . ' - ' . date('g:i A', strtotime($day['end_time']))?>
		    </span>
		</div>
	    <?php endforeach; ?>
	    <h3 class="schedule-header">Pickup Schedule</h3>
	    <?php foreach($schedule["pickup"] as $day): ?>
		<div class="schedule-line">
		    <span class="schedule-day blue-text"><?=$week[$day['day']]?></span>
		    <span class="schedule-time">
			<?=date('g:i A', strtotime($day['start_time'])) . ' - ' . date('g:i A', strtotime($day['end_time']))?>
		    </span>
		</div>
	    <?php endforeach; ?>
	</div>
    </div>
</div>

<?php require APP_ROOT . "/views/includes/footer.php" ?>
