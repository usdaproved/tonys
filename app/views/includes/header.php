<!DOCTYPE html>
<html lang="en">
    <head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<title><?=$this->pageTitle ?? "Tony's | Taco House"?></title>
	<link rel="apple-touch-icon" sizes="180x180" href="favicon/apple-touch-icon.png">
	<link rel="icon" type="image/png" sizes="32x32" href="favicon/favicon-32x32.png">
	<link rel="icon" type="image/png" sizes="16x16" href="favicon/favicon-16x16.png">
	<link rel="manifest" href="/site.webmanifest">
    </head>

    <body>
	<link href="<?= $this->getFile('css', 'reset'); ?>" rel="stylesheet">
	<?php require APP_ROOT . "/views/includes/navigation.php" ?>
