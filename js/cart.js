// js/cart.js
// Requires jQuery

function renderMiniCart(data, targetSelector) {
  var target = $(targetSelector);
  if (!target.length) return;
  if (!data || !data.items || data.items.length === 0) {
    target.html('<div class="p-3">Your cart is empty.</div>');
    $('#cart-count').text(0);
    return;
  }
  $('#cart-count').text(data.count);
  var html = '<div class="list-group list-group-flush">';
  data.items.forEach(function(it){
    var title = it.product_title || ('Product #' + it.product_id);
    var price = (parseFloat(it.unit_price) || 0).toFixed(2);
    var subtotal = ((parseFloat(it.unit_price)||0) * (parseInt(it.quantity)||0)).toFixed(2);
    html += '<div class="list-group-item">'
         + '<div class="d-flex justify-content-between"><div>' + title + '<br/><small>GHS' + price + ' x ' + it.quantity + '</small></div>'
         + '<div class="text-end">GHS' + subtotal + '<br/>'
         + '<button class="btn btn-sm btn-link text-danger p-0 mt-2" onclick="removeFromCart(' + it.cart_id + ')">Remove</button>'
         + '</div></div></div>';
  });
  html += '</div><div class="p-3 border-top"><strong>Total: GHS' + (parseFloat(data.total)||0).toFixed(2) + '</strong><div class="mt-2 d-flex gap-2">'
       + '<a href="cart.php" class="btn btn-sm btn-primary">View Cart</a>'
       + '<button class="btn btn-sm btn-outline-secondary" onclick="emptyCart()">Empty Cart</button>'
       + '</div></div>';
  target.html(html);
}

function fetchCart() {
  return $.ajax({
    url: 'actions/get_cart_action.php',
    method: 'GET',
    dataType: 'json'
  }).done(function(resp){
    if (resp && resp.status === 'success') {
      renderMiniCart(resp, '#mini-cart');
    }
  }).fail(function(){
    console.warn('Failed to fetch cart');
  });
}

function addToCart(productId, qty) {
  qty = qty || 1;
  return $.ajax({
    url: 'actions/add_to_cart_action.php',
    method: 'POST',
    data: { product_id: productId, qty: qty },
    dataType: 'json'
  }).done(function(resp){
    if (resp && resp.success) {
      fetchCart();
      alert(resp.message || 'Added to cart');
    } else {
      alert(resp.message || 'Failed to add to cart');
    }
  }).fail(function(jqXHR, textStatus, errorThrown){
    var msg = 'Network error adding to cart';
    try {
      if (jqXHR && jqXHR.responseJSON && jqXHR.responseJSON.message) msg = jqXHR.responseJSON.message;
      else if (jqXHR && jqXHR.responseText) {
        try { var r = JSON.parse(jqXHR.responseText); if (r && (r.message||r.error)) msg = r.message || r.error; } catch(e) {}
      }
      if (textStatus) msg += ' (' + textStatus + ')';
    } catch(e) {}
    alert(msg);
    console.error('AddToCart fail:', textStatus, errorThrown, jqXHR && jqXHR.responseText);
  });
}

function removeFromCart(cartId, btnElem) {
  if (!confirm('Remove this item from your cart?')) return;
  $.ajax({
    url: 'actions/remove_from_cart_action.php',
    method: 'POST',
    data: { cart_id: cartId },
    dataType: 'json'
  }).done(function(resp){
    if (resp && resp.success) {
      var p = window.location.pathname.toLowerCase();
      // If on cart page, remove the row and recalc totals client-side
      if (p.indexOf('/cart.php') !== -1) {
        try {
          var $row = null;
          if (btnElem) {
            $row = $(btnElem).closest('tr');
          } else {
            // try to find the button by cartId inside the table
            $row = $('#cart-table button').filter(function(){
              var on = $(this).attr('onclick') || '';
              return on.indexOf('removeFromCart(' + cartId) !== -1;
            }).first().closest('tr');
          }
          if ($row && $row.length) {
            $row.remove();
            // recalc total
            var total = 0;
            $('#cart-table tbody tr').each(function(){
              var subText = $(this).find('td').eq(3).text();
              var num = parseFloat(subText.replace(/[^0-9.-]+/g, '')) || 0;
              total += num;
            });
            $('#cart-total').text('Total: GHS' + total.toFixed(2));
            if ($('#cart-table tbody tr').length === 0) {
              $('#cart-container').html('<div class="alert alert-info">Your cart is empty. <a href="index.php">Continue shopping</a></div>');
            }
          } else {
            // fallback: reload
            window.location.reload();
          }
        } catch (e) {
          window.location.reload();
        }
      } else if (p.indexOf('/checkout.php') !== -1) {
        // on checkout page, refresh to keep layout correct
        window.location.reload();
      } else {
        // not on cart page — update mini cart
        fetchCart();
      }
    } else {
      alert(resp && resp.message ? resp.message : 'Failed to remove item');
    }
  }).fail(function(){ alert('Network error'); });
}

function updateQuantity(cartId, qty, inputElem) {
  qty = Math.max(1, parseInt(qty)||1);
  $.ajax({
    url: 'actions/update_quantity_action.php',
    method: 'POST',
    data: { cart_id: cartId, qty: qty },
    dataType: 'json'
  }).done(function(resp){
    if (resp && resp.success) {
      var p = window.location.pathname.toLowerCase();
      if (p.indexOf('/cart.php') !== -1) {
        try {
          var $row = null;
          if (inputElem) $row = $(inputElem).closest('tr');
          if ($row && $row.length) {
            var priceText = $row.find('td').eq(1).text();
            var price = parseFloat(priceText.replace(/[^0-9.-]+/g, '')) || 0;
            var subtotal = price * qty;
            $row.find('td').eq(3).text('GHS' + subtotal.toFixed(2));
            // recalc total
            var total = 0;
            $('#cart-table tbody tr').each(function(){
              var subText = $(this).find('td').eq(3).text();
              var num = parseFloat(subText.replace(/[^0-9.-]+/g, '')) || 0;
              total += num;
            });
            $('#cart-total').text('Total: GHS' + total.toFixed(2));
            // sync mini-cart
            fetchCart();
          } else {
            window.location.reload();
          }
        } catch (e) {
          window.location.reload();
        }
      } else if (p.indexOf('/checkout.php') !== -1) {
        window.location.reload();
      } else {
        fetchCart();
      }
    } else {
      alert(resp.message || 'Failed to update quantity');
    }
  }).fail(function(){ alert('Network error'); });
}

function emptyCart() {
  if (!confirm('Empty your cart?')) return;
  $.ajax({ url: 'actions/empty_cart_action.php', method: 'POST', dataType: 'json' })
    .done(function(resp){
      if (resp && resp.success) {
        fetchCart();
        // if user is on cart or checkout page, refresh to reflect emptiness
        var p = window.location.pathname.toLowerCase();
        if (p.indexOf('/cart.php') !== -1 || p.indexOf('/checkout.php') !== -1) {
          window.location.reload();
        }
      } else {
        alert(resp.message || 'Failed');
      }
    })
    .fail(function(){ alert('Network error'); });
}

// initialize on pages that include this script
$(function(){
  fetchCart();
});
