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
    <?php if($this->hasUserInfo): ?>
	<strong>Name</strong>:
	<br>
	<?= $this->escapeForHTML($this->user['name_first'] ?? NULL) . " " . $this->escapeForHTML($this->user['name_last'] ?? NULL); ?>
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
	<form method="post">
	    <input type="hidden" name="CSRFToken" value="<?= $this->sessionManager->getCSRFToken(); ?>">
	    <input type="submit" value="Submit order">
	</form>
    <?php else: ?>
	<form method="post">
	    <label for="email">Confirmation email</label>
	    <input type="email" id="email" name="email" autocomplete="email" value="<?=$this->escapeForAttributes($this->user['email'] ?? NULL);?>" required>
	    <label for="name_first">First name</label>
	    <input type="text" id="name_first" name="name_first" autocomplete="given-name" value="<?=$this->escapeForAttributes($this->user['name_first'] ?? NULL);?>" required>
	    <label for="name_last">Last name</label>
	    <input type="text" id="name_last" name="name_last" autocomplete="family-name" value="<?=$this->escapeForAttributes($this->user['name_last'] ?? NULL);?>" required>
	    <label for="phone">Phone number</label>
	    <input type="text" id="phone" name="phone" autocomplete="tel" value="<?=$this->escapeForAttributes($this->user['phone_number'] ?? NULL);?>" required>
	    <label for="address_line">Street address</label>
	    <input type="text" id="address_line" name="address_line" autocomplete="street-address" value="<?=$this->escapeForAttributes($this->user['address']['line'] ?? NULL);?>" required>
	    <label for="city">City</label>
	    <input type="text" id="city" name="city" autocomplete="address-level2" value="<?=$this->escapeForAttributes($this->user['address']['city'] ?? NULL);?>" required>
	    <label for="state">State</label>
	    <input type="text" id="state" name="state"  autocomplete="address-level1" value="<?=$this->escapeForAttributes($this->user['address']['state'] ?? NULL);?>" required>
	    <label for="zip_code">Zip code</label>
	    <input type="text" id="zip_code" name="zip_code" autocomplete="postal-code" value="<?=$this->escapeForAttributes($this->user['address']['zip_code'] ?? NULL);?>" required>
	    <input type="hidden" name="CSRFToken" value="<?= $this->sessionManager->getCSRFToken(); ?>">
	    <input type="submit" value="Submit order">
	</form>
    <?php endif; ?>
</article>

<?php require APP_ROOT . "/views/includes/footer.php" ?>
