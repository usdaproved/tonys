<?php require APP_ROOT . "/views/includes/header.php" ?>
	<link rel="stylesheet" type="text/css" href="css/views/home/home-register-page.css" />
	<!--<script async src="js/views/home.js"></script>-->
	<header>Tony's Taco House</header>
	<h2>Register before ordering</h2>
	<form method="post">
	    <label for="name_first">First Name</label>
	    <input type="text" id="name_first" name="name_first">
	    <label for="name_last">Last Name</label>
	    <input type="text" id="name_last" name="name_last">
	    <input type="submit" value="register">
	</form>
	
<?php require APP_ROOT . "/views/includes/footer.php" ?>
