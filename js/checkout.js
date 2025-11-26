// js/checkout.js
// Simple simulated payment flow

function openSimulatePayment(totalAmount) {
  // create a confirm modal via bootstrap with improved UX
  var html = '\n<div class="modal fade" id="simPayModal" tabindex="-1" aria-hidden="true">\n'
           + '<div class="modal-dialog modal-dialog-centered">\n'
           + '<div class="modal-content">\n'
           + '<div class="modal-header">\n<h5 class="modal-title">Simulate Payment</h5>\n<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>\n</div>\n'
           + '<div class="modal-body">\n'
           + '<p>Amount to pay: <strong>GHS' + (parseFloat(totalAmount)||0).toFixed(2) + '</strong></p>\n'
           + '<p>This is a simulated payment. Click <strong>Confirm</strong> to proceed.</p>\n'
           + '<div id="sim-pay-feedback" class="mt-3"></div>\n'
           + '</div>\n'
           + '<div class="modal-footer">\n'
           + '<button type="button" class="btn btn-secondary" data-bs-dismiss="modal" id="sim-cancel">Cancel</button>\n'
           + '<button type="button" class="btn btn-primary" id="confirm-sim-pay">Confirm</button>\n'
           + '</div>\n</div>\n</div>\n</div>\n';
  $('body').append(html);
  var modalEl = document.getElementById('simPayModal');
  var modal = new bootstrap.Modal(modalEl, {});
  modal.show();

  function cleanUpModal() {
    try { modal.hide(); } catch(e){}
    setTimeout(function(){ var el = document.getElementById('simPayModal'); if (el) el.remove(); }, 300);
  }

  $('#confirm-sim-pay').on('click', function(){
    var $btn = $(this);
    $btn.prop('disabled', true).text('Processing...');
    $('#sim-pay-feedback').html('<div class="text-center"><div class="spinner-border spinner-border-sm" role="status"></div> Processing payment...</div>');

    // call checkout action
    $.ajax({ url: 'actions/process_checkout_action.php', method: 'POST', dataType: 'json', timeout: 20000 })
      .done(function(resp){
        if (resp && resp.status === 'success') {
          // refresh mini-cart
          if (window.fetchCart) fetchCart();
          // redirect user to the Paystack payment page so they can complete (simulate) payment
          var paystackUrl = 'https://paystack.shop/pay/y7vkzrjivd';
          // include order reference so we can identify the order after redirect if needed
          if (resp.order_ref) paystackUrl += '?order_ref=' + encodeURIComponent(resp.order_ref);
          // cleanup modal and redirect
          cleanUpModal();
          window.location.href = paystackUrl;
          return;
        } else {
          var msg = (resp && (resp.message || resp.error)) ? (resp.message || resp.error) : 'Checkout failed';
          $('#sim-pay-feedback').html('<div class="alert alert-danger">' + msg + '</div>');
          $btn.prop('disabled', false).text('Confirm');
        }
      }).fail(function(jqXHR, textStatus){
        var msg = 'Network error';
        try { if (jqXHR && jqXHR.responseJSON && jqXHR.responseJSON.message) msg = jqXHR.responseJSON.message; } catch(e){}
        $('#sim-pay-feedback').html('<div class="alert alert-danger">' + msg + '</div>');
        $btn.prop('disabled', false).text('Confirm');
      });
  });
}

// helper to call from checkout page
function startCheckoutFlow(totalAmount) {
  openSimulatePayment(totalAmount);
}
