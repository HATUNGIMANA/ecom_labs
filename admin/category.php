<?php
// admin/category.php
try {
    require_once '../settings/core.php';

    // Check if user is logged in
    if (!is_logged_in()) {
        header('Location: ../login/login.php');
        exit;
    }

    // Check if user is admin
    if (!is_admin()) {
        header('Location: ../login/login.php');
        exit;
    }

    $customer_id = get_user_id();
} catch (Throwable $ex) {
    error_log('admin/category.php exception: ' . $ex->getMessage());
    http_response_code(500);
    echo '<h1>Server error</h1><p>Unable to load category admin page. Check server logs.</p>';
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Category Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            padding-top: 20px;
        }
        .btn-custom {
            background-color: #D19C97;
            border-color: #D19C97;
            color: #fff;
        }
        .btn-custom:hover {
            background-color: #b77a7a;
            border-color: #b77a7a;
            color: #fff;
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }
        .card-header {
            background-color: #D19C97;
            color: #fff;
            border-radius: 15px 15px 0 0 !important;
        }
        .table {
            margin-bottom: 0;
        }
        .table th {
            background-color: #f8f9fa;
            font-weight: 600;
        }
        input::placeholder {
            opacity: 0.5;
            font-style: italic;
        }
        input::-webkit-input-placeholder {
            opacity: 0.5;
            font-style: italic;
        }
        input::-moz-placeholder {
            opacity: 0.5;
            font-style: italic;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <h2><i class="fas fa-tags me-2"></i>Category Management</h2>
                    <a href="../index.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Home
                    </a>
                </div>
            </div>
        </div>

        <!-- Add Category Form -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-plus-circle me-2"></i>Add New Category</h5>
                    </div>
                    <div class="card-body">
                        <form id="add-category-form">
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="mb-3">
                                        <label for="cat_name" class="form-label">Category Name</label>
                                        <input type="text" class="form-control" id="cat_name" name="cat_name" 
                                            placeholder="Enter category name" required maxlength="100">
                                        <div class="invalid-feedback"></div>
                                    </div>
                                </div>
                                <div class="col-md-4 d-flex align-items-end">
                                    <button type="submit" class="btn btn-custom w-100" id="add-category-btn">
                                        <i class="fas fa-plus me-2"></i>Add Category
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Categories List -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-list me-2"></i>Categories</h5>
                    </div>
                    <div class="card-body">
                        <div id="loading-categories" class="text-center py-4">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                        <div id="categories-list" style="display: none;">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Category Name</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="categories-table-body">
                                    <!-- Categories will be loaded here -->
                                </tbody>
                            </table>
                        </div>
                        <div id="no-categories" style="display: none;" class="text-center py-4 text-muted">
                            <i class="fas fa-inbox fa-3x mb-3"></i>
                            <p>No categories found. Add your first category above.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Category Modal -->
    <div class="modal fade" id="editCategoryModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header" style="background-color: #D19C97; color: #fff;">
                    <h5 class="modal-title"><i class="fas fa-edit me-2"></i>Edit Category</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="edit-category-form">
                        <input type="hidden" id="edit_cat_id" name="cat_id">
                        <div class="mb-3">
                            <label for="edit_cat_name" class="form-label">Category Name</label>
                            <input type="text" class="form-control" id="edit_cat_name" name="cat_name" 
                                placeholder="Enter category name" required maxlength="100">
                            <div class="invalid-feedback"></div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-custom" id="update-category-btn">
                        <i class="fas fa-save me-2"></i>Update Category
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="../js/category.js"></script>
</body>
</html>

