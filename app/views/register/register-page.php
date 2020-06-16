<?php require APP_ROOT . "/views/includes/header.php" ?>
<link href="<?= $this->getFile("css", __FILE__); ?>" rel="stylesheet">
<?php $this->printOneTimeMessages(USER_ALERT); ?>
<form method="post">
    
    <label for="email">Email</label>
    <input type="email" id="email" name="email" value="<?=$this->escapeForAttributes($this->user["email"] ?? NULL);?>" autocomplete="email" required>
    <label for="password">Password</label>
    <input type="password" id="password" name="password" autocomplete="new-password" required>
    <label for="name_first">First name</label>
    <input type="text" id="name_first" name="name_first" value="<?=$this->escapeForAttributes($this->user["name_first"] ?? NULL);?>" autocomplete="given-name" required>
    <label for="name_last">Last name</label>
    <input type="text" id="name_last" name="name_last" value="<?=$this->escapeForAttributes($this->user["name_last"] ?? NULL);?>" autocomplete="family-name" required>
    <label for="phone">Phone number</label>
    <input type="text" id="phone" name="phone" value="<?=$this->escapeForAttributes($this->user["phone_number"] ?? NULL);?>" autocomplete="tel" required>
    <label for="address_line">Street address</label>
    <input type="text" id="address_line" name="address_line" value="<?=$this->escapeForAttributes($this->user["address"]["line"] ?? NULL);?>" autocomplete="street-address" required>
    <label for="city">City</label>
    <input type="text" id="city" name="city" value="<?=$this->escapeForAttributes($this->user["address"]["city"] ?? NULL);?>" autocomplete="address-level2" required>
    <label for="state">State</label>
    <input type="text" id="state" name="state" minlength="2" maxlength="2" value="<?=$this->escapeForAttributes($this->user["address"]["state"] ?? NULL);?>" autocomplete="address-level1" required>
    <label for="zip_code">Zip code</label>
    <input type="text" id="zip_code" name="zip_code" value="<?=$this->escapeForAttributes($this->user["address"]["zip_code"] ?? NULL);?>" autocomplete="postal-code" required>
    <input type="hidden" name="CSRFToken" value="<?= $this->sessionManager->getCSRFToken(); ?>">
    <input type="submit" value="Register">
</form>

<?php require APP_ROOT . "/views/includes/footer.php" ?>
