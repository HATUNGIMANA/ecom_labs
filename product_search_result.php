<?php
// product_search_result.php - Display search results
session_start();

require_once 'settings/core.php';
require_once 'controllers/product_controller.php';
require_once 'settings/db_class.php';

$query = trim($_GET['q'] ?? '');
$cat_id = isset($_GET['cat_id']) ? (int)$_GET['cat_id'] : 0;
$brand_id = isset($_GET['brand_id']) ? (int)$_GET['brand_id'] : 0;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$controller = new ProductController();
$products = [];
$totalProducts = 0;

// Perform search based on filters
if (!empty($query) || $cat_id > 0 || $brand_id > 0) {
    // Use composite search if multiple filters
    if (!empty($query) && ($cat_id > 0 || $brand_id > 0)) {
        $filters = [
            'query' => $query,
            'cat_id' => $cat_id,
            'brand_id' => $brand_id
        ];
        $products = $controller->composite_search_ctr($filters, $limit, $offset);
        $allResults = $controller->composite_search_ctr($filters);
        $totalProducts = is_array($allResults) ? count($allResults) : 0;
    } elseif (!empty($query)) {
        // Search only
        $products = $controller->search_products_ctr($query, $limit, $offset);
        $allResults = $controller->search_products_ctr($query);
        $totalProducts = is_array($allResults) ? count($allResults) : 0;
    } elseif ($cat_id > 0) {
        // Filter by category
        $products = $controller->filter_products_by_category_ctr($cat_id, $limit, $offset);
        $allResults = $controller->filter_products_by_category_ctr($cat_id);
        $totalProducts = is_array($allResults) ? count($allResults) : 0;
    } elseif ($brand_id > 0) {
        // Filter by brand
        $products = $controller->filter_products_by_brand_ctr($brand_id, $limit, $offset);
        $allResults = $controller->filter_products_by_brand_ctr($brand_id);
        $totalProducts = is_array($allResults) ? count($allResults) : 0;
    }
} else {
    // No search criteria, show all
    $products = $controller->view_all_products_ctr($limit, $offset);
    $allResults = $controller->view_all_products_ctr();
    $totalProducts = is_array($allResults) ? count($allResults) : 0;
}

$totalPages = ceil($totalProducts / $limit);

// Get categories and brands for filters
$db = new db_connection();
$categories = [];
$brands = [];
if ($db->db_connect()) {
    $categories = $db->db_fetch_all("SELECT cat_id, cat_name FROM categories ORDER BY cat_name");
    if (!$categories) $categories = [];
    $brands = $db->db_fetch_all("SELECT brand_id, brand_name FROM brands ORDER BY brand_name");
    if (!$brands) $brands = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Results - Afro Bites Kitchen</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --accent: #b77a7a;
            --accent-dark: #a26363;
        }
        body {
            background-color: #f8f9fa;
            font-family: Arial, sans-serif;
        }
        .navbar {
            background: rgba(255,255,255,0.96);
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .product-card {
            transition: transform 0.2s, box-shadow 0.2s;
            border: none;
            border-radius: 12px;
            overflow: hidden;
        }
        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }
        .product-image {
            height: 200px;
            object-fit: cover;
            background: linear-gradient(135deg, #f5f5f5 0%, #e0e0e0 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #999;
        }
        .product-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .price-tag {
            color: var(--accent);
            font-weight: bold;
            font-size: 1.2rem;
        }
        .search-header {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
    </style>
 </head>
 <body>
    <?php include __DIR__ . '/partials/navbar.php'; ?>

    <div class="container mb-5">
        <!-- Search Header -->
        <div class="search-header">
            <h1 class="mb-3"><i class="fas fa-search me-2"></i>Search Results</h1>
            
            <?php if (!empty($query)): ?>
                <p class="text-muted">Searching for: <strong>"<?php echo htmlspecialchars($query); ?>"</strong></p>
            <?php endif; ?>
            
            <p class="text-muted">Found <strong><?php echo $totalProducts; ?></strong> result(s)</p>

            <!-- Refine Search Form -->
            <form id="refine-form" method="GET" action="product_search_result.php" class="row g-3 mt-3">
                <input type="hidden" name="q" value="<?php echo htmlspecialchars($query); ?>">
                <div class="col-md-4">
                    <label class="form-label">Refine by Category</label>
                    <select id="refine-cat" name="cat_id" class="form-select">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['cat_id']; ?>" <?php echo ($cat_id == $cat['cat_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['cat_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Refine by Brand/Cuisine</label>
                    <select id="refine-brand" name="brand_id" class="form-select">
                        <option value="">All Brands</option>
                        <?php foreach ($brands as $brand): ?>
                            <option value="<?php echo $brand['brand_id']; ?>" <?php echo ($brand_id == $brand['brand_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($brand['brand_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">Apply Filters</button>
                </div>
            </form>
        </div>

        <!-- Results -->
        <?php if (empty($products)): ?>
            <div class="alert alert-info text-center">
                <i class="fas fa-info-circle me-2"></i>No dishes found matching your search criteria. Try different keywords or filters.
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($products as $product): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card product-card h-100">
                            <div class="product-image">
                                <?php if (!empty($product['product_image'])): ?>
                                    <?php
                                        $pi = $product['product_image'];
                                        $pi_url = $pi;
                                        if (function_exists('site_base_url') && strpos($pi, '/') !== 0) {
                                            $pi_url = rtrim(site_base_url(), '/') . '/' . ltrim($pi, '/');
                                        }
                                    ?>
                                    <img src="<?php echo htmlspecialchars($pi_url); ?>" alt="<?php echo htmlspecialchars($product['product_title']); ?>">
                                <?php else: ?>
                                    <i class="fas fa-utensils fa-3x"></i>
                                <?php endif; ?>
                            </div>
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($product['product_title']); ?></h5>
                                <p class="text-muted small mb-2">
                                    <i class="fas fa-tag me-1"></i><?php echo htmlspecialchars($product['cat_name'] ?? 'Uncategorized'); ?>
                                    <?php if (!empty($product['brand_name'])): ?>
                                        | <i class="fas fa-store me-1"></i><?php echo htmlspecialchars($product['brand_name']); ?>
                                    <?php endif; ?>
                                </p>
                                <p class="price-tag mb-3">GHS<?php echo number_format($product['product_price'], 2); ?></p>
                                <div class="d-flex gap-2">
                                    <a href="single_product.php?id=<?php echo $product['product_id']; ?>" class="btn btn-outline-primary btn-sm flex-grow-1">
                                        <i class="fas fa-eye me-1"></i>View Details
                                    </a>
                                    <button class="btn btn-primary btn-sm" onclick="addToCart(<?php echo $product['product_id']; ?>)">
                                        <i class="fas fa-cart-plus"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <nav aria-label="Page navigation" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?q=<?php echo urlencode($query); ?>&cat_id=<?php echo $cat_id; ?>&brand_id=<?php echo $brand_id; ?>&page=<?php echo $page - 1; ?>">Previous</a>
                            </li>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?q=<?php echo urlencode($query); ?>&cat_id=<?php echo $cat_id; ?>&brand_id=<?php echo $brand_id; ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($page < $totalPages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?q=<?php echo urlencode($query); ?>&cat_id=<?php echo $cat_id; ?>&brand_id=<?php echo $brand_id; ?>&page=<?php echo $page + 1; ?>">Next</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="js/cart.js"></script>
    <script src="js/checkout.js"></script>
        // Prevent name-only searches unless both category and brand are selected
        document.addEventListener('DOMContentLoaded', function(){
            var form = document.getElementById('refine-form');
            if (!form) return;
            form.addEventListener('submit', function(e){
                try {
                    var qInput = document.querySelector('input[name="q"]');
                    var q = qInput ? qInput.value.trim() : '';
                    var cat = (document.getElementById('refine-cat') || {}).value || '';
                    var brand = (document.getElementById('refine-brand') || {}).value || '';
                    if (q !== '' && (!cat || cat === '') && (!brand || brand === '')) {
                        e.preventDefault();
                        alert('Please select both a Category and a Brand before searching by name.');
                        var el = document.getElementById('refine-cat');
                        if (el) el.focus();
                        return false;
                    }
                } catch (err) {
                    // allow submission on JS error
                }
            });
        });
    </script>
</body>
</html>

