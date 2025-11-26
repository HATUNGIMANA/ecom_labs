<?php
// actions/debug_uploads.php
// Small admin-only debug endpoint to inspect uploads folder resolution and product_image DB values.
if (session_status() === PHP_SESSION_NONE) session_start();

// Try to locate core.php
$core_paths = [__DIR__ . '/../settings/core.php', __DIR__ . '/../../settings/core.php', __DIR__ . '/settings/core.php'];
$found = false;
foreach ($core_paths as $p) { if (file_exists($p)) { require_once $p; $found = true; break; } }
if (!$found) { header('Content-Type: text/plain'); echo "core.php not found"; exit; }
if (!function_exists('is_admin') || !is_admin()) { header('HTTP/1.1 403 Forbidden'); echo "Forbidden: admin only"; exit; }

// Find candidate uploads locations (same logic as upload handler)
$candidates = [
    __DIR__ . '/../uploads',
    __DIR__ . '/../../uploads',
    __DIR__ . '/../../../uploads',
];
if (!empty($_SERVER['DOCUMENT_ROOT'])) $candidates[] = rtrim($_SERVER['DOCUMENT_ROOT'], DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'uploads';

$foundUploads = false;
$uploads_base = '';
$tried = [];
foreach ($candidates as $cand) {
    $tried[] = $cand;
    $rp = realpath($cand);
    if ($rp !== false && is_dir($rp)) { $uploads_base = $rp; $foundUploads = true; break; }
}

// Connect to DB and fetch some products
$db_info = ['ok' => false, 'error' => null, 'products' => []];
$dbp = __DIR__ . '/../settings/db_class.php';
if (file_exists($dbp)) {
    require_once $dbp;
    $dbc = new db_connection();
    if ($dbc->db_connect()) {
        $db = $dbc->db;
        $res = $db->query("SELECT product_id, product_title, product_image FROM products ORDER BY product_id DESC LIMIT 50");
        if ($res) {
            while ($r = $res->fetch_assoc()) $db_info['products'][] = $r;
            $res->free();
            $db_info['ok'] = true;
        } else {
            $db_info['error'] = $db->error;
        }
    } else {
        $db_info['error'] = 'db_connect failed';
    }
} else {
    $db_info['error'] = 'db_class.php missing';
}

// List a few files under uploads for preview
$files_preview = [];
if ($foundUploads) {
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($uploads_base, RecursiveDirectoryIterator::SKIP_DOTS));
    $count = 0;
    foreach ($it as $fileinfo) {
        if ($fileinfo->isFile()) {
            $files_preview[] = str_replace('\\','/', substr($fileinfo->getPathname(), strlen($uploads_base)+1));
            $count++;
            if ($count >= 50) break;
        }
    }
}

// Output report
header('Content-Type: text/plain; charset=utf-8');
echo "DEBUG: uploads resolution\n";
echo "Candidates tried:\n" . implode("\n", $tried) . "\n\n";
if ($foundUploads) {
    echo "Resolved uploads base (filesystem):\n" . $uploads_base . "\n";
    echo "Doc root: " . ($_SERVER['DOCUMENT_ROOT'] ?? '(none)') . "\n";
    echo "Sample files (relative to uploads/):\n";
    foreach ($files_preview as $f) echo " - $f\n";
} else {
    echo "No uploads folder found in candidates.\n";
}

echo "\nDB products (product_id -> product_image):\n";
if ($db_info['ok']) {
    foreach ($db_info['products'] as $p) {
        echo $p['product_id'] . ' -> ' . ($p['product_image'] ?? '(null)') . ' | ' . ($p['product_title'] ?? '') . "\n";
    }
} else {
    echo "DB error: " . ($db_info['error'] ?? 'unknown') . "\n";
}

echo "\nEND DEBUG\n";

exit;

?>
