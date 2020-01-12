<?php require APP_ROOT . "/views/includes/header.php" ?>

<link href="<?= $this->getFile("css", __FILE__); ?>" rel="stylesheet">

<?php $this->printOneTimeMessages(USER_ALERT); ?>
<article>
    <h1>Cart</h1>
    <p>
	<ul>
	    <?php foreach($this->orderStorage['line_items'] as $lineItem): ?>
		<li><?= $this->escapeForHTML($lineItem['name']) . ' ' . $lineItem['quantity']; ?></li>
	    <?php endforeach; ?>
	</ul>
    </p>
    <p><strong>Subtotal</strong>: <?= "$" . $this->orderStorage['subtotal']; ?></p>
    <h3>Personal information</h3>
    <strong>Name</strong>:
    <br>
    <span id="name_first">
	<?= $this->escapeForHTML($this->user['name_first'] ?? NULL) ?>
    </span>
    <span id="name_last">
	<?= $this->escapeForHTML($this->user['name_last'] ?? NULL); ?>
    </span>
    <br>
    <strong>Email</strong>:
    <br>
    <?= $this->escapeForHTML($this->user['email'] ?? NULL); ?>
    <br>
    <strong>Phone number</strong>:
    <br>
    <?= $this->escapeForHTML($this->user['phone_number'] ?? NULL); ?>
    <br>
    <strong>Address</strong>:
    <br>
    <?= $this->formatAddressForHTML($this->user['address'] ?? NULL); ?>

    <div id="stripe-card-element">
	<!-- Elements will create input elements here -->
    </div>

    <!-- We'll put the error messages in this element -->
    <div id="stripe-card-errors" role="alert"></div>

    <input type="hidden" id="CSRFToken" name="CSRFToken" value="<?= $this->sessionManager->getCSRFToken(); ?>">
    <button id="stripe-payment-submit" data-secret="<?=$this->user['stripe_client_secret']?>" data-orderid="<?=$this->orderStorage['id']?>">Pay</button>

</article>

<script src="https://js.stripe.com/v3/"></script>
<script src="<?=$this->getFile('js', __FILE__);?>"></script>
<?php require APP_ROOT . "/views/includes/footer.php" ?>
