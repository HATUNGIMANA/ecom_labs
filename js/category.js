$(document).ready(function() {
    // Verify dependencies
    if (typeof $ === 'undefined') {
        console.error('jQuery is not loaded!');
        return;
    }

    if (typeof Swal === 'undefined') {
        console.error('SweetAlert2 is not loaded!');
        return;
    }

    console.log('Category management script loaded');

    // Regex pattern for category name validation
    const categoryNamePattern = /^[a-zA-Z0-9\s\-_]+$/;
    const categoryNameMaxLength = 100;

    // Validate category name
    function validateCategoryName(name) {
        if (!name || name.trim().length === 0) {
            return { valid: false, message: 'Category name is required.' };
        }
        if (name.trim().length > categoryNameMaxLength) {
            return { valid: false, message: 'Category name must be 100 characters or less.' };
        }
        if (!categoryNamePattern.test(name)) {
            return { valid: false, message: 'Category name can only contain letters, numbers, spaces, hyphens, and underscores.' };
        }
        return { valid: true, message: '' };
    }

    // Show field error
    function showFieldError(fieldId, message) {
        const field = $('#' + fieldId);
        field.addClass('is-invalid');
        let errorDiv = field.siblings('.invalid-feedback');
        if (errorDiv.length === 0) {
            errorDiv = $('<div class="invalid-feedback"></div>');
            field.after(errorDiv);
        }
        errorDiv.text(message);
    }

    // Clear field error
    function clearFieldError(fieldId) {
        const field = $('#' + fieldId);
        field.removeClass('is-invalid');
        field.siblings('.invalid-feedback').remove();
    }

    // Load categories
    function loadCategories() {
        $('#loading-categories').show();
        $('#categories-list').hide();
        $('#no-categories').hide();

        $.ajax({
            url: '../actions/fetch_category_action.php',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                $('#loading-categories').hide();

                if (response.success && response.categories && response.categories.length > 0) {
                    displayCategories(response.categories);
                    $('#categories-list').show();
                } else {
                    $('#no-categories').show();
                }
            },
            error: function(xhr, status, error) {
                $('#loading-categories').hide();
                console.error('Error loading categories:', error);
                
                let errorMessage = 'Failed to load categories.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }

                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: errorMessage
                });
            }
        });
    }

    // Display categories in table
    function displayCategories(categories) {
        const tbody = $('#categories-table-body');
        tbody.empty();

        categories.forEach(function(category) {
            const row = `
                <tr>
                    <td>${category.cat_id}</td>
                    <td>${escapeHtml(category.cat_name)}</td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary edit-category-btn" data-id="${category.cat_id}" data-name="${escapeHtml(category.cat_name)}">
                            <i class="fas fa-edit me-1"></i>Edit
                        </button>
                        <button class="btn btn-sm btn-outline-danger delete-category-btn" data-id="${category.cat_id}" data-name="${escapeHtml(category.cat_name)}">
                            <i class="fas fa-trash me-1"></i>Delete
                        </button>
                    </td>
                </tr>
            `;
            tbody.append(row);
        });
    }

    // Escape HTML to prevent XSS
    function escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }

    // Add category form submission
    $('#add-category-form').on('submit', function(e) {
        e.preventDefault();

        const catName = $('#cat_name').val().trim();
        const validation = validateCategoryName(catName);

        if (!validation.valid) {
            showFieldError('cat_name', validation.message);
            return;
        }

        clearFieldError('cat_name');

        const submitBtn = $('#add-category-btn');
        const originalText = submitBtn.html();
        submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Adding...');

        $.ajax({
            url: '../actions/add_category_action.php',
            type: 'POST',
            data: { cat_name: catName },
            dataType: 'json',
            success: function(response) {
                submitBtn.prop('disabled', false).html(originalText);

                if (response.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: response.message,
                        timer: 2000,
                        showConfirmButton: false
                    });
                    $('#add-category-form')[0].reset();
                    loadCategories();
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.message || 'Failed to add category.'
                    });
                }
            },
            error: function(xhr, status, error) {
                submitBtn.prop('disabled', false).html(originalText);
                console.error('Error adding category:', error);

                let errorMessage = 'Failed to add category.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }

                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: errorMessage
                });
            }
        });
    });

    // Edit category button click
    $(document).on('click', '.edit-category-btn', function() {
        const catId = $(this).data('id');
        const catName = $(this).data('name');

        $('#edit_cat_id').val(catId);
        $('#edit_cat_name').val(catName);
        clearFieldError('edit_cat_name');

        const modal = new bootstrap.Modal(document.getElementById('editCategoryModal'));
        modal.show();
    });

    // Update category
    $('#update-category-btn').on('click', function() {
        const catId = $('#edit_cat_id').val();
        const catName = $('#edit_cat_name').val().trim();
        const validation = validateCategoryName(catName);

        if (!validation.valid) {
            showFieldError('edit_cat_name', validation.message);
            return;
        }

        clearFieldError('edit_cat_name');

        const submitBtn = $('#update-category-btn');
        const originalText = submitBtn.html();
        submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Updating...');

        $.ajax({
            url: '../actions/update_category_action.php',
            type: 'POST',
            data: { cat_id: catId, cat_name: catName },
            dataType: 'json',
            success: function(response) {
                submitBtn.prop('disabled', false).html(originalText);

                if (response.success) {
                    const modal = bootstrap.Modal.getInstance(document.getElementById('editCategoryModal'));
                    modal.hide();

                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: response.message,
                        timer: 2000,
                        showConfirmButton: false
                    });
                    loadCategories();
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.message || 'Failed to update category.'
                    });
                }
            },
            error: function(xhr, status, error) {
                submitBtn.prop('disabled', false).html(originalText);
                console.error('Error updating category:', error);

                let errorMessage = 'Failed to update category.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }

                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: errorMessage
                });
            }
        });
    });

    // Delete category button click
    $(document).on('click', '.delete-category-btn', function() {
        const catId = $(this).data('id');
        const catName = $(this).data('name');

        Swal.fire({
            title: 'Are you sure?',
            text: `Do you want to delete category "${catName}"?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '../actions/delete_category_action.php',
                    type: 'POST',
                    data: { cat_id: catId },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Deleted!',
                                text: response.message,
                                timer: 2000,
                                showConfirmButton: false
                            });
                            loadCategories();
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: response.message || 'Failed to delete category.'
                            });
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error deleting category:', error);

                        let errorMessage = 'Failed to delete category.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }

                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: errorMessage
                        });
                    }
                });
            }
        });
    });

    // Load categories on page load
    loadCategories();
});

