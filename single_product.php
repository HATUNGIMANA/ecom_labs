<?php
// single_product.php - Display single dish/menu item details
session_start();

require_once 'settings/core.php';
require_once 'controllers/product_controller.php';

$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($product_id <= 0) {
    header('Location: all_product.php');
    exit;
}

$controller = new ProductController();
$product = $controller->view_single_product_ctr($product_id);

if (!$product || empty($product)) {
    header('Location: all_product.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['product_title']); ?> - Afro Bites Kitchen</title>
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
        .product-image-container {
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            background: linear-gradient(135deg, #f5f5f5 0%, #e0e0e0 100%);
        }
        .product-image {
            width: 100%;
            height: 400px;
            object-fit: cover;
        }
        .product-image-placeholder {
            width: 100%;
            height: 400px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #999;
            font-size: 4rem;
        }
        .price-display {
            font-size: 2rem;
            color: var(--accent);
            font-weight: bold;
        }
        .info-badge {
            display: inline-block;
            padding: 6px 12px;
            background: #e9ecef;
            border-radius: 6px;
            margin-right: 8px;
            margin-bottom: 8px;
        }
    </style>
 </head>
 <body>
    <?php include __DIR__ . '/partials/navbar.php'; ?>

    <div class="container mb-5">
        <div class="row">
            <!-- Product Image -->
            <div class="col-md-6 mb-4">
                <div class="product-image-container">
                    <?php if (!empty($product['product_image'])): ?>
                        <?php
                            $pi = $product['product_image'];
                            $pi_url = $pi;
                            if (function_exists('site_base_url') && strpos($pi, '/') !== 0) {
                                $pi_url = rtrim(site_base_url(), '/') . '/' . ltrim($pi, '/');
                            }
                        ?>
                        <img src="<?php echo htmlspecialchars($pi_url); ?>" 
                             alt="<?php echo htmlspecialchars($product['product_title']); ?>" 
                             class="product-image">
                    <?php else: ?>
                        <div class="product-image-placeholder">
                            <i class="fas fa-utensils"></i>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Product Details -->
            <div class="col-md-6">
                <h1 class="mb-3"><?php echo htmlspecialchars($product['product_title']); ?></h1>
                
                <div class="mb-3">
                    <span class="price-display">GHS<?php echo number_format($product['product_price'], 2); ?></span>
                </div>

                <div class="mb-4">
                    <div class="info-badge">
                        <i class="fas fa-tag me-1"></i>
                        <strong>Category:</strong> <?php echo htmlspecialchars($product['cat_name'] ?? 'Uncategorized'); ?>
                    </div>
                    <?php if (!empty($product['brand_name'])): ?>
                        <div class="info-badge">
                            <i class="fas fa-store me-1"></i>
                            <strong>Brand/Cuisine:</strong> <?php echo htmlspecialchars($product['brand_name']); ?>
                        </div>
                    <?php endif; ?>
                    <div class="info-badge">
                        <i class="fas fa-hashtag me-1"></i>
                        <strong>Product ID:</strong> #<?php echo $product['product_id']; ?>
                    </div>
                </div>

                <?php if (!empty($product['product_desc'])): ?>
                    <div class="mb-4">
                        <h5>Description</h5>
                        <p class="text-muted"><?php echo nl2br(htmlspecialchars($product['product_desc'])); ?></p>
                    </div>
                <?php endif; ?>

                <?php if (!empty($product['product_keywords'])): ?>
                    <div class="mb-4">
                        <h5>Keywords</h5>
                        <p class="text-muted">
                            <?php 
                            $keywords = explode(',', $product['product_keywords']);
                            foreach ($keywords as $keyword) {
                                echo '<span class="badge bg-secondary me-1">' . htmlspecialchars(trim($keyword)) . '</span>';
                            }
                            ?>
                        </p>
                    </div>
                <?php endif; ?>

                <div class="d-grid gap-2 d-md-flex">
                    <button class="btn btn-primary btn-lg flex-grow-1" onclick="addToCart(<?php echo $product['product_id']; ?>)">
                        <i class="fas fa-cart-plus me-2"></i>Add to Cart
                    </button>
                    <a href="all_product.php" class="btn btn-outline-secondary btn-lg">
                        <i class="fas fa-arrow-left me-2"></i>Continue Shopping
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="js/cart.js"></script>
    <script src="js/checkout.js"></script>
</body>
</html>

