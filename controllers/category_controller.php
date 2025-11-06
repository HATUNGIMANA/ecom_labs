<?php

require_once '../classes/category_class.php';

/**
 * Category Controller
 * Creates an instance of the category class and runs the methods
 */
class CategoryController
{
    /**
     * Add a new category
     * @param array $kwargs Category data (cat_name, customer_id)
     * @return array Returns array with 'success' and 'message' keys
     */
    public function add_category_ctr($kwargs)
    {
        $category = new category_class();

        // Check if category name already exists
        if ($category->check_category_name_exists($kwargs['cat_name'])) {
            return [
                'success' => false,
                'message' => 'Category name already exists. Please use a different name.'
            ];
        }

        // Add category
        $category_id = $category->add_category($kwargs);

        if ($category_id) {
            return [
                'success' => true,
                'message' => 'Category added successfully.',
                'category_id' => $category_id
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Failed to add category. Please try again.'
            ];
        }
    }

    /**
     * Update category
     * @param int $cat_id Category ID
     * @param array $kwargs Category data to update (cat_name)
     * @return array Returns array with 'success' and 'message' keys
     */
    public function update_category_ctr($cat_id, $kwargs)
    {
        $category = new category_class();

        // Check if category exists
        $existing_category = $category->get_category($cat_id);
        if (!$existing_category) {
            return [
                'success' => false,
                'message' => 'Category not found.'
            ];
        }

        // Check if new name already exists (excluding current category)
        if ($category->check_category_name_exists($kwargs['cat_name'], $cat_id)) {
            return [
                'success' => false,
                'message' => 'Category name already exists. Please use a different name.'
            ];
        }

        // Update category
        $result = $category->update_category($cat_id, $kwargs);

        if ($result) {
            return [
                'success' => true,
                'message' => 'Category updated successfully.'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Failed to update category. Please try again.'
            ];
        }
    }

    /**
     * Delete category
     * @param int $cat_id Category ID
     * @return array Returns array with 'success' and 'message' keys
     */
    public function delete_category_ctr($cat_id)
    {
        $category = new category_class();

        // Check if category exists
        $existing_category = $category->get_category($cat_id);
        if (!$existing_category) {
            return [
                'success' => false,
                'message' => 'Category not found.'
            ];
        }

        // Delete category
        $result = $category->delete_category($cat_id);

        if ($result) {
            return [
                'success' => true,
                'message' => 'Category deleted successfully.'
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Failed to delete category. Please try again.'
            ];
        }
    }

    /**
     * Get all categories for a user
     * @param int $customer_id Customer ID
     * @return array Returns array with 'success', 'message', and 'categories' keys
     */
    public function get_categories_ctr($customer_id)
    {
        $category = new category_class();
        $categories = $category->get_categories_by_user($customer_id);

        if ($categories !== false) {
            return [
                'success' => true,
                'message' => 'Categories retrieved successfully.',
                'categories' => $categories
            ];
        } else {
            return [
                'success' => false,
                'message' => 'Failed to retrieve categories.',
                'categories' => []
            ];
        }
    }

    /**
     * Get a single category
     * @param int $cat_id Category ID
     * @return array|false Category data or false
     */
    public function get_category_ctr($cat_id)
    {
        $category = new category_class();
        return $category->get_category($cat_id);
    }
}

