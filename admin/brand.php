<?php
// admin/brand.php
// Admin interface to manage brands (create / edit / delete / list)
try {
  if (session_status() === PHP_SESSION_NONE) session_start();

  // include core auth helpers (adjust path if your project stores it elsewhere)
  $core_paths = [
    __DIR__ . '/../settings/core.php',
    __DIR__ . '/../../settings/core.php',
    __DIR__ . '/settings/core.php'
  ];
  $foundCore = false;
  foreach ($core_paths as $p) {
    if (file_exists($p)) { require_once $p; $foundCore = true; break; }
  }
  if (!$foundCore) {
    // fallback: cannot check auth
    header('Location: ../login/login.php');
    exit;
  }

  // auth check: must be logged in and admin
  if (!function_exists('is_logged_in') || !function_exists('is_admin') || !is_logged_in() || !is_admin()) {
    // redirect to login
    header('Location: ../login/login.php');
    exit;
  }

  // For robustness on different hosts we avoid doing a server-side DB query here.
  // Instead, the category select will be populated client-side via AJAX against
  // `actions/fetch_category_action.php`. This prevents DB connection failures in
  // the admin page bootstrap and keeps the UI responsive even if the server-side
  // configuration differs.
  $categories = [];
} catch (Throwable $ex) {
  // Write a short trace to a local log file to help diagnose hosting-specific problems
  $logPath = __DIR__ . '/brand_error.log';
  $msg = '[' . date('c') . '] admin/brand.php exception: ' . $ex->getMessage() . "\n" . $ex->getTraceAsString() . "\n\n";
  // best-effort write; suppress any warnings if open_basedir prevents writing
  @file_put_contents($logPath, $msg, FILE_APPEND | LOCK_EX);
  error_log('admin/brand.php exception: ' . $ex->getMessage());
  http_response_code(500);
  echo '<h1>Server error</h1><p>Unable to load brand admin page. Check server logs.</p>';
  echo '<p class="small-muted">(Diagnostic log written to: ' . htmlspecialchars(basename($logPath)) . ' — check server filesystem or ask the host to review PHP error logs.)</p>';
  exit;
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Admin — Brands</title>

  <link href="../css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

  <style>
    body { background: #f7f7f8; font-family: Arial, sans-serif; padding: 24px; }
    .topbar { display:flex; justify-content:space-between; align-items:center; gap:12px; margin-bottom:18px; }
    .card { border-radius:10px; }
    #brands-container .card { margin-bottom:12px; }
    .small-muted { color:#666; font-size:.9rem; }
  </style>
</head>
<body>
  <div class="topbar">
    <h4 class="mb-0">Brand Management</h4>
    <div>
      <a href="../index.php" class="btn btn-sm btn-outline-secondary me-2"><i class="fa fa-home"></i> Home</a>
      <a href="../login/logout.php" class="btn btn-sm btn-outline-danger"><i class="fa fa-sign-out-alt"></i> Logout</a>
    </div>
  </div>

  <div class="row g-4">
    <div class="col-lg-5">
      <div class="card p-3">
        <h5>Add Brand</h5>
        <p class="small-muted">Create a new brand and assign it to a category. Brand+Category combinations must be unique.</p>

        <?php if (empty($categories)): ?>
          <div class="alert alert-warning">No categories found. Please create categories first.</div>
        <?php endif; ?>

        <form id="brand-add-form" class="mt-3">
          <div class="mb-3">
            <label for="brand_name" class="form-label">Brand Name</label>
            <input id="brand_name" name="brand_name" class="form-control" maxlength="100" required />
          </div>

          <div class="mb-3">
            <label for="brand_cat" class="form-label">Category</label>
            <select id="brand_cat" name="cat_id" class="form-select" required>
              <option value="">Select category</option>
              <?php foreach ($categories as $c): ?>
                <option value="<?php echo (int)$c['cat_id']; ?>"><?php echo htmlspecialchars($c['cat_name']); ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div>
            <button type="submit" class="btn btn-primary">Add Brand</button>
          </div>
        </form>
      </div>

      <div class="card p-3 mt-3">
        <h6>Notes</h6>
        <ul class="small-muted">
          <li>Only administrators can access this page.</li>
          <li>Edit by clicking the Edit button next to a brand.</li>
          <li>Delete removes the brand (related products may be affected).</li>
        </ul>
      </div>
    </div>

    <div class="col-lg-7">
      <div class="card p-3">
        <h5 class="mb-3">Brands by Category</h5>
        <div id="brands-container">
          <!-- brand.js will render the grouped brand list here via AJAX -->
          <div class="text-center py-4 text-muted">Loading brands...</div>
        </div>
      </div>
    </div>
  </div>

  <!-- Dependencies -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

  <!-- Populate category select via AJAX to avoid server-side DB dependency -->
  <script>
    (function(){
      if (typeof window.jQuery === 'undefined') return; // jQuery not loaded for some reason
      $(function(){
        $.post('../actions/fetch_category_action.php', { action: 'fetch' }, function(res){
          if (!res || !res.success || !Array.isArray(res.data)) return; 
          const sel = $('#brand_cat');
          sel.find('option:not([value=""])').remove();
          res.data.forEach(function(c){
            sel.append('<option value="'+ (c.cat_id || c.id || '') +'">'+ (c.cat_name || c.name || '') +'</option>');
          });
        }, 'json').fail(function(){
          console.warn('Could not load categories for brand form.');
        });
      });
    })();
  </script>

  <!-- Your brand JS (AJAX -> actions/brand_action.php) -->
  <script src="../js/brand.js?v=<?php echo time(); ?>"></script>
</body>
</html>
