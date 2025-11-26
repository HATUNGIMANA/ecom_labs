<?php
// actions/upload_product_image_action.php
header('Content-Type: application/json; charset=utf-8');
if (session_status() === PHP_SESSION_NONE) session_start();

function json_response($ok, $msg, $extra = []) {
    while (ob_get_level()) ob_end_clean();
    echo json_encode(array_merge(['success' => (bool)$ok, 'message' => $msg], $extra));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_response(false, 'Invalid request method. Use POST.');

// Basic auth check: must be logged in and admin
$core_paths = [__DIR__ . '/../settings/core.php', __DIR__ . '/../../settings/core.php', __DIR__ . '/settings/core.php'];
$foundCore = false;
foreach ($core_paths as $p) { if (file_exists($p)) { require_once $p; $foundCore = true; break; } }
if (!$foundCore) json_response(false, 'Server misconfiguration (core).');
if (!function_exists('is_logged_in') || !function_exists('is_admin')) json_response(false, 'Server misconfiguration: auth helpers missing.');
if (!is_logged_in() || !is_admin()) json_response(false, 'Unauthorized. Admin login required.');

// Validate inputs
$product_id = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
if ($product_id <= 0) json_response(false, 'product_id is required');

if (!isset($_FILES['product_image'])) json_response(false, 'No file uploaded');

$file = $_FILES['product_image'];
if (!is_uploaded_file($file['tmp_name'])) json_response(false, 'Upload failed or file not uploaded');

// Validate file size (optional) and mime type
$allowed_mimes = ['image/jpeg','image/png','image/gif','image/webp'];
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->file($file['tmp_name']);
if (!in_array($mime, $allowed_mimes)) json_response(false, 'Unsupported file type: ' . $mime);

// Determine user id for organizing uploads
$user_id = $_SESSION['customer_id'] ?? 0;
if ($user_id <= 0) $user_id = 0; // allow zero for system/admin

// Build uploads base directory (must be the existing uploads/ folder)
// Try several likely locations: project/uploads, parent/uploads, document root/uploads
$candidates = [
    __DIR__ . '/../uploads',        // project_root/uploads
    __DIR__ . '/../../uploads',     // parent/uploads
    __DIR__ . '/../../../uploads',  // grandparent/uploads
];
// also try document root
if (!empty($_SERVER['DOCUMENT_ROOT'])) $candidates[] = rtrim($_SERVER['DOCUMENT_ROOT'], DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'uploads';

$uploads_base = false;
$tried = [];
foreach ($candidates as $cand) {
    $tried[] = $cand;
    $rp = realpath($cand);
    if ($rp !== false && is_dir($rp)) { $uploads_base = $rp; break; }
}
if ($uploads_base === false) {
    json_response(false, 'Upload directory not available on server. Paths tried: ' . implode(', ', $tried));
}

// Build target directory: uploads/u{user_id}/p{product_id}
$target_dir = $uploads_base . DIRECTORY_SEPARATOR . 'u' . (int)$user_id . DIRECTORY_SEPARATOR . 'p' . (int)$product_id;
if (!is_dir($target_dir)) {
    if (!mkdir($target_dir, 0755, true)) {
        json_response(false, 'Failed to create upload directory');
    }
}

// Resolve realpath of target dir and ensure it is inside uploads_base
$real_target = realpath($target_dir);
if ($real_target === false || strpos($real_target, $uploads_base) !== 0) {
    json_response(false, 'Invalid upload directory');
}

// Sanitize filename and create unique name
$origName = basename($file['name']);
$ext = pathinfo($origName, PATHINFO_EXTENSION);
$ext = strtolower(preg_replace('/[^a-z0-9]/i','', $ext));
if ($ext === '') json_response(false, 'File must have an extension');
$allowed_ext = ['jpg','jpeg','png','gif','webp'];
if (!in_array($ext, $allowed_ext)) json_response(false, 'File extension not allowed');

$baseName = preg_replace('/[^A-Za-z0-9_\-]/', '_', pathinfo($origName, PATHINFO_FILENAME));
$unique = time() . '_' . bin2hex(random_bytes(4));
$filename = $baseName . '_' . $unique . '.' . $ext;
$target_path = $real_target . DIRECTORY_SEPARATOR . $filename;

// final containment check
$real_target_path = $real_target . DIRECTORY_SEPARATOR . $filename;
if (strpos(realpath(dirname($real_target_path)) ?: '', $uploads_base) !== 0) {
    json_response(false, 'Upload path resolved outside allowed folder');
}

// Move uploaded file
if (!move_uploaded_file($file['tmp_name'], $target_path)) {
    json_response(false, 'Failed to move uploaded file');
}

// Build DB path relative to project root (use forward slashes)
$relative_path = 'uploads/u' . (int)$user_id . '/p' . (int)$product_id . '/' . $filename;

// Determine web-accessible path to store in DB.
// If uploads folder is inside the server DOCUMENT_ROOT, store a document-root-relative path like '/uploads/..'
// Otherwise, store a path relative to the application base (e.g. '/your-app-folder/uploads/...').
$db_path = $relative_path; // fallback
// Compute web-accessible path based on the resolved uploads base directory.
$uploads_base_rp = str_replace('\\','/', realpath($uploads_base));
$db_path = '';
$docroot_rp = false;
if (!empty($_SERVER['DOCUMENT_ROOT'])) {
    $docroot_rp = str_replace('\\','/', realpath(rtrim($_SERVER['DOCUMENT_ROOT'], DIRECTORY_SEPARATOR)) ?: '');
}

// Compute a web-accessible path for the uploaded file.
// If the uploads folder is located in a typical userdir (/home/{user}/public_html/uploads)
// then the web path is '/~{user}/uploads/...'. Otherwise prefer document-root-relative
// or app-base paths. This handles shared-host/UserDir setups where the project
// is a sibling of uploads under the same user's public_html.
$uploads_base_rp = str_replace('\\','/', realpath($uploads_base));
$db_path = '';
// userdir pattern: /home/{username}/public_html[/...]
if (preg_match('#/home/([^/]+)/public_html#i', $uploads_base_rp, $m)) {
    $username = $m[1];
    $db_path = '/~' . $username . '/uploads/u' . (int)$user_id . '/p' . (int)$product_id . '/' . $filename;
} else {
    // if under DOCUMENT_ROOT, compute docroot-relative path
    if (!empty($_SERVER['DOCUMENT_ROOT'])) {
        $docroot_rp = str_replace('\\','/', realpath(rtrim($_SERVER['DOCUMENT_ROOT'], DIRECTORY_SEPARATOR)) ?: '');
        if ($docroot_rp !== '' && strpos($uploads_base_rp, $docroot_rp) === 0) {
            $web_uploads = substr($uploads_base_rp, strlen($docroot_rp));
            $web_uploads = '/' . trim($web_uploads, '/');
            $db_path = $web_uploads . '/u' . (int)$user_id . '/p' . (int)$product_id . '/' . $filename;
            $db_path = preg_replace('#/+#','/', $db_path);
        }
    }
}
// fallback: prefix with app base URL if still empty
if (empty($db_path)) {
    if (function_exists('site_base_url')) {
        $db_path = rtrim(site_base_url(), '/') . '/uploads/u' . (int)$user_id . '/p' . (int)$product_id . '/' . $filename;
        $db_path = preg_replace('#/+#','/', $db_path);
    } else {
        // as last resort use root-relative uploads
        $db_path = '/uploads/u' . (int)$user_id . '/p' . (int)$product_id . '/' . $filename;
    }
}

// Update products table
// Use db_class to get mysqli
$dbp = __DIR__ . '/../settings/db_class.php';
if (!file_exists($dbp)) json_response(false, 'Database helper missing');
require_once $dbp;
$dbc = new db_connection();
$conn = $dbc->db_conn();
if ($conn === false) {
    // try db_connect
    if (!$dbc->db_connect() || !$dbc->db) json_response(false, 'Database connection failed');
    $conn = $dbc->db;
}

// Ensure product exists and optionally belongs to user (skip ownership check to be minimal)
$stmt = $conn->prepare('UPDATE products SET product_image = ? WHERE product_id = ?');
if (!$stmt) json_response(false, 'DB prepare failed');
// Store the web-accessible path in the DB (use $db_path computed above)
$stmt->bind_param('si', $db_path, $product_id);
if (!$stmt->execute()) {
    $err = $stmt->error;
    $stmt->close();
    json_response(false, 'DB update failed: ' . $err);
}
$stmt->close();

json_response(true, 'Image uploaded', ['path' => $db_path]);
