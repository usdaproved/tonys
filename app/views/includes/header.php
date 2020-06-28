<!DOCTYPE html>
<html lang="en">
    <head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<title><?=$this->pageTitle ?? "Tony's | Taco House"?></title>
    </head>

    <body>
	<link href="<?= $this->getFile('css', 'reset'); ?>" rel="stylesheet">
	<?php require APP_ROOT . "/views/includes/navigation.php" ?>
