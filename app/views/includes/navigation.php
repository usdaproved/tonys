<?php // (C) Copyright 2020 by Trystan Brock All Rights Reserved. ?>
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
    <nav class="desktop-menu">
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

    <h3 class="restaurant-name">Tony's Taco House</h3>

    <div class="mobile-hamburger">
	<button type="button" class="mobile-hamburger-button" id="mobile-hamburger-button">
	    <svg class="navigation-svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
			<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
		</svg>
	</button>
    </div>
    
    
    <nav class="desktop-user-actions">
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
<div class="mobile-menu-container" id="mobile-menu-container">
	<div class="mobile-menu">
		<div class="space-y-6">
			<div class="mobile-exit-button-container">
				<button type="button" class="mobile-menu-exit-button" id="mobile-menu-exit-button">
					<svg class="navigation-svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
						<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
					</svg>
				</button>
			</div>
			<div class="mobile-user-container">
				<nav class="mobile-menu-nav">
					<a href="/" class="mobile-nav-link">
						Home
					</a>
					<a href="/Order" class="mobile-nav-link">
						Order
					</a>
				</nav>
				<div class="space-y-6">
					<div class="mobile-user-actions-container">
						<nav class="mobile-user-actions">
						<?php if($displayUser): ?>
							<a href="/User/orders" class="user-link">Order History</a>
							<a href="/User/info"  class="user-link">Info</a>
							<a href="/User/address"  class="user-link">Address</a>
						<?php endif; ?>
						</nav>
						<nav class="mobile-user-actions">
						<?php if($userType >= EMPLOYEE) : ?>
							<a href="#" class="mobile-dashboard-title">Dashboard</a>
							<a href="/Dashboard/orders/active"  class="user-link">Active Orders</a>
							<a href="/Dashboard/orders/search"  class="user-link">Search Orders</a>
							<a href="/Dashboard/customers/search"  class="user-link">Search Customers</a>
							<?php if($userType == ADMIN): ?>
								<a href="/Dashboard/menu"  class="user-link">Menu</a>
								<a href="/Dashboard/employees"  class="user-link">Employees</a>
								<a href="/Dashboard/settings"  class="user-link">Settings</a>
							<?php endif; ?>
						<?php endif; ?>
						</nav>
					</div>
					<?php if(!$loggedIn): ?>
					<div class="space-y-6">
						<span class="register-container">
							<a href="/register" class="register-button">
								Register
							</a>
						</span>
						<p class="login-container">
							Existing customer?
							<a href="/login" class="login-button">
								Sign in
							</a>
						</p>
					</div>
					<?php endif; ?>
					<?php if($loggedIn): ?>
						<div class="logout-container">
						<a href="/logout" class="logout-button">
							Log out
						</a>
						</div>
					<?php endif; ?>
				</div>
			</div>
		</div>
	</div>
</div>
<script>
	"use strict";

	const mobileHamburgerButton = document.querySelector('#mobile-hamburger-button');
	const mobileMenuExitButton = document.querySelector('#mobile-menu-exit-button');
	const mobileMenuElement = document.querySelector('#mobile-menu-container');

	let mobileMenuActive = false;

	const toggleMobileMenu = () => {
		if(mobileMenuElement.style.display === 'block'){
			mobileMenuElement.style.display = 'none';
			mobileMenuActive = false;
		} else {
			mobileMenuElement.style.display = 'block';
			mobileMenuActive = true;
		}
	};

	mobileHamburgerButton.addEventListener('click', (e) => {
		toggleMobileMenu();
	});

	mobileMenuExitButton.addEventListener('click', (e) => {
		toggleMobileMenu();
	});

	window.addEventListener('resize', (e) => {
		if(window.innerWidth > 768){
			mobileMenuElement.style.display = 'none';
		} else if (window.innerWidth < 768) {
			if(mobileMenuActive){
				mobileMenuElement.style.display = 'block';
			}
		}
	});
</script>

    
