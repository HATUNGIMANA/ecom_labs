<?php
// actions/fix_product_image_paths.php
// Admin-only one-off script to normalize product_image DB entries to root-relative /uploads/...
if (session_status() === PHP_SESSION_NONE) session_start();

// include core helper
$core_paths = [__DIR__ . '/../settings/core.php', __DIR__ . '/../../settings/core.php', __DIR__ . '/settings/core.php'];
$found = false;
foreach ($core_paths as $p) { if (file_exists($p)) { require_once $p; $found = true; break; } }
if (!$found) { header('Content-Type: text/plain'); echo "core.php not found"; exit; }
if (!function_exists('is_admin') || !is_admin()) { header('HTTP/1.1 403 Forbidden'); echo "Forbidden: admin only"; exit; }

// Find uploads base (same logic as upload handler)
$candidates = [
    __DIR__ . '/../uploads',
    __DIR__ . '/../../uploads',
    __DIR__ . '/../../../uploads',
];
if (!empty($_SERVER['DOCUMENT_ROOT'])) $candidates[] = rtrim($_SERVER['DOCUMENT_ROOT'], DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'uploads';

$uploads_base = false; $tried = [];
foreach ($candidates as $cand) { $tried[] = $cand; $rp = realpath($cand); if ($rp !== false && is_dir($rp)) { $uploads_base = $rp; break; } }

header('Content-Type: text/plain; charset=utf-8');
echo "Fix product_image paths - admin only\n";
echo "Candidates tried:\n" . implode("\n", $tried) . "\n\n";
if (!$uploads_base) { echo "ERROR: uploads folder not found. Aborting.\n"; exit; }

echo "Resolved uploads base: $uploads_base\n\n";

// DB connect
$dbp = __DIR__ . '/../settings/db_class.php';
if (!file_exists($dbp)) { echo "DB helper missing: $dbp\n"; exit; }
require_once $dbp;
$dbc = new db_connection();
if (!$dbc->db_connect()) { echo "DB connect failed\n"; exit; }
$db = $dbc->db;

// Fetch products with non-empty product_image
$res = $db->query("SELECT product_id, product_image FROM products WHERE product_image IS NOT NULL AND product_image <> ''");
if (!$res) { echo "DB query failed: " . $db->error . "\n"; exit; }

$changes = 0; $skipped = 0; $rows = [];
while ($r = $res->fetch_assoc()) $rows[] = $r;
$res->free();

foreach ($rows as $r) {
    $pid = (int)$r['product_id'];
    $img = trim($r['product_image']);
    if ($img === '') { $skipped++; continue; }

    // normalize slashes
    $s = str_replace('\\','/',$img);

    // If it's an absolute URL, skip
    if (preg_match('#^https?://#i', $s)) { $skipped++; continue; }

    // If it already starts with /uploads/, check file exists
    $candidate_rel = '';
    if (strpos($s, '/uploads/') === 0) {
        $candidate_rel = ltrim($s, '/');
    } elseif (strpos($s, 'uploads/') !== false) {
        $pos = strpos($s, 'uploads/');
        $candidate_rel = substr($s, $pos);
    } else {
        // Not referencing uploads folder explicitly — skip
        $skipped++; continue;
    }

    $fs_path = rtrim($uploads_base, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $candidate_rel);
    if (file_exists($fs_path)) {
        // Determine web prefix. If uploads_base is under /home/{user}/public_html, use '/~{user}/uploads/...'
        $uploads_base_rp = str_replace('\\','/', realpath($uploads_base));
        $new_db = '';
        if (preg_match('#/home/([^/]+)/public_html#i', $uploads_base_rp, $mm)) {
            $uname = $mm[1];
            $new_db = '/~' . $uname . '/' . ltrim($candidate_rel, '/');
        } else {
            // default to root-relative '/uploads/...'
            $new_db = '/' . ltrim($candidate_rel, '/');
        }
        $new_db = str_replace('\\','/', $new_db);
        // Update only if different
        if ($new_db !== $img) {
            $stmt = $db->prepare('UPDATE products SET product_image = ? WHERE product_id = ?');
            if ($stmt) {
                $stmt->bind_param('si', $new_db, $pid);
                if ($stmt->execute()) { $changes++; echo "Updated product $pid -> $new_db\n"; }
                else { echo "Failed update $pid: " . $stmt->error . "\n"; }
                $stmt->close();
            } else {
                echo "Prepare failed for product $pid: " . $db->error . "\n";
            }
        } else {
            // already correct
        }
    } else {
        echo "File not found for product $pid: looked for $fs_path (DB had: $img)\n";
        $skipped++;
    }
}

echo "\nDone. Changes: $changes, Skipped: $skipped\n";
exit;

?>
