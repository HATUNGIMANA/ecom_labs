<?php
// controllers/brand_controller.php

// Simple controller that delegates to classes/brand_class.php
class BrandController
{
    protected $model;

    public function __construct()
    {
        // try include model from common paths
        $paths = [
            __DIR__ . '/../classes/brand_class.php',
            __DIR__ . '/../../classes/brand_class.php',
            __DIR__ . '/classes/brand_class.php'
        ];
        $found = false;
        foreach ($paths as $p) {
            if (file_exists($p)) {
                require_once $p;
                $found = true;
                break;
            }
        }
        if (!$found || !class_exists('brand_class')) {
            throw new Exception('Brand model not found.');
        }
        $this->model = new brand_class();
    }

    // payload: ['brand_name','cat_id','created_by']
    public function add_brand_ctr(array $payload)
    {
        // validate
        if (empty($payload['brand_name']) || empty($payload['cat_id'])) {
            return ['success' => false, 'message' => 'Missing brand name or category'];
        }

        // uniqueness check (if model supports it)
        if (method_exists($this->model, 'exists_brand_for_user')) {
            $exists = $this->model->exists_brand_for_user($payload['brand_name'], $payload['cat_id'], $payload['created_by']);
            if ($exists) return ['success' => false, 'message' => 'Brand already exists for this category'];
        }

    // call model add - expected to return inserted id or false
        if (!method_exists($this->model, 'add')) {
            return ['success' => false, 'message' => 'Model method add() not implemented'];
        }
        $newId = $this->model->add($payload);
        if ($newId) {
            return ['success' => true, 'message' => 'Brand added successfully', 'brand_id' => (int)$newId];
        }
        $err = isset($this->model->last_error) && $this->model->last_error ? $this->model->last_error : 'Failed to add brand';
        error_log('BrandController::add_brand_ctr - ' . $err);
        return ['success' => false, 'message' => $err];
    }

    // payload: ['brand_id','brand_name','updated_by']
    public function update_brand_ctr(array $payload)
    {
        if (empty($payload['brand_id']) || empty($payload['brand_name'])) {
            return ['success' => false, 'message' => 'Missing brand id or name'];
        }

        if (!method_exists($this->model, 'update')) {
            return ['success' => false, 'message' => 'Model method update() not implemented'];
        }
        $ok = $this->model->update($payload['brand_id'], $payload['brand_name'], $payload['updated_by'] ?? null);
        if ($ok) return ['success' => true, 'message' => 'Brand updated successfully'];
        return ['success' => false, 'message' => 'Failed to update brand'];
    }

    // delete: brand_id, user check optional
    public function delete_brand_ctr(int $brand_id, $user_id = null)
    {
        if ($brand_id <= 0) return ['success' => false, 'message' => 'Invalid brand id'];

        if (!method_exists($this->model, 'delete')) {
            return ['success' => false, 'message' => 'Model method delete() not implemented'];
        }
        $ok = $this->model->delete($brand_id, $user_id);
        if ($ok) return ['success' => true, 'message' => 'Brand deleted'];
        return ['success' => false, 'message' => 'Failed to delete brand'];
    }

    // fetch brands for a user (or all if null)
    public function fetch_brands_ctr($user_id = null)
    {
        if (!method_exists($this->model, 'get_all_by_user')) {
            // fallback to generic get_all
            if (method_exists($this->model, 'get_all')) {
                $rows = $this->model->get_all();
            } else {
                throw new Exception('No fetch method available on brand model.');
            }
        } else {
            $rows = $this->model->get_all_by_user($user_id);
        }

        // group by category (assumes each row has cat_id and cat_name)
        $grouped = [];
        foreach ($rows as $r) {
            $cat = $r['cat_id'] ?? 0;
            $cat_name = $r['cat_name'] ?? 'Uncategorized';
            if (!isset($grouped[$cat])) $grouped[$cat] = ['cat_id' => $cat, 'cat_name' => $cat_name, 'brands' => []];
            $grouped[$cat]['brands'][] = $r;
        }
        // return grouped array values
        return array_values($grouped);
    }
}
