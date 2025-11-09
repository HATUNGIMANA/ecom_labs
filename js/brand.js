/* js/brand.js */
$(function() {
    if (typeof $ === 'undefined') return alert('jQuery required');
    if (typeof Swal === 'undefined') return alert('SweetAlert2 required');

    // Build absolute path to actions directory
    const pathParts = window.location.pathname.split('/').filter(p => p);
    const adminIndex = pathParts.indexOf('admin');
    let api;
    
    if (adminIndex >= 0) {
        // Build absolute path: /project_root/actions/brand_action.php
        const basePath = '/' + pathParts.slice(0, adminIndex).join('/');
        api = basePath + '/actions/brand_action.php';
    } else {
        // Fallback to relative path
        api = '../actions/brand_action.php';
    }
    
    console.log('brand.js: using path:', api);
    console.log('Current location:', window.location.pathname);

    function onFail(xhr, st, err) {
        console.error('AJAX fail', st, err, xhr.status, xhr.responseText);
        let msg = 'Failed to contact server.';
        if (xhr.status === 404) {
            msg = 'Action file not found. Check if brand_action.php exists in the actions directory.';
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

    // fetch
    function fetchBrands() {
        $.ajax({
            url: api,
            method: 'POST',
            data: { action: 'fetch' },
            dataType: 'json',
            success: function(res) {
                console.log('Brand fetch response:', res);
                if (!res || !res.success) {
                    $('#brands-container').html('<div class="alert alert-warning">Could not load brands: ' + (res && res.message ? res.message : 'server error') + '</div>');
                    return;
                }
                render(res.data || []);
            },
            error: onFail
        });
    }

    function render(grouped) {
        const c = $('#brands-container').empty();
        if (!grouped.length) { c.append('<div class="alert alert-info">No brands yet.</div>'); return; }
        grouped.forEach(cat => {
            const card = $(`<div class="card mb-3"><div class="card-header"><strong>${escapeHtml(cat.cat_name||'Uncategorized')}</strong></div></div>`);
            const body = $('<div class="card-body"></div>');
            const list = $('<ul class="list-group list-group-flush"></ul>');
            (cat.brands||[]).forEach(b => {
                const li = $(`<li class="list-group-item d-flex justify-content-between align-items-center">
                    <span>${escapeHtml(b.brand_name)}</span>
                    <div>
                      <button class="btn btn-sm btn-outline-primary edit-brand" data-id="${b.brand_id}" data-name="${escapeHtml(b.brand_name)}">Edit</button>
                      <button class="btn btn-sm btn-outline-danger delete-brand" data-id="${b.brand_id}">Delete</button>
                    </div>
                </li>`);
                list.append(li);
            });
            body.append(list); card.append(body); c.append(card);
        });
    }

    $('#brand-add-form').on('submit', function(e) {
        e.preventDefault();
        const name = $('#brand_name').val().trim(); const cat = $('#brand_cat').val();
        if (!name || !cat) return Swal.fire('Validation', 'Brand name and category required', 'warning');
        $.post(api, { action: 'add', brand_name: name, cat_id: cat }, function(res) {
            if (res.success) { Swal.fire('Success', res.message, 'success'); $('#brand_name').val(''); fetchBrands(); }
            else Swal.fire('Error', res.message || 'Failed', 'error');
        }, 'json').fail(onFail);
    });

    $(document).on('click', '.edit-brand', function() {
        const id = $(this).data('id'), name = $(this).data('name') || $(this).closest('li').find('span').text();
        Swal.fire({ title:'Edit brand', input:'text', inputValue: name, showCancelButton:true, preConfirm: n => { if (!n||!n.trim()) return Swal.showValidationMessage('Name required'); return n.trim(); } })
            .then(r => { if (!r.isConfirmed) return; $.post(api, { action: 'update', brand_id: id, brand_name: r.value }, function(res){ if (res.success) { Swal.fire('Updated', res.message,'success'); fetchBrands(); } else Swal.fire('Error', res.message||'Failed','error'); }, 'json').fail(onFail); });
    });

    $(document).on('click', '.delete-brand', function() {
        const id = $(this).data('id');
        Swal.fire({ title:'Delete brand?', icon:'warning', showCancelButton:true }).then(r => {
            if (!r.isConfirmed) return;
            $.post(api, { action:'delete', brand_id:id }, function(res){ if (res.success) { Swal.fire('Deleted', res.message,'success'); fetchBrands(); } else Swal.fire('Error', res.message||'Failed','error'); }, 'json').fail(onFail);
        });
    });

    function escapeHtml(s){ return String(s||'').replace(/[&<>"'`=\/]/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;','/':'&#x2F;','`':'&#x60;'}[c])); }

    fetchBrands();
});
