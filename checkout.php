<?php
// checkout.php - show summary and simulated payment trigger
session_start();
require_once 'settings/core.php';
require_once 'controllers/cart_controller.php';

$customer_id = isset($_SESSION['customer_id']) ? (int)$_SESSION['customer_id'] : null;
$session_key = session_id();
$ctrl = new CartController();
$items = $ctrl->get_user_cart_ctr($customer_id, $session_key);
$total = 0.0;
foreach ($items as $it) { $total += (isset($it['unit_price'])?(float)$it['unit_price']:0) * (isset($it['quantity'])?(int)$it['quantity']:0); }
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Checkout - Afro Bites</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body class="p-4">
  <?php include __DIR__ . '/partials/navbar.php'; ?>
  <div class="container">
    <h3>Checkout</h3>
    <?php if (empty($items)): ?>
      <div class="alert alert-info">Your cart is empty. <a href="index.php">Continue shopping</a></div>
    <?php else: ?>
      <div class="card mb-3">
        <div class="card-body">
          <ul class="list-group mb-3">
            <?php foreach ($items as $it): ?>
              <li class="list-group-item d-flex justify-content-between align-items-center">
                <div>
                  <strong><?php echo htmlspecialchars($it['product_title'] ?? 'Product'); ?></strong>
                  <div class="small text-muted">GHS<?php echo number_format($it['unit_price'] ?? 0,2); ?> x <?php echo (int)$it['quantity']; ?></div>
                </div>
                <div>GHS<?php echo number_format(((float)$it['unit_price']*(int)$it['quantity']),2); ?></div>
              </li>
            <?php endforeach; ?>
          </ul>
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <a href="cart.php" class="btn btn-light">Back to Cart</a>
            </div>
            <div>
              <strong>Total: GHS<?php echo number_format($total,2); ?></strong>
              <?php if (!empty($customer_id)): ?>
                <button class="btn btn-primary ms-3" onclick="startCheckoutFlow(<?php echo number_format($total,2,'.',''); ?>)">Simulate Payment</button>
              <?php else: ?>
                <button class="btn btn-primary ms-3" data-bs-toggle="modal" data-bs-target="#loginRequiredModal">Proceed to Login</button>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    <?php endif; ?>
  </div>

  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="js/cart.js"></script>
  <script src="js/checkout.js"></script>

  <!-- Login required modal -->
  <div class="modal fade" id="loginRequiredModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Login Required</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <p>You need to be logged in to proceed to checkout.</p>
        </div>
      </div>
    </div>
  </div>
</body>
</html>
