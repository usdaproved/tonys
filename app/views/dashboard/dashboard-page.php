<?php require APP_ROOT . "/views/includes/header.php" ?>
<link href="<?= $this->getFile('css', __FILE__); ?>" rel="stylesheet">
<header>Tony's Taco House</header>
<a href="/Dashboard/orders/active">Active Orders</a>
<a href="/Dashboard/orders/search">Search Orders</a>
<a href="/Dashboard/customers/search">Search Customers</a>
<a href="/Dashboard/menu">Menu</a>
<a href="/Dashboard/employees">Employees</a>
<?php $this->printOneTimeMessages(USER_ALERT); ?>

<?php require APP_ROOT . "/views/includes/footer.php" ?>
