<?php require APP_ROOT . "/views/includes/header.php" ?>

<article>
    <h1>Successfuly ordered.</h1>
    <p><strong>Ordered</strong>: <?= $this->order['date']; ?></p>
    <p><strong>Total</strong>: <?= "$" . $this->order['total_price']; ?></p>
    <p><strong>Order Details</strong>:
	<ul>
	    <?php foreach($this->order['order_line_items'] as $lineItem): ?>
	    <li><?= $lineItem['quantity'] . ' ' . $lineItem['name']; ?></li>
	    <?php endforeach; ?>
	</ul>
    </p>

    <p><a href="/">Home</a></p>

    
</article>

<?php require APP_ROOT . "/views/includes/footer.php" ?>
