<?php require APP_ROOT . "/views/includes/header.php" ?>
	<link rel="stylesheet" type="text/css" href="css/views/home/home-register-page.css" />
	<!--<script async src="js/views/home.js"></script>-->
	<header>Tony's Taco House</header>
	<h1>Welcome back <?= $this->userWholeName["name_first"]; ?></h1>
	<p><a href="/Order">Order again</a></p>

	<?php foreach($this->orders as $order): ?>
	    <article>
		<h2><strong>Order ID</strong>: <?= $order['id'];?></h2>
		<p><strong>Ordered on</strong>: <?= $order['date']; ?></p>
		<p><strong>Total</strong>: <?= "$" . $order['total_price']; ?></p>
		<p><strong>Order Details</strong>:
		    <ul>
			<?php foreach($order['order_line_item'] as $lineItem): ?>
			    <li><?= $lineItem['quantity'] . ' ' . $lineItem['name']; ?></li>
			<?php endforeach; ?>
		    </ul>
		</p>
	</article>
	<?php endforeach; ?>
	
<?php require APP_ROOT . "/views/includes/footer.php" ?>
