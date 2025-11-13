<?php
// payment_failed.php - simple failure page
session_start();
require_once 'settings/core.php';
$error = isset($_GET['error']) ? htmlspecialchars($_GET['error']) : '';
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Payment Failed</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="p-4">
  <?php include __DIR__ . '/partials/navbar.php'; ?>
  <div class="container text-center">
    <h2 class="text-danger">Payment Failed</h2>
    <p class="lead">Your simulated payment was not successful.</p>
    <?php if ($error): ?>
      <div class="alert alert-warning"><?php echo $error; ?></div>
    <?php endif; ?>
    <div class="mt-3">
      <a href="checkout.php" class="btn btn-primary">Return to Checkout</a>
      <a href="cart.php" class="btn btn-outline-secondary ms-2">View Cart</a>
    </div>
  </div>
</body>
</html>
