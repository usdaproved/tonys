<?php require APP_ROOT . "/views/includes/header.php" ?>
<link href="<?=$this->getFile('css', 'components');?>" rel="stylesheet">
<link href="<?=$this->getFile('css', __FILE__);?>" rel="stylesheet">

<?php if(!empty($orders)): ?>
    <div class="user-orders">
	<?php foreach($orders as $order): ?>
	    <div class="shadow user-order<?=$order['status'] == COMPLETE ? NULL : ' active'?>">
		<div class="border-wrapper">
		    <div class="">
			<?=date("F d, Y g:i A", strtotime($order["date"]))?>
			
			<?=$this->formatBasicOrderForHTML($order)?>
			<div class="total">
			    <?='Total: $' . $this->intToCurrency($order["cost"]["total"])?>
			</div>
			<a href="/Order/status?order=<?=UUID::orderedBytesToArrangedString($order['uuid'])?>" class="status-link short-button no-text-decorate">
			    <?php if($order['status'] != COMPLETE): ?>
				<svg viewBox="0 0 100 100" height="16" width="16" id="svg-active-circle" class="blinking">
				    <circle cx="50%" cy="50%" r="50" fill="red"></circle>
				</svg>
				<span class="status-text">
				    View Status
				</span>
			    <?php else: ?>
				View Details
			    <?php endif; ?>
			</a>
			
		    </div>
		</div>
	    </div>
	<?php endforeach; ?>
    </div>
<?php else: ?>
    <div class="center-container">
	<div class="shadow text-form-inner-container">
	    <div class="text-form-header">
		No orders to show yet.
	    </div>
	</div>
    </div>
<?php endif; ?>

<?php require APP_ROOT . "/views/includes/footer.php" ?>
