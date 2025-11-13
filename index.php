<?php
// index.php
session_start();

// --- Utility: safe DB connect using project's constants or sensible defaults ---
function project_db_connect() {
    // Try common settings file locations first (do not throw fatal if missing)
    $try = [
        __DIR__ . '/settings/db_cred.php',
        __DIR__ . '/settings/db_class.php',
        __DIR__ . '/settings/db_cred_local.php',
        __DIR__ . '/../settings/db_cred.php',
    ];
    foreach ($try as $f) {
        if (file_exists($f)) {
            @require_once $f;
            break;
        }
    }

    // Prefer constants if defined
    $host = defined('DB_HOST') ? DB_HOST : (defined('SERVER') ? SERVER : 'localhost');
    $user = defined('DB_USER') ? DB_USER : (defined('USERNAME') ? USERNAME : 'root');
    $pass = defined('DB_PASS') ? DB_PASS : (defined('PASSWD') ? PASSWD : '');
    $name = defined('DB_NAME') ? DB_NAME : (defined('DATABASE') ? DATABASE : 'shoppn');

    // Create mysqli and return or null on failure
    $mysqli = @new mysqli($host, $user, $pass, $name);
    if ($mysqli && !$mysqli->connect_errno) {
        $mysqli->set_charset('utf8mb4');
        return $mysqli;
    }
    return null;
}

// Featured fallback meals (used if DB product fetch fails)
$featuredMeals = [
    ['id' => 1, 'title' => 'Jollof Rice with Grilled Chicken', 'desc' => 'Spiced tomato rice served with succulent grilled chicken.', 'icon' => 'fa-drumstick-bite'],
    ['id' => 2, 'title' => 'Pilau with Beef', 'desc' => 'Fragrant spiced rice cooked with tender beef pieces.', 'icon' => 'fa-bowl-rice'],
  ['id' => 3, 'title' => 'Grilled Fish & Plantain', 'desc' => 'Whole grilled fish with fried plantain - smoky and savory.', 'icon' => 'fa-fish'],
    ['id' => 4, 'title' => 'Beef Burger & Fries', 'desc' => 'Classic burger stacked with cheese and served with crispy fries.', 'icon' => 'fa-hamburger'],
];

$loggedIn = isset($_SESSION['customer_id']) && !empty($_SESSION['customer_id']);
$customerName = $loggedIn ? ($_SESSION['customer_name'] ?? '') : '';
$userRole = $loggedIn ? ($_SESSION['user_role'] ?? 2) : 2; // 1 = admin, 2 = customer

// --- Fetch categories, brands and products from DB (best-effort) ---
$db = project_db_connect();
$categories = [];
$brands = [];
$products = [];

if ($db) {
    // categories
    $res = $db->query("SELECT cat_id, cat_name FROM categories ORDER BY cat_name");
    if ($res) {
        while ($r = $res->fetch_assoc()) $categories[] = $r;
        $res->free();
    }

    // brands
    $res = $db->query("SELECT brand_id, brand_name FROM brands ORDER BY brand_name");
    if ($res) {
        while ($r = $res->fetch_assoc()) $brands[] = $r;
        $res->free();
    }

    // products - latest 12 with category & brand
    $sql = "SELECT p.product_id, p.product_title, p.product_price, p.product_desc, p.product_image,
                   c.cat_id, c.cat_name, b.brand_id, b.brand_name
            FROM products p
            LEFT JOIN categories c ON p.product_cat = c.cat_id
            LEFT JOIN brands b ON p.product_brand = b.brand_id
            ORDER BY p.product_id DESC
            LIMIT 12";
    $res = $db->query($sql);
    if ($res) {
        while ($r = $res->fetch_assoc()) $products[] = $r;
        $res->free();
    }
    // keep connection close for later
    $db->close();
}

// If products list empty, fall back to static featuredMeals (map them into product-like shape)
if (empty($products)) {
    $products = [];
    foreach ($featuredMeals as $fm) {
        $products[] = [
            'product_id' => $fm['id'],
            'product_title' => $fm['title'],
            'product_price' => 6.99,
            'product_desc' => $fm['desc'],
            'product_image' => '', // no image
            'cat_name' => 'Featured',
            'brand_name' => 'Afro Bites'
        ];
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Afro Bites Kitchen - Home</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">

  <style>
    :root {
      --accent: #b77a7a;
      --accent-dark: #a26363;
      --card-bg: rgba(255,255,255,0.95);
    }

    html,body { height:100%; margin:0; }
    body {
      background-color: var(--accent);
      color: #222;
      font-family: Arial, Helvetica, sans-serif;
      min-height:100vh;
    }

    /* Centered top menu (placed in the middle of the page) */
    .menu-tray {
      position: relative; /* not fixed so it flows with content */
      margin: 12px auto 0 auto;
      background: rgba(255,255,255,0.96);
      border-radius: 8px;
      padding: 8px 12px;
      box-shadow: 0 8px 30px rgba(0,0,0,0.12);
      display:flex; gap:8px; align-items:center; justify-content:center; width:fit-content;
      z-index:1000;
    }
    .menu-tray a.btn-outline-light { color: #b77a7a !important; border-color: #b77a7a !important; }
    .menu-tray a.btn-outline-light:hover { background-color: #b77a7a !important; color:#fff !important; }

    .hero {
      padding: 72px 16px 28px;
      text-align:center;
      color:#fff;
    }
    .hero h1 { font-weight:800; margin-bottom:6px; }
    .hero p.lead { color: rgba(255,255,255,0.94); max-width:820px; margin:0 auto 18px; }

    /* Layout: stacked search above featured products and centered */
    .layout {
      padding: 12px;
      margin-top: 12px;
      padding-top: 0;
      display:flex;
      justify-content:center;
    }
    .layout .row { width:100%; max-width:1100px; margin:0 auto; }
    .left-card { margin:0 auto 18px; max-width:700px; }
  .search-card { background: rgba(255,255,255,0.96); border-radius:6px; padding:16px; box-shadow:0 8px 30px rgba(0,0,0,0.12); max-width:900px; margin:0 auto; }

    /* product cards */
  /* Rectangular product cards centered */
  .meal-card { border-radius:4px; background: var(--card-bg); box-shadow:0 8px 28px rgba(0,0,0,0.12); transition: .12s; overflow:hidden; }
  .meal-card:hover { transform: translateY(-6px); }
  .meal-icon { height:80px; display:flex; justify-content:center; align-items:center; font-size:36px; color:var(--accent-dark); background: rgba(255,255,255,0.06); border-radius:0; }
    .order-btn { background:#D19C97; border:none; color:white; border-radius:6px; }
    .order-btn:hover { background:#b77a7a; }

    footer { color:white; text-align:center; padding:28px 0; font-size:.9rem; margin-top:36px; }

    @media (max-width:767px) {
      .left-card { position: relative; top: auto; margin-bottom:12px; }
      .meal-icon { height:100px; font-size:36px; }
    }

    /* At wider screens ensure content is stacked vertically and centered */
    @media (min-width:768px) {
      .layout .row { display:flex; flex-direction:column; align-items:center; }
      section.col-md-8, aside.col-md-4 { width:100%; max-width:920px; }
      .row.g-4 { justify-content:center; }
      /* Keep the box size but lay items out in two fixed columns centered */
      :root { --featured-box-w: 420px; }
      .products-grid { display:block; }
      .product-item { width:100%; }
      .products-grid.grid-md { display:grid; grid-template-columns: repeat(2, var(--featured-box-w)); gap:1.5rem; justify-content:center; }
      .products-grid.grid-md .product-item { width: var(--featured-box-w); }
    }
  </style>
</head>
<body>

  <?php include __DIR__ . '/partials/navbar.php'; ?>

  <section class="hero">
    <h1>Afro Bites Kitchen</h1>
    <p class="lead">Craving that taste of home? You're in the right place. Every meal here is made with love, bringing you the warmth of African flavors and the comfort of family recipes. Each dish tells a story, each bite feels like a hug.</p>
    <?php if (!$loggedIn): ?>
      <div class="mt-3">
        <a href="login/register.php" class="btn btn-outline-light me-2">Create an account</a>
        <a href="login/login.php" class="btn btn-light">Login</a>
      </div>
    <?php endif; ?>
  </section>

  <main class="layout container">
    <div class="row">
      <!-- LEFT: search / filters (sticky) -->
      <aside class="col-12 col-md-4">
        <div class="left-card">
          <div class="search-card">
            <form id="search-form" method="GET" action="product_search_result.php">
              <div class="mb-2">
                <label class="form-label small mb-1">Search</label>
                <input type="text" name="q" id="search-query" class="form-control form-control-sm" placeholder="Search dishes..." value="<?php echo htmlspecialchars($_GET['q'] ?? ''); ?>">
              </div>

              <div class="mb-2">
                <label class="form-label small mb-1">Category</label>
                <select name="cat_id" id="filter-category" class="form-select form-select-sm">
                  <option value="">All Categories</option>
                  <?php foreach ($categories as $cat): 
                    $sel = (isset($_GET['cat_id']) && $_GET['cat_id'] == $cat['cat_id']) ? 'selected' : '';
                  ?>
                    <option value="<?php echo (int)$cat['cat_id']; ?>" <?php echo $sel; ?>><?php echo htmlspecialchars($cat['cat_name']); ?></option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="mb-2">
                <label class="form-label small mb-1">Brand</label>
                <select name="brand_id" id="filter-brand" class="form-select form-select-sm">
                  <option value="">All Brands</option>
                  <?php foreach ($brands as $b): 
                    $selb = (isset($_GET['brand_id']) && $_GET['brand_id'] == $b['brand_id']) ? 'selected' : '';
                  ?>
                    <option value="<?php echo (int)$b['brand_id']; ?>" <?php echo $selb; ?>><?php echo htmlspecialchars($b['brand_name']); ?></option>
                  <?php endforeach; ?>
                </select>
              </div>

                <div class="d-grid">
                  <button type="submit" class="btn btn-sm btn-outline-primary"><i class="fa fa-search"></i> Search</button>
                </div>
            </form>
          </div>
        </div>
      </aside>

      <!-- RIGHT: product grid -->
      <section class="col-12 col-md-8">
  <h3 class="text-white mb-3 text-center">Featured Meals & Dishes</h3>
        <div class="products-grid grid-md">
          <?php foreach ($products as $p):
            $pid = (int)($p['product_id'] ?? 0);
            $title = htmlspecialchars($p['product_title'] ?? 'Untitled');
            $desc = htmlspecialchars($p['product_desc'] ?? '');
            $price = isset($p['product_price']) ? number_format((float)$p['product_price'], 2) : 'N/A';
            $img = $p['product_image'] ?? '';
            // order link: if not logged in, send to login with next param
            $orderUrl = $loggedIn ? "order.php?product_id={$pid}" : "login/login.php?next=" . urlencode("order.php?product_id={$pid}");
          ?>
            <div class="product-item">
              <div class="meal-card h-100">
                <?php if (!empty($img)): ?>
                  <div style="height:160px; overflow:hidden; display:flex; align-items:center; justify-content:center; background:#fff;">
                    <img src="<?php echo htmlspecialchars($img); ?>" alt="<?php echo $title; ?>" style="max-width:100%; max-height:160px; object-fit:cover;">
                  </div>
                <?php else: ?>
                  <div class="meal-icon"><i class="fa fa-utensils"></i></div>
                <?php endif; ?>

                <div class="p-3">
                  <h5><?php echo $title; ?></h5>
                  <p class="text-muted small"><?php echo mb_strlen($desc) > 120 ? htmlspecialchars(mb_substr($desc,0,120)) . '...' : $desc; ?></p>
                  <div class="d-flex justify-content-between align-items-center">
                    <span class="text-muted small">From GHS<?php echo $price; ?></span>
                    <button class="btn btn-sm order-btn" onclick="addToCart(<?php echo $pid; ?>)">Add to Cart</button>
                  </div>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </section>
    </div>
  </main>

  <footer>
    © <?php echo date('Y'); ?> Afro Bites Kitchen - Bringing home the flavors of the continent.
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="js/cart.js"></script>
  <script src="js/checkout.js"></script>
  <!-- optional product search JS if you have it -->
  <script src="js/product_search.js"></script>
  <script>
    // Prevent name-only searches unless category AND brand are selected
    $(function(){
      $('#search-form').on('submit', function(e){
        try {
          var q = $('#search-query').val() ? $('#search-query').val().trim() : '';
          var cat = $('#filter-category').val();
          var brand = $('#filter-brand').val();
          if (q !== '' && (!cat || cat === '') && (!brand || brand === '')) {
            e.preventDefault();
            alert('Please select both a Category and a Brand before searching by name.');
            $('#filter-category').focus();
            return false;
          }
        } catch (err) {
          // fail silently and allow submit if JS errors occur
        }
      });
    });
  </script>
</body>
</html>


