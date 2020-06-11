<?php $displayUser = false; ?>
<?php $loggedIn = false; ?>
<?php $userType = 0; ?>
<?php if($this->sessionManager->isUserLoggedIn()): ?>
    <?php $displayUser = true; ?>
    <?php $loggedIn = true; ?>
    <?php $userType = $this->userManager->getUserAuthorityLevel($userUUID); ?>
<?php elseif($userUUID !== NULL && $this->userManager->getUnregisteredInfoLevel($userUUID) > INFO_NONE): ?>
    <?php $displayUser = true; ?>
<?php endif;?>

<link href="<?= $this->getFile('css', __FILE__); ?>" rel="stylesheet">
<header>
    <nav class="menu">
	<ul>
	    <?php if($userType >= EMPLOYEE) : ?>
		<li class="dropdown-container">
		    <a href="#" id="nav-dashboard">Dashboard</a>
		    <ul class="dropdown">
			<li><a href="/Dashboard/orders/active">Active Orders</a></li>
			<li><a href="/Dashboard/orders/search">Search Orders</a></li>
			<li><a href="/Dashboard/customers/search">Search Customers</a></li>
			<?php if($userType == ADMIN): ?>
			    <li><a href="/Dashboard/menu">Menu</a></li>
			    <li><a href="/Dashboard/employees">Employees</a></li>
			    <li><a href="/Dashboard/settings">Settings</a></li>
			<?php endif; ?>
		    </ul>
		</li>
	    <?php endif; ?>
	    <li><a href="/">Home</a></li>
	    <li><a href="/Order">Order</a></li>
	</ul>
    </nav>

    <h3 class="logo <?=($this instanceof HomeController) ? 'big' : ''?>">Tony's Taco House</h3>
    
    
    <nav class="user-actions">
	<ul>
	    <?php if(!$loggedIn): ?>
		<li><a href="/register">Register</a></li>
		<li><a href="/login">Log in</a></li>
	    <?php endif; ?>
	    <?php if($displayUser): ?>
		<li class="dropdown-container">
		    <a href="#" id="nav-user-name"><?=$this->escapeForHTML($this->user["name_first"])?></a>
		    <ul class="dropdown">
			<li><a href="/User/orders">Order History</a></li>
			<li><a href="/User/info">Info</a></li>
			<li><a href="/User/address">Address</a></li>
			<?php if($loggedIn): ?>
			    <li><a href="/logout">Log out</a></li>
			<?php endif; ?>
		    </ul>
		</li>
	    <?php endif; ?>
	</ul>
    </nav>
</header>

    
