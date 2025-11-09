/* js/product.js */
$(function() {
    if (typeof $ === 'undefined') return alert('jQuery required');
    if (typeof Swal === 'undefined') return alert('SweetAlert2 required');

    // SIMPLER PATH DETECTION
    const currentPath = window.location.pathname;
    let api;
    if (currentPath.includes('/admin/')) {
        api = '../actions/product_action.php';
    } else {
        api = 'actions/product_action.php';
    }

    console.log('product.js: using path:', api);

    function onFail(xhr, st, err) {
        console.error('AJAX fail', st, err, xhr.status, xhr.responseText);
        let msg = 'Failed to contact server.';
        if (xhr.status === 404) {
            msg = 'Action file not found. Check if product_action.php exists in the actions directory.';
        } else if (xhr.status === 403) {
            msg = 'Access denied. Please ensure you are logged in as admin.';
        } else if (xhr.responseText) {
            try { 
                let j = JSON.parse(xhr.responseText); 
                if (j && j.message) msg = j.message; 
            } catch(e) {
                // If response is not valid JSON, show the raw response
                msg = 'Invalid server response. Status: ' + xhr.status + '. Response: ' + xhr.responseText.substring(0, 100);
            }
        } else {
            msg = 'Server error: ' + xhr.status;
        }
        Swal.fire('Error', msg, 'error');
    }

    function fetchProducts() { 
        $.ajax({
            url: api,
            method: 'POST',
            data: { action: 'fetch' },
            dataType: 'json',
            success: function(res) {
                console.log('Product fetch response:', res);
                if (!res || !res.success) { 
                    $('#products-container').html('<div class="alert alert-warning">Could not load products: ' + (res && res.message ? res.message : 'server error') + '</div>'); 
                    return; 
                } 
                render(res.data||[]); 
            },
            error: onFail
        });
    }

    function render(grouped) {
        const c = $('#products-container').empty();
        if (!grouped.length) { c.append('<div class="alert alert-info">No products yet.</div>'); return; }
        grouped.forEach(cat => {
            const card = $(`<div class="card mb-3"><div class="card-header"><strong>${escapeHtml(cat.cat_name||'Uncategorized')}</strong></div></div>`);
            const body = $('<div class="card-body"></div>');
            (cat.brands||[]).forEach(brand => {
                body.append(`<h6>${escapeHtml(brand.brand_name||'Brand')}</h6>`);
                const table = $('<div class="table-responsive mb-3"><table class="table table-sm"><thead><tr><th>Title</th><th>Price</th><th>Keywords</th><th></th></tr></thead><tbody></tbody></table></div>');
                const tbody = table.find('tbody');
                (brand.products||[]).forEach(p => {
                    const id = p.product_id||p.id;
                    tbody.append(`<tr>
                        <td>${escapeHtml(p.product_title||p.title||'')}</td>
                        <td>${escapeHtml(String(p.product_price||p.price||''))}</td>
                        <td>${escapeHtml(p.product_keywords||p.keywords||'')}</td>
                        <td>
                          <button class="btn btn-sm btn-outline-primary edit-product" data-id="${id}">Edit</button>
                          <button class="btn btn-sm btn-outline-danger delete-product" data-id="${id}">Delete</button>
                        </td>
                    </tr>`);
                });
                body.append(table);
            });
            card.append(body); c.append(card);
        });
    }

    $('#product-form').on('submit', function(e) {
        e.preventDefault();
        const payload = {
            action:'add',
            product_cat: $('#product_cat').val(),
            product_brand: $('#product_brand').val(),
            product_title: $('#product_title').val().trim(),
            product_price: $('#product_price').val().trim(),
            product_desc: $('#product_desc').val().trim(),
            product_keywords: $('#product_keywords').val().trim()
        };
        if (!payload.product_cat||!payload.product_brand||!payload.product_title||!payload.product_price) return Swal.fire('Validation','Please fill required fields','warning');
        if (isNaN(Number(payload.product_price))) return Swal.fire('Validation','Price must be numeric','warning');
        $.post(api, payload, function(res) { if (res.success) { Swal.fire('Saved',res.message,'success'); $('#product-form')[0].reset(); fetchProducts(); } else Swal.fire('Error',res.message||'Failed','error'); }, 'json').fail(onFail);
    });

    $(document).on('click', '.edit-product', function() {
        const id = $(this).data('id');
        Swal.fire({ title:'Edit product', html:'<input id="swal-title" class="swal2-input" placeholder="Title"><input id="swal-price" class="swal2-input" placeholder="Price"><input id="swal-keywords" class="swal2-input" placeholder="Keywords">', showCancelButton:true, preConfirm:()=>({ title:$('#swal-title').val().trim(), price:$('#swal-price').val().trim(), keywords:$('#swal-keywords').val().trim() })})
            .then(result => { if (!result.isConfirmed) return; const v=result.value; if (!v.title||!v.price) return Swal.fire('Validation','Title and price required','warning'); if (isNaN(Number(v.price))) return Swal.fire('Validation','Price must be numeric','warning');
                $.post(api, { action:'update', product_id:id, product_title:v.title, product_price:v.price, product_keywords:v.keywords }, function(res){ if (res.success) { Swal.fire('Updated',res.message,'success'); fetchProducts(); } else Swal.fire('Error',res.message||'Failed','error'); }, 'json').fail(onFail);
            });
    });

    $(document).on('click', '.delete-product', function() {
        const id = $(this).data('id');
        Swal.fire({ title:'Delete product?', icon:'warning', showCancelButton:true }).then(r => { if (!r.isConfirmed) return; $.post(api, { action:'delete', product_id:id }, function(res){ if (res.success) { Swal.fire('Deleted',res.message,'success'); fetchProducts(); } else Swal.fire('Error',res.message||'Failed','error'); }, 'json').fail(onFail); });
    });

    function escapeHtml(s){ return String(s||'').replace(/[&<>"'`=\/]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;','/':'&#x2F;','`':'&#x60;'})[c]||''); }

    fetchProducts();
});
