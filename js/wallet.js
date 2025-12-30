/* ========================================
   WALLET & COIN PURCHASE SYSTEM
   ======================================== */

// Coin prices in Rs (Sri Lankan Rupees, displayed as "Rs.")
const COIN_PACKAGES = {
  50: { coins: 50, price: 100, description: '50 Coins' },
  100: { coins: 100, price: 200, description: '100 Coins' },
  200: { coins: 200, price: 400, description: '200 Coins' },
  500: { coins: 500, price: 1000, description: '500 Coins' }
};

// Current selected package (for quick purchase)
let selectedPackage = null;

// BroadcastChannel for real-time updates across tabs/windows
let walletChannel = null;

// Payment Gateway Base URL (Razorpay or similar)
// Update this with your actual payment gateway
const PAYMENT_GATEWAY_URL = 'https://checkout.razorpay.com/'; // Placeholder

// Initialize wallet modal on page load
document.addEventListener('DOMContentLoaded', function() {
  initializeWalletModal();
  displayCurrentBalance();
  initializeWalletBroadcastChannel();
});

/**
 * Initialize wallet modal system
 */
function initializeWalletModal() {
  const walletIcon = document.querySelector('.wallet-img');
  const walletModal = document.getElementById('walletModal');
  const closeButton = document.querySelector('.wallet-modal-close');
  const overlay = document.getElementById('walletModalOverlay');

  // Attach event listeners
  if (walletIcon) {
    walletIcon.parentElement.addEventListener('click', function(e) {
      e.preventDefault();
      openWalletModal();
    });
  }

  if (closeButton) {
    closeButton.addEventListener('click', closeWalletModal);
  }

  if (overlay) {
    overlay.addEventListener('click', function(e) {
      if (e.target === overlay) {
        closeWalletModal();
      }
    });
  }

  // Initialize package buttons
  initializePackageButtons();

  // Initialize custom amount input
  initializeCustomAmount();

  // Initialize purchase button
  initializePurchaseButton();
}

/**
 * Initialize BroadcastChannel for real-time wallet updates
 */
function initializeWalletBroadcastChannel() {
  if (typeof BroadcastChannel === 'undefined') {
    console.log('[Wallet] BroadcastChannel not available');
    return;
  }
  
  try {
    walletChannel = new BroadcastChannel('cart_updates');
    walletChannel.onmessage = function(event) {
      console.log('[Wallet] Received broadcast message:', event.data);
      
      // Update wallet when purchase is made
      if (event.data.action === 'purchase_made' && event.data.new_balance !== undefined) {
        updateWalletBalance(event.data.new_balance);
      }
    };
    console.log('[Wallet] BroadcastChannel initialized');
  } catch (e) {
    console.log('[Wallet] BroadcastChannel error:', e.message);
  }
}

/**
 * Update wallet balance display immediately
 */
function updateWalletBalance(newBalance) {
  const balanceElement = document.getElementById('walletBalance');
  if (balanceElement) {
    const balance = parseInt(newBalance);
    balanceElement.textContent = balance.toLocaleString();
    console.log('[Wallet] Balance updated to:', balance);
    // Store in localStorage as well
    localStorage.setItem('userCoins', balance);
  }
}

/**
 * Open wallet modal
 */
function openWalletModal() {
  const overlay = document.getElementById('walletModalOverlay');
  if (overlay) {
    overlay.classList.add('active');
    // Refresh balance when opening modal
    displayCurrentBalance();
    // Get scrollbar width to prevent layout shift
    const scrollbarWidth = window.innerWidth - document.documentElement.clientWidth;
    document.body.style.overflow = 'hidden';
    document.body.style.paddingRight = scrollbarWidth + 'px';
      // Also apply to fixed navbar
      const navbar = document.querySelector('.container-fluid.fixed-top');
      if (navbar) {
        navbar.style.paddingRight = scrollbarWidth + 'px';
      }
  }
}

/**
 * Close wallet modal
 */
function closeWalletModal() {
  const overlay = document.getElementById('walletModalOverlay');
  if (overlay) {
    overlay.classList.remove('active');
    document.body.style.overflow = 'auto';
    document.body.style.paddingRight = '0';
      // Also remove from fixed navbar
      const navbar = document.querySelector('.container-fluid.fixed-top');
      if (navbar) {
        navbar.style.paddingRight = '0';
      }
  }
  // Reset selection
  selectedPackage = null;
  document.querySelectorAll('.coin-package-btn').forEach(btn => {
    btn.classList.remove('active');
  });
}

/**
 * Display current user's coin balance
 */
function displayCurrentBalance() {
  // Fetch balance from server
  fetch('/NoteShare/api/get-wallet-balance.php')
    .then(r => r.json())
    .then(data => {
      let balance = 200; // default: 200 coins
      if (data.success) {
        balance = parseInt(data.wallet_balance);
      }
      
      // Store in localStorage for quick access
      localStorage.setItem('userCoins', balance);
      
      const balanceElement = document.getElementById('walletBalance');
      if (balanceElement) {
        balanceElement.textContent = balance.toLocaleString();
      }
    })
    .catch(error => {
      console.error('[Wallet] Error fetching balance:', error);
      // Fallback to localStorage
      const balance = parseInt(localStorage.getItem('userCoins') || '200');
      const balanceElement = document.getElementById('walletBalance');
      if (balanceElement) {
        balanceElement.textContent = balance.toLocaleString();
      }
    });
}

/**
 * Get user's current coin balance from server
 */
function getUserBalance() {
  // Note: This is now async but for compatibility, balance is fetched and displayed via displayCurrentBalance()
  // If you need to use this synchronously, use localStorage as fallback
  return parseInt(localStorage.getItem('userCoins') || '200');
}

/**
 * Set user's coin balance
 * Updates both server and local storage
 */
function setUserBalance(coins) {
  localStorage.setItem('userCoins', coins);
  displayCurrentBalance();
}

/**
 * Initialize package button click handlers
 */
function initializePackageButtons() {
  const buttons = document.querySelectorAll('.coin-package-btn');
  
  buttons.forEach(button => {
    button.addEventListener('click', function() {
      const coins = parseInt(this.getAttribute('data-coins'));
      selectPackage(coins);
    });
  });
}

/**
 * Select a preset coin package
 */
function selectPackage(coins) {
  // Deselect all buttons
  document.querySelectorAll('.coin-package-btn').forEach(btn => {
    btn.classList.remove('active');
  });

  // Select clicked button
  document.querySelector(`[data-coins="${coins}"]`).classList.add('active');
  
  selectedPackage = coins;
  updatePackageDetails(coins);
  clearCustomAmount();
}

/**
 * Update package details display
 */
function updatePackageDetails(coins) {
  const pkg = COIN_PACKAGES[coins];
  const detailsDiv = document.getElementById('walletPackageDetails');
  
  if (detailsDiv && pkg) {
    detailsDiv.innerHTML = `
      <h4>
        <img src="assets/images/coin.png" alt="coin" class="coin-icon"> 
        ${pkg.description}
      </h4>
      <p class="wallet-package-price-detail">Rs. ${pkg.price}</p>
    `;
  }
}

/**
 * Initialize custom amount input
 */
function initializeCustomAmount() {
  const customInput = document.getElementById('customCoinAmount');
  
  if (customInput) {
    customInput.addEventListener('input', function() {
      const amount = parseInt(this.value) || 0;
      
      // Deselect preset packages
      document.querySelectorAll('.coin-package-btn').forEach(btn => {
        btn.classList.remove('active');
      });
      
      selectedPackage = null;
      
      if (amount > 0) {
        // Calculate price: Rs 2.00 per coin
        const estimatedPrice = Math.ceil(amount * 2);
        updateCustomPriceDisplay(amount, estimatedPrice);
      } else {
        document.getElementById('walletCustomPrice').style.display = 'none';
      }
    });
  }
}

/**
 * Update custom price display
 */
function updateCustomPriceDisplay(coins, price) {
  const priceDisplay = document.getElementById('walletCustomPrice');
  
  if (priceDisplay) {
    priceDisplay.style.display = 'flex';
    priceDisplay.innerHTML = `
      <span class="wallet-price-label">Estimated Price:</span>
      <span class="wallet-price-value">Rs. ${price}</span>
    `;
  }
}

/**
 * Get purchase amount (from preset or custom input)
 */
function getPurchaseAmount() {
  if (selectedPackage) {
    return COIN_PACKAGES[selectedPackage];
  }
  
  const customInput = document.getElementById('customCoinAmount');
  if (customInput && customInput.value) {
    const coins = parseInt(customInput.value);
    if (coins > 0) {
      return {
        coins: coins,
        price: Math.ceil(coins * 2), // Rs 2.0 per coin
        description: `${coins} Coins`
      };
    }
  }
  
  return null;
}

/**
 * Initialize purchase button
 */
function initializePurchaseButton() {
  const purchaseBtn = document.querySelector('.wallet-purchase-btn');
  
  if (purchaseBtn) {
    purchaseBtn.addEventListener('click', processCoinPurchase);
  }
}

/**
 * Process coin purchase - redirect to payment gateway
 */
function processCoinPurchase() {
  const amount = getPurchaseAmount();
  
  if (!amount) {
    showError('Please select a coin package or enter a custom amount');
    return;
  }
  
  // Show loading state
  const btn = document.querySelector('.wallet-purchase-btn');
  const originalText = btn.innerHTML;
  btn.disabled = true;
  btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
  
  // Simulate payment processing
  setTimeout(() => {
    initiatePayment(amount);
  }, 500);
}

/**
 * Initiate payment through gateway
 */
function initiatePayment(amount) {
  // In production, this would call your backend payment API
  // which would then redirect to Razorpay or Stripe
  
  // For demo purposes, we'll create a form and submit it
  const paymentData = {
    coins: amount.coins,
    price: amount.price,
    description: amount.description,
    userId: getUserId(), // Get from session/auth
    returnUrl: window.location.origin + '/home.html' // Redirect after payment
  };
  
  // Call your backend endpoint to create order and get payment link
  createPaymentOrder(paymentData);
}

/**
 * Create payment order on backend
 */
function createPaymentOrder(paymentData) {
  // Example API call - replace with your actual endpoint
  fetch('/api/payment/create-order', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify(paymentData)
  })
  .then(response => response.json())
  .then(data => {
    if (data.success && data.paymentUrl) {
      // Redirect to payment gateway
      window.location.href = data.paymentUrl;
    } else {
      showError(data.message || 'Payment gateway error');
      resetPurchaseButton();
    }
  })
  .catch(error => {
    console.error('Payment error:', error);
    showError('Unable to process payment. Please try again.');
    resetPurchaseButton();
  });
}

/**
 * Get user ID from session/auth token
 */
function getUserId() {
  // Placeholder - get from session, localStorage, or auth context
  return localStorage.getItem('userId') || 'anonymous';
}

/**
 * Clear custom amount input
 */
function clearCustomAmount() {
  const customInput = document.getElementById('customCoinAmount');
  if (customInput) {
    customInput.value = '';
    document.getElementById('walletCustomPrice').style.display = 'none';
  }
}

/**
 * Reset purchase button to normal state
 */
function resetPurchaseButton() {
  const btn = document.querySelector('.wallet-purchase-btn');
  btn.disabled = false;
  btn.innerHTML = '<i class="fas fa-credit-card"></i> Proceed to Payment';
}

/**
 * Show error message
 */
function showError(message) {
  // Create and show error toast/alert
  const errorDiv = document.createElement('div');
  errorDiv.style.cssText = `
    position: fixed;
    top: 20px;
    right: 20px;
    background: #e74c3c;
    color: white;
    padding: 16px 24px;
    border-radius: 8px;
    z-index: 2000;
    animation: slideIn 0.3s ease;
  `;
  errorDiv.textContent = message;
  document.body.appendChild(errorDiv);
  
  setTimeout(() => {
    errorDiv.remove();
  }, 4000);
}

/**
 * Handle payment success callback
 * This is called after user completes payment and is redirected back
 */
function handlePaymentSuccess(coins) {
  const currentBalance = getUserBalance();
  setUserBalance(currentBalance + coins);
  
  // Show success message
  showSuccess(`Successfully purchased ${coins} coins!`);
  
  // Close modal and reset
  closeWalletModal();
  resetPurchaseButton();
  
  // Optionally trigger a refresh of any UI that displays coin balance
  displayCurrentBalance();
}

/**
 * Show success message
 */
function showSuccess(message) {
  const successDiv = document.createElement('div');
  successDiv.style.cssText = `
    position: fixed;
    top: 20px;
    right: 20px;
    background: #27ae60;
    color: white;
    padding: 16px 24px;
    border-radius: 8px;
    z-index: 2000;
    animation: slideIn 0.3s ease;
  `;
  successDiv.textContent = message;
  document.body.appendChild(successDiv);
  
  setTimeout(() => {
    successDiv.remove();
  }, 4000);
}
