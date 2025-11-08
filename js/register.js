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

    console.log('Registration form script loaded');

    // Reserved admin email (must match server-side reserved email)
    const RESERVED_ADMIN_EMAIL = 'admin.afrobitesk@gmail.com';

    // Regex patterns for validation (matching database schema)
    const patterns = {
        name: /^[a-zA-Z\s]{2,100}$/, // 2-100 characters, letters and spaces only
        email: /^[^\s@]+@[^\s@]+\.[^\s@]+$/, // Standard email pattern (max 50 chars per DB)
        password: /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[a-zA-Z\d@$!%*?&]{8,}$/, // At least 8 chars, 1 lowercase, 1 uppercase, 1 digit
        city: /^[a-zA-Z\s]{2,30}$/, // 2-30 characters, letters and spaces (per DB schema)
        contact: v => /^\+?\d{7,15}$/.test(v) // optional leading +, then 7-15 digits
    };

    // Validation messages
    const validationMessages = {
        name: 'Full name must be 2-100 characters and contain only letters and spaces',
        email: 'Please enter a valid email address (max 50 characters)',
        password: 'Password must be at least 8 characters long and contain at least one lowercase letter, one uppercase letter, and one number',
        city: 'City must be 2-30 characters and contain only letters and spaces',
        contact: 'Contact number must be 7-15 digits (optional leading +)'
    };

    // Validate individual field
    function validateField(fieldName, value) {
        const pattern = patterns[fieldName];
        if (!pattern) return true; // No pattern means field is valid
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

    // Validate all fields
    function validateForm() {
        let isValid = true;

        // Validate full name
        const name = $('#customer_name').val().trim();
        if (!name || !validateField('name', name)) {
            showFieldError('customer_name', validationMessages.name);
            isValid = false;
        } else {
            clearFieldError('customer_name');
        }

        // Validate email
        const email = $('#customer_email').val().trim();
        if (!email || !validateField('email', email)) {
            showFieldError('customer_email', validationMessages.email);
            isValid = false;
        } else if (email.length > 50) {
            showFieldError('customer_email', 'Email must be 50 characters or less');
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

        // Validate password confirmation
        const passwordConfirm = $('#customer_pass_confirm').val();
        if (!passwordConfirm) {
            showFieldError('customer_pass_confirm', 'Please confirm your password');
            isValid = false;
        } else if (password !== passwordConfirm) {
            showFieldError('customer_pass_confirm', 'Passwords do not match');
            isValid = false;
        } else {
            clearFieldError('customer_pass_confirm');
        }

        // Validate country (dropdown selection)
        const country = $('#customer_country').val();
        if (!country || country === '') {
            showFieldError('customer_country', 'Please select your country');
            isValid = false;
        } else if (country.length > 30) {
            showFieldError('customer_country', 'Country name too long (max 30 characters)');
            isValid = false;
        } else {
            clearFieldError('customer_country');
        }

        // Validate city
        const city = $('#customer_city').val().trim();
        if (!city || !validateField('city', city)) {
            showFieldError('customer_city', validationMessages.city);
            isValid = false;
        } else {
            clearFieldError('customer_city');
        }

        // Validate contact number
        const contact = $('#customer_contact').val().trim();
        if (!contact || !validateField('contact', contact)) {
            showFieldError('customer_contact', validationMessages.contact);
            isValid = false;
        } else {
            clearFieldError('customer_contact');
        }

        return isValid;
    }

    // Real-time validation on field blur
    $('#customer_name, #customer_email, #customer_pass, #customer_pass_confirm, #customer_country, #customer_city, #customer_contact').on('blur', function() {
        const fieldId = $(this).attr('id');
        const fieldName = fieldId.replace('customer_', '').replace('pass_confirm', 'pass').replace('_', '');
        const value = $(this).val();

        // Special handling for password confirmation
        if (fieldId === 'customer_pass_confirm') {
            const password = $('#customer_pass').val();
            if (!value) {
                showFieldError(fieldId, 'Please confirm your password');
            } else if (password !== value) {
                showFieldError(fieldId, 'Passwords do not match');
            } else {
                clearFieldError(fieldId);
            }
        } else if (fieldId === 'customer_country') {
            // Dropdown validation
            if (!value || value === '') {
                showFieldError(fieldId, 'Please select your country');
            } else {
                clearFieldError(fieldId);
            }
        } else if (value && !validateField(fieldName, value)) {
            showFieldError(fieldId, validationMessages[fieldName]);
        } else {
            clearFieldError(fieldId);
        }
    });

    // Real-time password matching check
    $('#customer_pass_confirm').on('input', function() {
        const password = $('#customer_pass').val();
        const passwordConfirm = $(this).val();
        if (passwordConfirm && password !== passwordConfirm) {
            showFieldError('customer_pass_confirm', 'Passwords do not match');
        } else if (passwordConfirm && password === passwordConfirm) {
            clearFieldError('customer_pass_confirm');
        }
    });

    // Clear errors on input
    $('#customer_name, #customer_email, #customer_pass, #customer_pass_confirm, #customer_country, #customer_city, #customer_contact').on('input change', function() {
        const fieldId = $(this).attr('id');
        if (fieldId !== 'customer_pass_confirm' && fieldId !== 'customer_country') {
            clearFieldError(fieldId);
        }
    });

    // Form submission handler
    $('#register-form').on('submit', function(e) {
        e.preventDefault();
        e.stopPropagation();

        console.log('Form submitted');

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
            customer_name: $('#customer_name').val().trim(),
            customer_email: $('#customer_email').val().trim(),
            customer_pass: $('#customer_pass').val(),
            customer_country: $('#customer_country').val(),
            customer_city: $('#customer_city').val().trim(),
            customer_contact: $('#customer_contact').val().trim(),
            user_role: $('input[name="user_role"]:checked').val() || 2 // Get selected role (2 = customer, 1 = restaurant owner)
        };

        // Client-side reserved-admin check (fast feedback)
        if (formData.customer_email && formData.customer_email.toLowerCase() === RESERVED_ADMIN_EMAIL.toLowerCase()) {
            Swal.fire({
                icon: 'error',
                title: 'Registration Not Allowed',
                text: 'The email address you entered is reserved for the site administrator and cannot be registered.',
            });
            return;
        }

        // Show loading state
        const submitBtn = $('#register-submit-btn');
        const originalText = submitBtn.html();
        submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Registering...');

        console.log('Sending form data:', formData);

        // Asynchronously invoke register_customer_action
        $.ajax({
            url: '../actions/register_customer_action.php',
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
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: responseData.message || 'Registration successful! Please login to continue.',
                        confirmButtonText: 'Go to Login'
                    }).then((result) => {
                        if (result.isConfirmed || result.isDismissed) {
                            // Redirect to login page
                            window.location.href = 'login.php';
                        }
                    });
                } else {
                    // Specific handling for reserved-admin message (server-side)
                    const serverMessage = responseData.message || '';
                    if (serverMessage.toLowerCase().includes('reserved') && serverMessage.toLowerCase().includes('admin')) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Registration Not Allowed',
                            text: serverMessage,
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Registration Failed',
                            text: serverMessage || 'An error occurred during registration. Please try again.',
                        });
                    }
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
                        // If response is not JSON, check for PHP errors
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
