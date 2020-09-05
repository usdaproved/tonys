<?php require APP_ROOT . "/views/includes/header.php" ?>
<link href="<?=$this->getFile('css', 'components');?>" rel="stylesheet">
<link href="<?=$this->getFile('css', __FILE__);?>" rel="stylesheet">

<section class="width-full max-width-768 shadow text-form-inner-container">
    <div class="order-info">
	<?=$this->formatCartForHTML($this->orderStorage)?>
    </div>
    <div class="wide-button-container">
	<button class="wide-button svg-button" type="submit" id="submit-order-button">
	    Submit Order
	</button>
    </div>
    <div class="center-container margin-top-1 text-center">
	<p>Optionally, associate this order with a user by searching below.</p>
    </div>
</section>
    
<?=$this->searchUserComponent(true);?>

<input type="hidden" name="CSRFToken"  id="CSRFToken" value="<?= $this->sessionManager->getCSRFToken(); ?>">

<script src="<?=$this->getFile('js', __FILE__);?>" type="module"></script>
<?php require APP_ROOT . "/views/includes/footer.php" ?>
