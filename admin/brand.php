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

  // DB helper: attempt to open DB using several possible constant names
  function get_db_conn() {
    // look for common defines
    $hosts = [
      // modern alternative names
      ['host' => defined('DB_HOST') ? DB_HOST : null, 'user' => defined('DB_USER') ? DB_USER : null, 'pass' => defined('DB_PASS') ? DB_PASS : null, 'db' => defined('DB_NAME') ? DB_NAME : null],
      // older style
      ['host' => defined('SERVER') ? SERVER : null, 'user' => defined('USERNAME') ? USERNAME : null, 'pass' => defined('PASSWD') ? PASSWD : null, 'db' => defined('DATABASE') ? DATABASE : null],
      // fallback common XAMPP
      ['host' => 'localhost', 'user' => 'root', 'pass' => '', 'db' => 'shoppn']
    ];
    foreach ($hosts as $c) {
      if (empty($c['host']) || empty($c['user']) || empty($c['db'])) continue;
      $mysqli = @new mysqli($c['host'], $c['user'], $c['pass'] ?? '', $c['db']);
      if ($mysqli && !$mysqli->connect_errno) {
        // set charset
        $mysqli->set_charset('utf8mb4');
        return $mysqli;
      }
    }
    return null;
  }

  // Load categories for the Add Brand form
  $db = get_db_conn();
  $categories = [];
  if ($db) {
    $sql = "SELECT cat_id, cat_name FROM categories ORDER BY cat_name";
    if ($res = $db->query($sql)) {
      while ($r = $res->fetch_assoc()) {
        $categories[] = $r;
      }
      $res->free();
    }
    // don't close -- might be reused by other includes, but okay to close
    $db->close();
  }
} catch (Throwable $ex) {
  error_log('admin/brand.php exception: ' . $ex->getMessage());
  http_response_code(500);
  echo '<h1>Server error</h1><p>Unable to load brand admin page. Check server logs.</p>';
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

  <!-- Your brand JS (AJAX -> actions/brand_action.php) -->
  <script src="../js/brand.js?v=<?php echo time(); ?>"></script>
</body>
</html>
