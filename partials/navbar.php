<?php
// partials/navbar.php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../settings/core.php';
$loggedIn = is_logged_in();
$customerName = $loggedIn ? get_user_name() : '';
?>
<style>
  /* Apply serif typography site-wide via shared partial */
  html, body, p, h1, h2, h3, h4, h5, h6, button, input, select, textarea, .btn {
    font-family: Georgia, "Times New Roman", Times, serif !important;
  }
  /* ensure small text also uses serif */
  small, .small { font-family: Georgia, "Times New Roman", Times, serif !important; }
</style>
<nav class="navbar navbar-expand-lg navbar-light mb-3" style="background: rgba(255,255,255,0.96); box-shadow:0 2px 10px rgba(0,0,0,0.08);">
  <div class="container">
    <a class="navbar-brand" href="index.php"><i class="fas fa-utensils me-2"></i>Afro Bites Kitchen</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="mainNav">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
        <li class="nav-item"><a class="nav-link" href="all_product.php">All Dishes</a></li>
      </ul>

      <div class="d-flex align-items-center">
        <?php if ($loggedIn): ?>
          <span class="me-3 text-muted">Welcome, <?php echo htmlspecialchars($customerName); ?></span>
          <?php if (get_user_role() == 1): ?>
            <a href="admin/category.php" class="btn btn-sm btn-outline-primary me-2">Category</a>
            <a href="admin/brand.php" class="btn btn-sm btn-outline-primary me-2">Brand</a>
            <a href="admin/product.php" class="btn btn-sm btn-outline-primary me-2">Add Product</a>
          <?php endif; ?>
          <a href="login/logout.php" class="btn btn-sm btn-outline-danger me-2">Logout</a>
        <?php else: ?>
          <a href="login/register.php" class="btn btn-sm btn-outline-primary me-2">Register</a>
          <a href="login/login.php" class="btn btn-sm btn-outline-primary me-2">Login</a>
        <?php endif; ?>

        <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="offcanvas" data-bs-target="#cartOffcanvas" aria-controls="cartOffcanvas">
          <i class="fa fa-shopping-cart"></i> Cart <span id="cart-count" class="badge bg-danger ms-1">0</span>
        </button>
      </div>
    </div>
  </div>
</nav>

<!-- Cart Offcanvas (shared) -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="cartOffcanvas" aria-labelledby="cartOffcanvasLabel">
  <div class="offcanvas-header">
    <h5 id="cartOffcanvasLabel">Your Cart</h5>
    <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
  </div>
  <div class="offcanvas-body" id="mini-cart">
    <div class="p-3">Loading...</div>
  </div>
</div>

<script>
// attach logout confirmation to logout links
document.addEventListener('DOMContentLoaded', function(){
  document.querySelectorAll('a[href*="login/logout.php"]').forEach(function(a){
    a.addEventListener('click', function(e){
      e.preventDefault();
      if (confirm('Do you really want to log out?')) {
        window.location.href = a.href;
      }
    });
  });
});
</script>
