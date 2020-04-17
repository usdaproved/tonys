<?php require APP_ROOT . "/views/includes/header.php" ?>
<link href="<?=$this->getFile('css', __FILE__);?>" rel="stylesheet">
<header>Tony's Taco House</header>
<a href="/">Home</a>

<label for="email">Email</label>
<input type="email" id="email" name="email" value="<?=$this->escapeForAttributes($this->user["email"] ?? NULL);?>" autocomplete="email">
<label for="name_first">First name</label>
<input type="text" id="name_first" name="name_first" value="<?=$this->escapeForAttributes($this->user["name_first"] ?? NULL);?>" autocomplete="given-name">
<label for="name_last">Last name</label>
<input type="text" id="name_last" name="name_last" value="<?=$this->escapeForAttributes($this->user["name_last"] ?? NULL);?>" autocomplete="family-name">
<label for="phone">Phone number</label>
<input type="text" id="phone" name="phone" value="<?=$this->escapeForAttributes($this->user["phone_number"] ?? NULL);?>" autocomplete="tel">
<label for="address_line">Street address</label>
<input type="text" id="address_line" name="address_line" value="<?=$this->escapeForAttributes($this->user["address"]["line"] ?? NULL);?>" autocomplete="street-address">
<label for="city">City</label>
<input type="text" id="city" name="city" value="<?=$this->escapeForAttributes($this->user["address"]["city"] ?? NULL);?>" autocomplete="address-level2">
<label for="state">State</label>
<input type="text" id="state" name="state" value="<?=$this->escapeForAttributes($this->user["address"]["state"] ?? NULL);?>" autocomplete="address-level1">
<label for="zip_code">Zip code</label>
<input type="text" id="zip_code" name="zip_code" value="<?=$this->escapeForAttributes($this->user["address"]["zip_code"] ?? NULL);?>" autocomplete="postal-code">
<input type="submit" id="update-info" value="Update">

<input type="hidden" name="CSRFToken"  id="CSRFToken" value="<?= $this->sessionManager->getCSRFToken(); ?>">

<script src="<?=$this->getFile('js', __FILE__);?>" type="module"></script>
<?php require APP_ROOT . "/views/includes/footer.php" ?>
