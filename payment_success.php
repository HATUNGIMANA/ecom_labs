<?php
// payment_success.php - simple confirmation page
session_start();
require_once 'settings/core.php';

$order_ref = isset($_GET['order_ref']) ? htmlspecialchars($_GET['order_ref']) : '';
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Payment Successful</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4">
  <div class="container text-center">
    <?php include __DIR__ . '/partials/navbar.php'; ?>
    <h2>Thank you!</h2>
    <p class="lead">Your payment was successfully simulated.</p>
    <?php if ($order_ref): ?>
      <p>Your order reference: <strong><?php echo $order_ref; ?></strong></p>
    <?php endif; ?>
    <a href="index.php" class="btn btn-primary">Return Home</a>
  </div>
</body>
</html>
