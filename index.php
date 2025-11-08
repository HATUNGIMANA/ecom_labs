<?php
// index.php
session_start();

// Featured meals
$featuredMeals = [
    ['id' => 1, 'title' => 'Jollof Rice with Grilled Chicken', 'desc' => 'Spiced tomato rice served with succulent grilled chicken.', 'icon' => 'fa-drumstick-bite'],
    ['id' => 2, 'title' => 'Pilau with Beef', 'desc' => 'Fragrant spiced rice cooked with tender beef pieces.', 'icon' => 'fa-bowl-rice'],
    ['id' => 3, 'title' => 'Grilled Fish & Plantain', 'desc' => 'Whole grilled fish with fried plantain — smoky and savory.', 'icon' => 'fa-fish'],
    ['id' => 4, 'title' => 'Beef Burger & Fries', 'desc' => 'Classic burger stacked with cheese and served with crispy fries.', 'icon' => 'fa-hamburger'],
];

$loggedIn = isset($_SESSION['customer_id']) && !empty($_SESSION['customer_id']);
$customerName = $loggedIn ? ($_SESSION['customer_name'] ?? '') : '';
$userRole = $loggedIn ? ($_SESSION['user_role'] ?? 2) : 2;
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Taste of Africa — Home</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

  <style>
    :root {
      --accent: #b77a7a;
      --accent-dark: #a26363;
      --card-bg: rgba(255,255,255,0.95);
      --muted: #444;
    }

    body {
      background-color: var(--accent);
      font-family: Arial, Helvetica, sans-serif;
      color: #222;
      margin:0;
      min-height:100vh;
    }

    .menu-tray {
      position: fixed;
      top: 16px;
      right: 16px;
      background: rgba(255,255,255,0.96);
      border-radius: 10px;
      padding: 8px 12px;
      box-shadow: 0 8px 30px rgba(0,0,0,0.12);
      display:flex;
      align-items:center;
      gap:8px;
      z-index:1000;
    }

    /* CHANGE REQUESTED: Buttons Become Text Colored #b77a7a */
    .menu-tray a.btn-outline-light {
      color: #b77a7a !important;
      border-color: #b77a7a !important;
    }
    .menu-tray a.btn-outline-light:hover {
      background-color: #b77a7a !important;
      color: white !important;
    }

    .menu-tray .welcome-user { color: var(--accent); font-weight:700; }

    .hero {
      padding: 72px 16px 28px;
      text-align:center;
      color: #fff;
    }
    .hero h1 { font-weight:800; margin-bottom:6px; }
    .hero p.lead { color: rgba(255,255,255,0.94); max-width:820px; margin:0 auto 18px; }

    .meals-grid { padding: 20px 16px 56px; }
    .meal-card {
      border-radius: 12px;
      background: var(--card-bg);
      box-shadow: 0 8px 28px rgba(0,0,0,0.12);
      transition:.12s;
    }
    .meal-card:hover { transform: translateY(-6px); }

    .meal-icon {
      height:140px;
      display:flex; justify-content:center; align-items:center;
      font-size:56px;
      color:var(--accent-dark);
      background: rgba(255,255,255,0.15);
    }

    .order-btn { background:#D19C97; border:none; color:white; border-radius:6px; }
    .order-btn:hover { background:#b77a7a; }

    footer { color:white; text-align:center; padding:28px 0; font-size:.9rem; }
  </style>
</head>
<body>

  <div class="menu-tray">
    <span class="me-2">Menu:</span>

    <?php if ($loggedIn): ?>
      <span class="welcome-user me-2">Welcome, <?php echo htmlspecialchars($customerName); ?>!</span>
      <?php if ($userRole == 1): ?>
        <a href="admin/category.php" class="btn btn-sm btn-outline-primary">Category</a>
      <?php endif; ?>
      <a href="login/logout.php" class="btn btn-sm btn-outline-danger">Logout</a>
    <?php else: ?>
      <!-- These are the two links we recolored -->
      <a href="login/register.php" class="btn btn-sm btn-outline-light">Register</a>
      <a href="login/login.php" class="btn btn-sm btn-outline-light">Login</a>
    <?php endif; ?>
  </div>

  <section class="hero">
    <h1>Taste of Africa</h1>
    <p class="lead">Authentic, home-style African meals - crafted with real ingredients and bold flavors.</p>

    <?php if (!$loggedIn): ?>
      <div class="mt-3">
        <a href="login/register.php" class="btn btn-outline-light me-2">Create an account</a>
        <a href="login/login.php" class="btn btn-light">Login</a>
      </div>
    <?php endif; ?>
  </section>

  <section class="meals-grid">
    <div class="container">
      <h3 class="text-white mb-3">Featured Meals</h3>
      <div class="row g-4">

        <?php foreach ($featuredMeals as $meal): ?>
          <div class="col-12 col-sm-6 col-lg-3">
            <div class="meal-card h-100">
              <div class="meal-icon"><i class="fa <?php echo $meal['icon']; ?>"></i></div>

              <div class="p-3">
                <h5><?php echo htmlspecialchars($meal['title']); ?></h5>
                <p class="text-muted small"><?php echo htmlspecialchars($meal['desc']); ?></p>
                <div class="d-flex justify-content-between align-items-center">
                  <span class="text-muted small">From $6.99</span>

                  <?php if ($loggedIn): ?>
                    <a href="order.php?meal_id=<?php echo $meal['id']; ?>" class="btn btn-sm order-btn">Order</a>
                  <?php else: ?>
                    <a href="login/login.php" class="btn btn-sm order-btn">Order</a>
                  <?php endif; ?>

                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>

      </div>
    </div>
  </section>

  <footer>
    © <?php echo date('Y'); ?> Taste of Africa — Bringing home the flavors of the continent.
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
