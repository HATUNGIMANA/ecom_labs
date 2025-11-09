/* js/product_search.js - Dynamic search and filtering for products */

$(document).ready(function() {
    // Auto-submit search on Enter key
    $('#search-query').on('keypress', function(e) {
        if (e.which === 13) { // Enter key
            e.preventDefault();
            $('#search-form').submit();
        }
    });

    // Optional: Live search as user types (debounced)
    let searchTimeout;
    $('#search-query').on('input', function() {
        clearTimeout(searchTimeout);
        const query = $(this).val().trim();
        
        // Only trigger live search if query is at least 3 characters
        if (query.length >= 3) {
            searchTimeout = setTimeout(function() {
                performLiveSearch(query);
            }, 500); // Wait 500ms after user stops typing
        }
    });

    // Function to perform live search via AJAX
    function performLiveSearch(query) {
        const catId = $('#filter-category').val() || '';
        const brandId = $('#filter-brand').val() || '';
        
        // Build URL for search
        let searchUrl = 'product_search_result.php?q=' + encodeURIComponent(query);
        if (catId) searchUrl += '&cat_id=' + catId;
        if (brandId) searchUrl += '&brand_id=' + brandId;
        
        // Optional: Show loading indicator
        // You could show a loading spinner here
        
        // For now, just redirect to search results page
        // In a more advanced implementation, you could load results via AJAX
        // window.location.href = searchUrl;
    }

    // Update brand dropdown based on selected category (if brands are category-specific)
    $('#filter-category').on('change', function() {
        const catId = $(this).val();
        // If your brands are linked to categories, you could filter brands here
        // For now, we'll keep all brands available
    });

    // Clear search on Escape key
    $('#search-query').on('keydown', function(e) {
        if (e.which === 27) { // Escape key
            $(this).val('');
        }
    });

    // Highlight search terms in results (if on search results page)
    if (window.location.pathname.includes('product_search_result.php')) {
        const urlParams = new URLSearchParams(window.location.search);
        const query = urlParams.get('q');
        
        if (query && query.length > 0) {
            // Highlight matching text in product titles
            $('.card-title').each(function() {
                const text = $(this).text();
                const regex = new RegExp('(' + escapeRegExp(query) + ')', 'gi');
                const highlighted = text.replace(regex, '<mark>$1</mark>');
                $(this).html(highlighted);
            });
        }
    }

    // Helper function to escape special regex characters
    function escapeRegExp(string) {
        return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    }

    // Smooth scroll to results when searching
    $('#search-form').on('submit', function(e) {
        const query = $('#search-query').val().trim();
        if (query.length === 0 && $('#filter-category').val() === '' && $('#filter-brand').val() === '') {
            e.preventDefault();
            alert('Please enter a search term or select a filter.');
            return false;
        }
    });
});

