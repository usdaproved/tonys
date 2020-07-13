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
<style>
 .space-y-6 > * + * {
     margin-top: 1.5rem;
 }

 header{
     padding: 1rem;
     position: relative;
     margin-bottom: 1rem;
     display: flex;
     justify-content: space-between;
     align-items: center;
     border-bottom-style: solid;
     border-color: #f7fafc;
     border-bottom-width: 2px;
 }
 .mobile-hamburger{
     display: block;
     margin-right: -0.5rem;
     margin-top: -0.5rem;
     margin-bottom: -0.5rem;
 }

 .mobile-hamburger-button{
     display:inline-flex;
     align-items: center;
     justify-content: center;
     padding: 0.5rem;
     -webkit-appearance: button;
     border-style: none;
     padding: 0;
     background-color: transparent;
 }

 .navigation-svg{
     height: 1.5rem;
     width: 1.5rem;
 }

 .mobile-menu-container{
     display: none;
     position: absolute;
     top: 0;
     right: 0;
     left: 0;
     padding: 1rem;
     z-index: 1000;
 }

 .mobile-menu{
     padding-top: 2.25rem;
     background-color: white;
     box-shadow: 0 0 0 1px rgba(0, 0, 0, 0.05);
     border-radius: 0.5rem;
 }

 .mobile-exit-button-container{
     display: flex;
     justify-content: flex-end;
 }

 .mobile-menu-exit-button{
     -webkit-appearance: button;
     border-style: none;
     padding: 0;
     background-color: transparent;
 }

 .mobile-menu-nav{
     display: grid;
     row-gap: 2rem;
     margin-bottom: 2rem;
 }

 .mobile-nav-link{
     margin: -0.75rem;
     padding: 0.75rem;
     align-items: center;
     border-radius: 0.375rem;

 }

 .mobile-nav-link:hover{
     background-color: #f7fafc;
 }

 .mobile-user-actions-container{
     display: grid;
     grid-template-columns: repeat(2, minmax(0, 1fr));
     row-gap: 1rem;
     column-gap: 2rem;
 }

 .mobile-user-actions{
     display: flex;
     flex-direction: column;
 }

 .mobile-dashboard-title{
     font-weight: 700;
     color: inherit;
     margin-bottom: 0.25rem;
 }
 .user-link{
     margin-bottom: 0.5rem;
 }

 .mobile-user-container{
     padding-right: 1.25rem;
     padding-left: 1.25rem;
     padding-top: 1.5rem;
     padding-bottom: 1.5rem;
 }

 .register-container{
     width: 100%;
     display: flex;
     border-radius: 0.375rem;
 }

 .register-button{
     width: 100%;
     display: flex;
     justify-items: center;
     justify-content: center;
     padding-right: 1rem;
     padding-left: 1rem;
     padding-top: 0.5rem;
     padding-bottom: 0.5rem;
     border-width: 1px;
     border-color: transparent;
     font-weight: 500;
     color: #fff;
     background-color: #5a67d8;
     border-radius: 0.375rem;
     text-decoration: none;
 }

 .logout-container{
     width: 100%;
     display: flex;
     border-radius: 0.375rem;
 }

 .logout-button{
     width: 100%;
     display: flex;
     justify-items: center;
     justify-content: center;
     padding-right: 1rem;
     padding-left: 1rem;
     padding-top: 0.5rem;
     padding-bottom: 0.5rem;
     border-width: 1px;
     border-color: transparent;
     font-weight: 500;
     color: #fff;
     background-color: #e53e3e;
     border-radius: 0.375rem;
     text-decoration: none;
 }

 .register-button:hover{
     background-color: #667eea;
 }

 .register-button:focus{
     background-color: #4c51bf;
 }

 .register-button:active{
     background-color: #4c51bf;
 }

 h3.restaurant-name{
     font-size:1.75em;
     text-align: center;
 }

 .restaurant-name a {
     text-decoration: none;
     color: inherit;
 }

 .desktop-menu, .desktop-user-actions{
     display: none;
 }

 /* Larger than a phone screen. Taking a mobile first approach. */
 @media (min-width: 768px){
     .mobile-hamburger{
         display: none;
     }
     .mobile-menu{
         display: none;
     }
     header{
         justify-content: space-evenly;
     }
     h3.restaurant-name{
         font-size: 2em;
     }
     .desktop-menu, .desktop-user-actions{
         flex-grow: 1;
         display: flex;
         flex-direction: row;
         justify-content: space-evenly;
         width: auto;
     }
 }

 .dropdown-container{
     position: relative;
 }

 .dropdown-button{
     display:inline-flex;
     justify-items: center;
     -webkit-appearance: button;
     border-style: none;
     padding: 0;
     text-transform: none;
     background-color: transparent;
 }

 .dropdown-text{
     font-weight: 700;
 }

 .dropdown{
     margin-left: -2em;
     margin-top: 1rem;

     position: absolute;
     display: none;
     z-index: 100;
 }

 .dropdown-box{
     background-color: white;
     box-shadow: 0 0 0 1px rgba(0, 0, 0, 0.05);
     border-radius: 0.5rem;
     min-width: max-content;
 }

 .dropdown-link-container{
     display: flex;
     flex-direction: column;
     padding: 0.5rem;
     margin: 0.5rem;
     max-width: max-content;
 }

 .dropdown-link{
     margin-bottom: 0.5rem;
 }

 .dropdown-link:hover{
     background-color: #f7fafc;
 }
</style>
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
				<?php if($userType >= ADMIN): ?>
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

    
