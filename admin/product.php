<?php
// admin/product.php
// Admin interface: view & add products (no image uploads)
try {
  // Try multiple locations for core.php (some hosts use different document roots)
  $core_paths = [
    __DIR__ . '/../settings/core.php',
    __DIR__ . '/../../settings/core.php',
    __DIR__ . '/settings/core.php'
  ];
  $found = false;
  foreach ($core_paths as $p) {
    if (file_exists($p)) { require_once $p; $found = true; break; }
  }
  if (!$found) throw new Exception('Core file not found');

  // Check if user is logged in and an admin
  if (!function_exists('is_logged_in') || !function_exists('is_admin') || !is_logged_in() || !is_admin()) {
    header('Location: ../login/login.php');
    exit;
  }

  $customer_id = get_user_id();
} catch (Throwable $ex) {
  // write a small diagnostic to a local file for the host to inspect
  $logPath = __DIR__ . '/product_error.log';
  $msg = '[' . date('c') . '] admin/product.php exception: ' . $ex->getMessage() . "\n" . $ex->getTraceAsString() . "\n\n";
  @file_put_contents($logPath, $msg, FILE_APPEND | LOCK_EX);
  error_log('admin/product.php exception: ' . $ex->getMessage());
  http_response_code(500);
  echo '<h1>Server error</h1><p>Unable to load product admin page. Check server logs.</p>';
  echo '<p class="small-muted">(Diagnostic log: ' . htmlspecialchars(basename($logPath)) . ')</p>';
  exit;
}

// Avoid doing a server-side DB query here (some hosts have different DB credentials).
// Populate categories client-side via AJAX (actions/fetch_category_action.php).
$categories = [];
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Admin - Products</title>

  <link href="../css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

  <style>
    body { background:#fff; font-family: Arial, sans-serif; padding:20px; }
    .topbar { display:flex; justify-content:space-between; align-items:center; margin-bottom:18px; }
    .card { border-radius:10px; }
    .small-muted { color:#666; font-size:.9rem; }
  </style>
</head>
<body>

  <div class="topbar">
    <h4 class="mb-0">Product Management</h4>
    <div>
      <a href="../index.php" class="btn btn-sm btn-outline-secondary me-2"><i class="fa fa-home"></i> Home</a>
      <a href="brand.php" class="btn btn-sm btn-outline-primary me-2"><i class="fa fa-tags"></i> Brands</a>
      <a href="../login/logout.php" class="btn btn-sm btn-outline-danger"><i class="fa fa-sign-out-alt"></i> Logout</a>
    </div>
  </div>

  <div class="row g-4">
    <div class="col-lg-5">
      <div class="card p-3">
        <h5>Add Product</h5>
        <p class="small-muted">Add a product tied to a category and brand. Images are not handled in this lab.</p>

        <form id="product-form" class="mt-3">
          <div class="mb-2">
            <label for="product_cat" class="form-label">Category</label>
            <select id="product_cat" name="product_cat" class="form-select" required>
              <option value="">Select category</option>
              <?php foreach ($categories as $c): ?>
                <option value="<?php echo (int)$c['cat_id']; ?>"><?php echo htmlspecialchars($c['cat_name']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="mb-2">
            <label for="product_brand" class="form-label">Brand</label>
            <select id="product_brand" name="product_brand" class="form-select" required>
              <option value="">Select brand (choose category first)</option>
            </select>
          </div>

          <div class="mb-2">
            <label for="product_title" class="form-label">Product Title</label>
            <input id="product_title" name="product_title" class="form-control" required maxlength="200" />
          </div>

          <div class="mb-2">
            <label for="product_price" class="form-label">Price (GHS)</label>
            <input id="product_price" name="product_price" class="form-control" required />
          </div>

          <div class="mb-2">
            <label for="product_desc" class="form-label">Description</label>
            <textarea id="product_desc" name="product_desc" class="form-control" rows="3"></textarea>
          </div>

          <div class="mb-2">
            <label for="product_keywords" class="form-label">Keywords</label>
            <input id="product_keywords" name="product_keywords" class="form-control" maxlength="150" />
          </div>

          <div class="mb-2">
            <label for="product_image" class="form-label">Product Image (optional)</label>
            <input id="product_image" name="product_image" type="file" accept="image/*" class="form-control" />
            <div class="small-muted">Uploads will be stored in the server's <code>uploads/</code> directory.</div>
          </div>

          <div>
            <button type="submit" class="btn btn-primary">Save Product</button>
          </div>
        </form>

      </div>
    </div>

    <div class="col-lg-7">
      <div class="card p-3">
        <h5 class="mb-3">Products</h5>
        <div id="products-container">
          <!-- js/product.js will render products here via AJAX -->
          <div class="text-center py-4 text-muted">Loading products...</div>
        </div>
      </div>
    </div>
  </div>

  <!-- Dependencies -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

  <!-- product + brand JS -->
  <!-- Populate category select via AJAX to avoid server-side DB dependency -->
  <script>
    (function(){
      if (typeof window.jQuery === 'undefined') return;
      $(function(){
        $.post('../actions/fetch_category_action.php', { action: 'fetch' }, function(res){
          if (!res || !res.success || !Array.isArray(res.data) || !res.data.length) {
            $('#product_cat').after('<div class="small-muted text-danger mt-2">Could not load categories. Please check server logs or create categories first.</div>');
            console.warn('fetch_category_action returned empty or error:', res);
            return;
          }
          const sel = $('#product_cat');
          sel.find('option:not([value=""])').remove();
          res.data.forEach(function(c){
            sel.append('<option value="'+ (c.cat_id || c.id || '') +'">'+ (c.cat_name || c.name || '') +'</option>');
          });
        }, 'json').fail(function(xhr){ console.warn('Could not load categories for product form.', xhr); $('#product_cat').after('<div class="small-muted text-danger mt-2">Failed to load categories (network error). See console for details.</div>'); });
      });
    })();
  </script>

  <script src="../js/brand.js?v=<?php echo time(); ?>"></script>
  <script src="../js/product.js?v=<?php echo time(); ?>"></script>

  <script>
  // When category select changes, populate the brand select from brand_action fetch (client-side)
  $(function() {
      function populateBrandsForCategory(groupedData) {
          const catId = $('#product_cat').val();
          const brandSelect = $('#product_brand');
          brandSelect.empty();
          brandSelect.append('<option value="">Select brand</option>');
          if (!catId) return;
          
          console.log('Populating brands for category:', catId, 'Grouped data:', groupedData);
          
          // groupedData is expected as returned by brand_action fetch: [{cat_id,cat_name,brands:[...]}]
          let foundBrands = false;
          groupedData.forEach(cat => {
              // Match by cat_id, or if cat_id is 0/undefined, show all brands
              if (String(cat.cat_id) === String(catId) || cat.cat_id == 0 || !cat.cat_id) {
                  (cat.brands || []).forEach(b => {
                      brandSelect.append('<option value="'+ b.brand_id +'">'+ (b.brand_name || '') +'</option>');
                      foundBrands = true;
                  });
              }
          });
          
          // If no brands found for the category, show all brands (fallback)
          if (!foundBrands) {
              console.log('No brands found for category, showing all brands');
              groupedData.forEach(cat => {
                  (cat.brands || []).forEach(b => {
                      brandSelect.append('<option value="'+ b.brand_id +'">'+ (b.brand_name || '') +'</option>');
                  });
              });
          }
      }

      // load brands once and keep in memory
      let cachedBrandGroups = null;
      function loadBrandGroupsOnce(cb) {
          if (cachedBrandGroups) { cb(cachedBrandGroups); return; }
          $.post('../actions/brand_action.php', { action: 'fetch' }, function(res) {
              if (res && res.success) {
                  cachedBrandGroups = res.data || [];
                  cb(cachedBrandGroups);
              } else {
                  console.error('Failed to load brands:', res && res.message);
                  cb([]);
              }
          }, 'json').fail(function(){ cb([]); });
      }

      $('#product_cat').on('change', function() {
          loadBrandGroupsOnce(function(groups) {
              populateBrandsForCategory(groups);
          });
      });
      
      // Debug: Log when brands are loaded
      console.log('Brand dropdown handler initialized');

      // Also populate brand select on page load if a category is already selected (rare)
      if ($('#product_cat').val()) {
          loadBrandGroupsOnce(function(groups) { populateBrandsForCategory(groups); });
      }
  });
  </script>
</body>
</html>
