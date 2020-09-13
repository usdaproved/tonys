<?php require APP_ROOT . "/views/includes/header.php" ?>
<link href="<?=$this->getFile('css', 'components');?>" rel="stylesheet">
<link href="<?=$this->getFile('css', __FILE__);?>" rel="stylesheet">
<?php $this->printOneTimeMessages(USER_SUCCESS); ?>
<?php $this->printOneTimeMessages(USER_ALERT); ?>

<div class="center-container">
    <div class="shadow text-form-inner-container">
	<div class="text-form-header">
	    Edit Employees
	</div>
	<div class="current-employee-container" id="current-employees">
	    <?php foreach($this->userStorage as $employee): ?>
		<button class="svg-button current-employee" id="<?=UUID::orderedBytesToArrangedString($employee['uuid']);?>">
		    <span class="employee-name">
			<?=$this->escapeForHTML($employee['name_first']) . ' ' . $this->escapeForHTML($employee['name_last'])?>
		    </span>
		    <span class="employee-role">
			<?=USER_TYPE_ARRAY[$employee['user_type']];?>
		    </span>
		</button>
	    <?php endforeach; ?>
	</div>
	<div class="double-button-container">
	    <div class="wide-button-container">
		<button type="button" class="svg-button wide-button inactive" id="delete-employee" disabled>
		    Delete
		</button>
	    </div>
	    <div class="wide-button-container margin-left-1">
		<button type="button" class="svg-button wide-button inactive" id="toggle-admin" disabled>
		    Toggle Admin Status
		</button>
	    </div>
	</div>
    </div>
</div>

<div class="center-container">
    <div id="search-filters" class="shadow text-form-inner-container">
	<div class="text-form-header">
	    Add New Employee
	</div>
	<div class="text-form-subheader-container">
	    <div class="center-container">
		Use as many filters as necessary.
	    </div>
	</div>
	<div class="input-container">
	    <label for="name_first">First Name</label>
	    <input type="text" id="name_first" autocomplete="off">
	</div>
	<div class="input-container">
	    <label for="name_last">Last Name</label>
	    <input type="text" id="name_last" autocomplete="off">
	</div>
	<div class="input-container">
	    <label for="email">Email</label>
	    <input type="email" id="email" autocomplete="off">
	</div>
	<div class="input-container">
	    <label for="phone_number">Phone Number</label>
	    <input type="text" id="phone_number" autocomplete="off">
	</div>
	<input type="checkbox" id="registered-only" checked disabled hidden>
	<div class="double-button-container">
	    <div class="wide-button-container">
		<button type="button" class="wide-button svg-button" id="user-search-button">
		    Search
		</button>
	    </div>
	    <div class="add-button-container margin-left-1">
		<button type="button"  class="add-button svg-button inactive" id="add-employee" disabled>
		    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="white" width="20px" height="20px">
			<path d="M0 0h24v24H0V0z" fill="none"/>
			<path d="M18 13h-5v5c0 .55-.45 1-1 1s-1-.45-1-1v-5H6c-.55 0-1-.45-1-1s.45-1 1-1h5V6c0-.55.45-1 1-1s1 .45 1 1v5h5c.55 0 1 .45 1 1s-.45 1-1 1z"/>
		    </svg>
		</button>
	    </div>
	</div>
	<div class="center-container margin-top-1">
	    Registered users only.
	</div>
	<div id="user-table" class="search-result-container"></div>
    </div>
</div>

<input type="hidden" id="CSRFToken" name="CSRFToken" value="<?= $this->sessionManager->getCSRFToken(); ?>">

<script src="<?=$this->getFile('js', __FILE__);?>" type="module"></script>
<?php require APP_ROOT . "/views/includes/footer.php" ?>
