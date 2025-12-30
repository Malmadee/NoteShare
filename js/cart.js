// Cart system using database backend
(function(){
  const API_GET_CART = '/NoteShare/api/get-cart.php';
  const API_REMOVE = '/NoteShare/api/remove-from-cart.php';
  const API_PURCHASE = '/NoteShare/api/add-purchase.php';
  
  let cartChannel = null;
  
  // Toast notification helper
  function showToast(message, type = 'info') {
    let toastContainer = document.getElementById('toastContainer');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.id = 'toastContainer';
        toastContainer.style.cssText = `
            position: fixed;
            bottom: 20px;
            left: 20px;
            z-index: 9999;
            max-width: 400px;
        `;
        document.body.appendChild(toastContainer);
    }
    
    const toast = document.createElement('div');
    toast.style.cssText = `
        background-color: #333;
        color: white;
        padding: 16px 20px;
        border-radius: 4px;
        margin-bottom: 10px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.3);
        animation: slideIn 0.3s ease-in;
        font-size: 14px;
        line-height: 1.4;
        word-wrap: break-word;
    `;
    
    if (type === 'success') {
        toast.style.backgroundColor = '#555';
    } else if (type === 'error') {
        toast.style.backgroundColor = '#f44336';
    }
    
    toast.textContent = message;
    toastContainer.appendChild(toast);
    
    setTimeout(() => {
        toast.style.animation = 'slideOut 0.3s ease-out forwards';
        setTimeout(() => toast.remove(), 300);
    }, 4000);
  }
  
  // Initialize BroadcastChannel for real-time updates across tabs/windows
  if (typeof BroadcastChannel !== 'undefined') {
    try {
      cartChannel = new BroadcastChannel('cart_updates');
      cartChannel.onmessage = function(event) {
        console.log('[Cart] Received broadcast message:', event.data);
        if (event.data.action === 'item_added' || event.data.action === 'item_removed') {
          // Refresh cart display
          loadAndRenderCart();
        }
      };
    } catch (e) {
      console.log('[Cart] BroadcastChannel not available:', e.message);
    }
  }
  
  async function loadAndRenderCart() {
    try {
      const response = await fetch(API_GET_CART);
      const data = await response.json();
      
      if (!data.success) {
        console.log('[Cart] Not logged in or error loading cart');
        return;
      }
      
      renderCart(data.items, data.total);
    } catch (error) {
      console.error('[Cart] Error loading cart:', error);
    }
  }
  
  function formatPrice(p) { 
    return Math.round(p || 0); 
  }
  
  function removeFromCart(materialId) {
    const formData = new FormData();
    formData.append('material_id', materialId);
    
    fetch(API_REMOVE, {
      method: 'POST',
      body: formData
    })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        console.log('[Cart] Item removed. New count:', data.cart_count);
        showToast('Item removed from cart.', 'success');
        // Broadcast to other tabs
        if (cartChannel) {
          cartChannel.postMessage({
            action: 'item_removed',
            material_id: materialId,
            cart_count: data.cart_count
          });
        }
        loadAndRenderCart();
      } else {
        showToast('Error removing item: ' + (data.message || 'Unknown error'), 'error');
      }
    })
    .catch(err => {
      console.error('[Cart] Error removing from cart:', err);
      showToast('Error removing item', 'error');
    });
  }
  
  function renderCart(items, total) {
    const offBody = document.querySelector('#offcanvasCart .offcanvas-body');
    if (!offBody) return;
    
    const list = document.createElement('ul');
    list.className = 'list-group mb-3';
    
    if (!items || items.length === 0) {
      const emptyLi = document.createElement('li');
      emptyLi.className = 'list-group-item text-center text-muted';
      emptyLi.textContent = 'Your cart is empty';
      list.appendChild(emptyLi);
    } else {
      items.forEach(function(item) {
        const li = document.createElement('li');
        li.className = 'list-group-item d-flex justify-content-between align-items-start';
        
        const left = document.createElement('div');
        const h = document.createElement('h6');
        h.className = 'mb-0';
        h.textContent = item.title;
        left.appendChild(h);
        
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'remove-pill';
        btn.textContent = 'Remove';
        btn.addEventListener('click', function() {
          removeFromCart(item.id);
        });
        left.appendChild(btn);
        
        const span = document.createElement('span');
        span.className = 'price-pill rounded-pill';
        span.innerHTML = '<img src="assets/images/coin.png" alt="" class="coin">' + formatPrice(item.price);
        
        li.appendChild(left);
        li.appendChild(span);
        list.appendChild(li);
      });
    }
    
    // Total
    const totalLi = document.createElement('li');
    totalLi.className = 'list-group-item d-flex justify-content-between align-items-center';
    totalLi.innerHTML = '<strong>Total:</strong><span class="price-pill price-pill--clean rounded-pill"><img src="assets/images/coin.png" alt="" class="coin">' + formatPrice(total || 0) + '</span>';
    list.appendChild(totalLi);
    
    // Checkout button
    const checkout = document.createElement('button');
    checkout.className = 'w-100 btn btn-primary btn-lg';
    checkout.type = 'button';
    checkout.textContent = 'Check Out';
    checkout.disabled = !items || items.length === 0;
    
    checkout.addEventListener('click', function() {
      if (!items || items.length === 0) {
        showToast('Cart is empty', 'error');
        return;
      }
      
      // Disable button during checkout
      checkout.disabled = true;
      
      const itemsPayload = items.map(i => ({
        id: i.id,
        price: i.price,
        qty: 1
      }));
      
      // Use new checkout-with-wallet endpoint
      fetch('/NoteShare/api/checkout-with-wallet.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          items: itemsPayload,
          total: total
        })
      })
      .then(r => r.json())
      .then(resp => {
        if (resp && resp.success) {
          showToast('Purchase has been made.', 'success');
          // Refresh wallet balance and cart
          setTimeout(() => {
            loadAndRenderCart();
            // Trigger wallet update on all pages
            if (cartChannel) {
              cartChannel.postMessage({
                action: 'purchase_made',
                new_balance: resp.new_balance
              });
            }
          }, 500);
        } else {
          showToast(resp && resp.message ? resp.message : 'Checkout failed', 'error');
          checkout.disabled = false;
        }
      })
      .catch(err => {
        console.error('[Cart] Checkout error:', err);
        showToast('Checkout failed. See console for details.', 'error');
        checkout.disabled = false;
      });
    });
    
    offBody.innerHTML = '';
    offBody.appendChild(list);
    offBody.appendChild(checkout);
  }
  
  // Initialize on page load
  document.addEventListener('DOMContentLoaded', function() {
    loadAndRenderCart();
    
    // Also refresh cart when offcanvas is shown
    const offcanvasEl = document.getElementById('offcanvasCart');
    if (offcanvasEl) {
      offcanvasEl.addEventListener('show.bs.offcanvas', function() {
        console.log('[Cart] Offcanvas opening, refreshing cart');
        loadAndRenderCart();
      });
    }
  });
})();
