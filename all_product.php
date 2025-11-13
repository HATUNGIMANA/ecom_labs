<?php
// all_product.php - Display all dishes/menu items
session_start();

require_once 'settings/core.php';
require_once 'controllers/product_controller.php';
require_once 'settings/db_class.php';

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

$controller = new ProductController();
$products = $controller->view_all_products_ctr($limit, $offset);
$totalProducts = count($controller->view_all_products_ctr()); // Get total count
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
    <title>All Dishes - Afro Bites Kitchen</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --accent: #b77a7a;
            --accent-dark: #a26363;
            --card-bg: rgba(255,255,255,0.95);
        }
        body {
            background-color: var(--accent);
            color: #222;
            font-family: Arial, Helvetica, sans-serif;
            min-height: 100vh;
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
        .filter-section {
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
        <h1 class="mb-4"><i class="fas fa-utensils me-2"></i>All Dishes</h1>

        <!-- Filter Section -->
        <div class="filter-section">
            <h5 class="mb-3">Filter Dishes</h5>
            <form method="GET" action="all_product.php" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Category</label>
                    <select name="cat_id" class="form-select" onchange="this.form.submit()">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['cat_id']; ?>" <?php echo (isset($_GET['cat_id']) && $_GET['cat_id'] == $cat['cat_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['cat_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Brand/Cuisine</label>
                    <select name="brand_id" class="form-select" onchange="this.form.submit()">
                        <option value="">All Brands</option>
                        <?php foreach ($brands as $brand): ?>
                            <option value="<?php echo $brand['brand_id']; ?>" <?php echo (isset($_GET['brand_id']) && $_GET['brand_id'] == $brand['brand_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($brand['brand_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <a href="all_product.php" class="btn btn-outline-secondary w-100">Clear Filters</a>
                </div>
            </form>
        </div>

        <!-- Apply filters if set -->
        <?php
        if (isset($_GET['cat_id']) && $_GET['cat_id'] > 0) {
            $products = $controller->filter_products_by_category_ctr((int)$_GET['cat_id'], $limit, $offset);
        } elseif (isset($_GET['brand_id']) && $_GET['brand_id'] > 0) {
            $products = $controller->filter_products_by_brand_ctr((int)$_GET['brand_id'], $limit, $offset);
        }
        ?>

        <!-- Products Grid -->
        <?php if (empty($products)): ?>
            <div class="alert alert-info text-center">
                <i class="fas fa-info-circle me-2"></i>No dishes found. Check back later!
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($products as $product): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card product-card h-100">
                            <div class="product-image">
                                <?php if (!empty($product['product_image'])): ?>
                                    <img src="<?php echo htmlspecialchars($product['product_image']); ?>" alt="<?php echo htmlspecialchars($product['product_title']); ?>">
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
                                <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo isset($_GET['cat_id']) ? '&cat_id=' . $_GET['cat_id'] : ''; ?><?php echo isset($_GET['brand_id']) ? '&brand_id=' . $_GET['brand_id'] : ''; ?>">Previous</a>
                            </li>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                <a class="page-link" href="?page=<?php echo $i; ?><?php echo isset($_GET['cat_id']) ? '&cat_id=' . $_GET['cat_id'] : ''; ?><?php echo isset($_GET['brand_id']) ? '&brand_id=' . $_GET['brand_id'] : ''; ?>"><?php echo $i; ?></a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($page < $totalPages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo isset($_GET['cat_id']) ? '&cat_id=' . $_GET['cat_id'] : ''; ?><?php echo isset($_GET['brand_id']) ? '&brand_id=' . $_GET['brand_id'] : ''; ?>">Next</a>
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
</body>
</html>

