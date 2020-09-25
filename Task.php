<?php

// This script gets ran every minute by cron.

require "vendor/autoload.php";

$orderManager = new Order();

$orderManager->task_updateSubmittedToPreparing();
$orderManager->task_updatePreparingToPrepared();



?>
