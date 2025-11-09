<?php
// controllers/product_controller.php

class ProductController
{
    protected $model;

    public function __construct()
    {
        $paths = [
            __DIR__ . '/../classes/product_class.php',
            __DIR__ . '/../../classes/product_class.php',
            __DIR__ . '/classes/product_class.php'
        ];
        $found = false;
        foreach ($paths as $p) {
            if (file_exists($p)) { require_once $p; $found = true; break; }
        }
        if (!$found || !class_exists('product_class')) {
            throw new Exception('Product model not found.');
        }
        $this->model = new product_class();
    }

    // payload: product data
    public function add_product_ctr(array $payload)
    {
        // basic checks
        if (empty($payload['product_cat']) || empty($payload['product_brand']) || empty($payload['product_title'])) {
            return ['success' => false, 'message' => 'Missing required product fields'];
        }
        if (!method_exists($this->model, 'add')) return ['success' => false, 'message' => 'Model method add() not implemented'];
        $newId = $this->model->add($payload);
        if ($newId) return ['success' => true, 'message' => 'Product added successfully', 'product_id' => (int)$newId];
        return ['success' => false, 'message' => 'Failed to add product'];
    }

    public function update_product_ctr(array $payload)
    {
        if (empty($payload['product_id']) || empty($payload['product_cat']) || empty($payload['product_brand']) || empty($payload['product_title'])) {
            return ['success' => false, 'message' => 'Missing required product fields'];
        }
        if (!method_exists($this->model, 'update')) return ['success' => false, 'message' => 'Model method update() not implemented'];
        $ok = $this->model->update($payload['product_id'], $payload);
        if ($ok) return ['success' => true, 'message' => 'Product updated successfully'];
        return ['success' => false, 'message' => 'Failed to update product'];
    }

    public function delete_product_ctr(int $product_id, $user_id = null)
    {
        if ($product_id <= 0) return ['success' => false, 'message' => 'Invalid product id'];
        if (!method_exists($this->model, 'delete')) return ['success' => false, 'message' => 'Model method delete() not implemented'];
        $ok = $this->model->delete($product_id, $user_id);
        if ($ok) return ['success' => true, 'message' => 'Product deleted'];
        return ['success' => false, 'message' => 'Failed to delete product'];
    }

    public function fetch_products_ctr($user_id = null)
    {
        if (method_exists($this->model, 'get_all_by_user')) {
            $rows = $this->model->get_all_by_user($user_id);
        } elseif (method_exists($this->model, 'get_all')) {
            $rows = $this->model->get_all();
        } else {
            throw new Exception('No fetch method available on product model.');
        }

        // Optionally group by category->brand
        $grouped = [];
        foreach ($rows as $r) {
            $cat = $r['product_cat'] ?? ($r['cat_id'] ?? 0);
            $cat_name = $r['cat_name'] ?? $r['category_name'] ?? 'Uncategorized';
            $brand = $r['product_brand'] ?? ($r['brand_id'] ?? 0);
            $brand_name = $r['brand_name'] ?? $r['brand'] ?? 'Unknown';

            if (!isset($grouped[$cat])) $grouped[$cat] = ['cat_id' => $cat, 'cat_name' => $cat_name, 'brands' => []];
            if (!isset($grouped[$cat]['brands'][$brand])) $grouped[$cat]['brands'][$brand] = ['brand_id' => $brand, 'brand_name' => $brand_name, 'products' => []];
            $grouped[$cat]['brands'][$brand]['products'][] = $r;
        }

        // convert nested maps to array structure
        $out = [];
        foreach ($grouped as $c) {
            $brandsArr = [];
            foreach ($c['brands'] as $b) $brandsArr[] = $b;
            $c['brands'] = $brandsArr;
            $out[] = $c;
        }
        return $out;
    }

    /**
     * View all products (customer-facing)
     * @param int $limit Optional limit for pagination
     * @param int $offset Optional offset for pagination
     * @return array|false Array of products or false on failure
     */
    public function view_all_products_ctr($limit = null, $offset = 0)
    {
        if (!method_exists($this->model, 'view_all_products')) {
            return false;
        }
        return $this->model->view_all_products($limit, $offset);
    }

    /**
     * Search products
     * @param string $query Search query
     * @param int $limit Optional limit for pagination
     * @param int $offset Optional offset for pagination
     * @return array|false Array of products or false on failure
     */
    public function search_products_ctr($query, $limit = null, $offset = 0)
    {
        if (!method_exists($this->model, 'search_products')) {
            return false;
        }
        return $this->model->search_products($query, $limit, $offset);
    }

    /**
     * Filter products by category
     * @param int $cat_id Category ID
     * @param int $limit Optional limit for pagination
     * @param int $offset Optional offset for pagination
     * @return array|false Array of products or false on failure
     */
    public function filter_products_by_category_ctr($cat_id, $limit = null, $offset = 0)
    {
        if (!method_exists($this->model, 'filter_products_by_category')) {
            return false;
        }
        return $this->model->filter_products_by_category($cat_id, $limit, $offset);
    }

    /**
     * Filter products by brand
     * @param int $brand_id Brand ID
     * @param int $limit Optional limit for pagination
     * @param int $offset Optional offset for pagination
     * @return array|false Array of products or false on failure
     */
    public function filter_products_by_brand_ctr($brand_id, $limit = null, $offset = 0)
    {
        if (!method_exists($this->model, 'filter_products_by_brand')) {
            return false;
        }
        return $this->model->filter_products_by_brand($brand_id, $limit, $offset);
    }

    /**
     * View single product
     * @param int $id Product ID
     * @return array|false Product data or false on failure
     */
    public function view_single_product_ctr($id)
    {
        if (!method_exists($this->model, 'view_single_product')) {
            return false;
        }
        return $this->model->view_single_product($id);
    }

    /**
     * Composite search with multiple filters (EXTRA CREDIT)
     * @param array $filters Search filters
     * @param int $limit Optional limit for pagination
     * @param int $offset Optional offset for pagination
     * @return array|false Array of products or false on failure
     */
    public function composite_search_ctr($filters = [], $limit = null, $offset = 0)
    {
        if (!method_exists($this->model, 'composite_search')) {
            return false;
        }
        return $this->model->composite_search($filters, $limit, $offset);
    }
}
