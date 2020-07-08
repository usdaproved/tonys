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
	    <a href="/">Home</a>
	    <a href="/Order">Order</a>
    </nav>

    <h3 class="restaurant-name"><a href="/">Tony's Taco House</a></h3>

    <div class="mobile-hamburger">
	<button type="button" class="mobile-hamburger-button" id="mobile-hamburger-button">
	    <svg class="navigation-svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
			<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
		</svg>
	</button>
    </div>
    
    
    <nav class="desktop-user-actions">
	    <?php if($displayUser): ?>
		<div class="dropdown-container">
			<button type="button" class="dropdown-button" id="user-dropdown-button">
				<span class="dropdown-text"><?=$this->escapeForHTML($this->user["name_first"])?></span>
				<svg class="navigation-svg" viewBox="0 0 20 20" fill="currentColor">
              		<path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
				</svg>
			</button>
		    <div class="dropdown" id="user-dropdown-container">
				<div class="dropdown-box">
				<div class="dropdown-link-container">
				<a href="/User/orders" class="dropdown-link">Order History</a>
				<a href="/User/info" class="dropdown-link">Info</a>
				<a href="/User/address" class="dropdown-link">Address</a>
				<?php if($loggedIn): ?>
					<a href="/logout" class="dropdown-link">Log out</a>
				<?php endif; ?>
				</div>
				</div>
		    </div>
		</div>
		
		<?php if($userType >= EMPLOYEE) : ?>
		<div class="dropdown-container">
			<button type="button" class="dropdown-button" id="dashboard-dropdown-button">
				<span class="dropdown-text">Dashboard</span>
				<svg class="navigation-svg" viewBox="0 0 20 20" fill="currentColor">
              		<path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
				</svg>
			</button>
			<div class="dropdown" id="dashboard-dropdown-container">
				<div class="dropdown-box">
				<div class="dropdown-link-container">
				<a href="/Dashboard/orders/active" class="dropdown-link">Active Orders</a>
				<a href="/Dashboard/orders/search" class="dropdown-link">Search Orders</a>
				<a href="/Dashboard/customers/search" class="dropdown-link">Search Customers</a>
				<?php if($userType == ADMIN): ?>
				<a href="/Dashboard/menu" class="dropdown-link">Menu</a>
				<a href="/Dashboard/employees" class="dropdown-link">Employees</a>
				<a href="/Dashboard/settings" class="dropdown-link">Settings</a>
				<?php endif; ?>
				</div>
				</div>
			</div>
		</div>
		<?php endif; ?>
		<?php endif; ?>
		<?php if(!$loggedIn): ?>
		<li><a href="/register">Register</a></li>
		<li><a href="/login">Log in</a></li>
	    <?php endif; ?>
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

	const mobileHamburgerButton      = document.querySelector('#mobile-hamburger-button');
	const mobileMenuExitButton       = document.querySelector('#mobile-menu-exit-button');
	const mobileMenuElement          = document.querySelector('#mobile-menu-container');
	const dashboardDropdownButton    = document.querySelector('#dashboard-dropdown-button');
	const dashboardDropdownContainer = document.querySelector('#dashboard-dropdown-container');
	const userDropdownButton         = document.querySelector('#user-dropdown-button');
	const userDropdownContainer      = document.querySelector('#user-dropdown-container');

	let mobileMenuActive        = false;
	let dashboardDropdownActive = false;
	let userDropdownActive      = false;

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
		e.stopPropagation();
		toggleMobileMenu();
	});

	mobileMenuExitButton.addEventListener('click', (e) => {
		e.stopPropagation();
		toggleMobileMenu();
	});

	window.addEventListener('resize', (e) => {
		if(window.innerWidth > 768){
			mobileMenuElement.style.display = 'none';
			if(dashboardDropdownActive){
				dashboardDropdownContainer.style.display = 'block';
			}
			if(userDropdownActive){
				userDropdownContainer.style.display = 'block';
			}
		} else if (window.innerWidth < 768) {
			if(mobileMenuActive){
				mobileMenuElement.style.display = 'block';
			}
			dashboardDropdownContainer.style.display = 'none';
			userDropdownContainer.style.display = 'none';
		}
	});

	if(dashboardDropdownButton){
		dashboardDropdownButton.addEventListener('click', (e) => {
			e.stopPropagation();
			
			if(userDropdownActive){
				userDropdownActive = false;
				userDropdownContainer.style.display = 'none';
			}
			
			if(dashboardDropdownActive){
				dashboardDropdownContainer.style.display = 'none';
			} else {
				dashboardDropdownContainer.style.display = 'block';
			}
			dashboardDropdownActive = !dashboardDropdownActive;
		});
	}
	if(userDropdownButton){
		userDropdownButton.addEventListener('click', (e) => {
			e.stopPropagation();

			if(dashboardDropdownActive){
				dashboardDropdownContainer.style.display = 'none';
				dashboardDropdownActive = false;
			}

			if(userDropdownActive){
				userDropdownContainer.style.display = 'none';
			} else {
				userDropdownContainer.style.display = 'block';
			}
			userDropdownActive = !userDropdownActive;
		});
	}

	window.addEventListener('click', (e) => {
		if(mobileMenuActive){
			if(!e.target.closest('#mobile-menu-container')){
				mobileMenuElement.style.display = 'none';
				mobileMenuActive = false;
			}
		}
		if(dashboardDropdownActive){
			if(!e.target.closest('#dashboard-dropdown-container')){
				dashboardDropdownContainer.style.display = 'none';
				dashboardDropdownActive = false;
			}
		}
		if(userDropdownActive){
			if(!e.target.closest('#user-dropdown-container')){
				userDropdownContainer.style.display = 'none';
				userDropdownActive = false;
			}
		}
	});
</script>

    
