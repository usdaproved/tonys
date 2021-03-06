<!DOCTYPE html>
<html lang="en">
    <head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>404 - No tacos</title>
    </head>
    
    <body>
	<link href="/css/reset.css" rel="stylesheet">
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

	 .svg-button{
	     cursor: pointer;
	     -webkit-appearance: button;
	     border-style: none;
	     padding: 0;
	     background-color: transparent;
	 }

	 .svg-button.inactive{
	     cursor: inherit;
	 }

	 .mobile-hamburger-button{
	     display:inline-flex;
	     align-items: center;
	     justify-content: center;
	     padding: 0.5rem;
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

	 .shadow{
	     border-radius: 0.5rem;
	     box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
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
	     margin-right: 0.50rem;
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
	     font-size: 1.5em;
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
		 font-size: 1.75em;
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
	<link href="/css/components.css" rel="stylesheet">
	<header>
	    <nav class="desktop-menu">
		<a href="/">Home</a>
	    </nav>

	    <h3 class="restaurant-name"><a href="/">Tony's Taco House</a></h3>

	    <div class="desktop-menu">
		<a href="/Order">Order</a>
	    </div>

	    <div class="mobile-hamburger">
		<button type="button" class="svg-button mobile-hamburger-button" id="mobile-hamburger-button">
		    <svg class="navigation-svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
			<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
		    </svg>
		</button>
	    </div>
	</header>
	<div class="mobile-menu-container" id="mobile-menu-container">
	    <div class="shadow">
		<div class="mobile-menu">
		    <div class="space-y-6">
			<div class="mobile-exit-button-container">
			    <button type="button" class="svg-button mobile-menu-exit-button" id="mobile-menu-exit-button">
				<svg class="navigation-svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
				    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
				</svg>
			    </button>
			</div>
			<div class="mobile-user-container">
			    <div class="mobile-menu-nav">
				<a href="/" class="mobile-nav-link">
				    Home
				</a>
				<a href="/Order" class="mobile-nav-link">
				    Order
				</a>
			    </div>
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
		 if(dashboardDropdownContainer){
		     dashboardDropdownContainer.style.display = 'none';
		     userDropdownContainer.style.display = 'none';
		 }
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
	<div class="center-container">
	    <h3>Page not found.</h3>
	</div>

    </body>
</html>
