$(document).ready(function() {
    // Verify jQuery and dependencies are loaded
    if (typeof $ === 'undefined') {
        console.error('jQuery is not loaded!');
        alert('Error: jQuery library is not loaded. Please refresh the page.');
        return;
    }

    if (typeof Swal === 'undefined') {
        console.error('SweetAlert2 is not loaded!');
        alert('Error: SweetAlert2 library is not loaded. Please refresh the page.');
        return;
    }

    console.log('Login form script loaded');

    // Regex patterns for validation
    const patterns = {
        email: /^[^\s@]+@[^\s@]+\.[^\s@]+$/, // Standard email pattern
        password: /^.{8,}$/ // At least 8 characters
    };

    // Validation messages
    const validationMessages = {
        email: 'Please enter a valid email address',
        password: 'Password must be at least 8 characters'
    };

    // helper: get query param (e.g., ?next=...)
    function getQueryParam(name) {
        name = name.replace(/[[]/, '\\[').replace(/[]]/, '\\]');
        const regex = new RegExp('[\\?&]' + name + '=([^&#]*)');
        const results = regex.exec(window.location.search);
        return results === null ? null : decodeURIComponent(results[1].replace(/\+/g, ' '));
    }

    // Determine post-login redirect target (priority: next param -> server-provided -> default index)
    function getRedirectTarget(responseData) {
        // 1) check URL query 'next'
        const nextFromUrl = getQueryParam('next');
        if (nextFromUrl) return nextFromUrl;

        // 2) if server provided a next field in JSON, use it (some actions return this)
        if (responseData && typeof responseData.next === 'string' && responseData.next.trim() !== '') {
            return responseData.next;
        }

        // 3) fallback to index
        return '../index.php';
    }

    // Validate individual field
    function validateField(fieldName, value) {
        const pattern = patterns[fieldName];
        if (!pattern) return true;
        return pattern.test(value.trim());
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

    // Validate form
    function validateForm() {
        let isValid = true;

        // Validate email
        const email = $('#customer_email').val().trim();
        if (!email || !validateField('email', email)) {
            showFieldError('customer_email', validationMessages.email);
            isValid = false;
        } else {
            clearFieldError('customer_email');
        }

        // Validate password
        const password = $('#customer_pass').val();
        if (!password || !validateField('password', password)) {
            showFieldError('customer_pass', validationMessages.password);
            isValid = false;
        } else {
            clearFieldError('customer_pass');
        }

        return isValid;
    }

    // Real-time validation on field blur
    $('#customer_email, #customer_pass').on('blur', function() {
        const fieldId = $(this).attr('id');
        const fieldName = fieldId.replace('customer_', '').replace('_', '');
        const value = $(this).val();

        if (value && !validateField(fieldName, value)) {
            showFieldError(fieldId, validationMessages[fieldName]);
        } else {
            clearFieldError(fieldId);
        }
    });

    // Clear errors on input
    $('#customer_email, #customer_pass').on('input', function() {
        clearFieldError($(this).attr('id'));
    });

    // Form submission handler
    $('#login-form').on('submit', function(e) {
        e.preventDefault();
        e.stopPropagation();

        console.log('Login form submitted');

        // Validate form
        if (!validateForm()) {
            console.log('Form validation failed');
            Swal.fire({
                icon: 'error',
                title: 'Validation Error',
                text: 'Please correct the errors in the form before submitting.',
            });
            return;
        }

        // Get form values
        const formData = {
            customer_email: $('#customer_email').val().trim(),
            customer_pass: $('#customer_pass').val()
        };

        console.log('Sending login data');

        // Show loading state
        const submitBtn = $('#login-submit-btn');
        const originalText = submitBtn.html();
        submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Logging in...');

        // Asynchronously invoke login_customer_action
        $.ajax({
            url: '../actions/login_customer_action.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                // Restore button state
                submitBtn.prop('disabled', false).html(originalText);

                // Handle response - check if response is already an object or needs parsing
                let responseData = response;
                if (typeof response === 'string') {
                    try {
                        responseData = JSON.parse(response);
                    } catch (e) {
                        console.error('Error parsing response:', e);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Invalid response from server. Please try again.',
                        });
                        return;
                    }
                }

                if (responseData.success) {
                    // Decide redirect target
                    const target = getRedirectTarget(responseData);

                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: responseData.message || 'Login successful! Redirecting...',
                        timer: 1200,
                        showConfirmButton: false
                    }).then(() => {
                        // If target is a relative URL without path, keep as-is; otherwise decode
                        window.location.href = target;
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Login Failed',
                        text: responseData.message || 'Invalid email or password. Please try again.',
                    });
                }
            },
            error: function(xhr, status, error) {
                // Restore button state
                submitBtn.prop('disabled', false).html(originalText);

                console.error('AJAX Error:', status, error);
                console.error('Response:', xhr.responseText);

                let errorMessage = 'An error occurred! Please try again later.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                } else if (xhr.responseText) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        errorMessage = response.message || errorMessage;
                    } catch (e) {
                        if (xhr.responseText.includes('Fatal error') || xhr.responseText.includes('Parse error')) {
                            errorMessage = 'Server error occurred. Please check the console for details.';
                        }
                    }
                }

                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: errorMessage,
                });
            }
        });
    });
});
