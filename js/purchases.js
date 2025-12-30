// Purchases page logic
(function() {
  const API_GET_PURCHASES = '/NoteShare/api/get-user-purchases.php';
  const API_DELETE_PURCHASE = '/NoteShare/api/delete-purchase.php';
  
  let allPurchases = [];
  let filteredPurchases = [];
  
  // Filter state
  let filterState = {
    category: 'all',
    sortBy: 'recent',
    keyword: ''
  };
  
  // Custom confirmation dialog
  function showConfirmDialog(message, onConfirm) {
    // Create modal backdrop
    const backdrop = document.createElement('div');
    backdrop.className = 'custom-confirm-backdrop';
    backdrop.style.cssText = `
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background-color: rgba(0, 0, 0, 0.5);
      display: flex;
      align-items: center;
      justify-content: center;
      z-index: 10000;
    `;

    // Create dialog box
    const dialog = document.createElement('div');
    dialog.className = 'custom-confirm-dialog';
    dialog.style.cssText = `
      background-color: white;
      border-radius: 8px;
      padding: 30px;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
      max-width: 400px;
      text-align: center;
      animation: slideUp 0.3s ease-out;
    `;

    // Message
    const messageEl = document.createElement('p');
    messageEl.textContent = message;
    messageEl.style.cssText = `
      font-size: 16px;
      color: #333;
      margin-bottom: 25px;
      line-height: 1.5;
    `;
    dialog.appendChild(messageEl);

    // Button container
    const buttonContainer = document.createElement('div');
    buttonContainer.style.cssText = `
      display: flex;
      gap: 10px;
      justify-content: center;
    `;

    // Yes button
    const yesBtn = document.createElement('button');
    yesBtn.textContent = 'Yes';
    yesBtn.style.cssText = `
      padding: 10px 30px;
      background-color: #81c408;
      color: white;
      border: none;
      border-radius: 5px;
      font-weight: bold;
      cursor: pointer;
      transition: background-color 0.3s ease;
    `;
    yesBtn.addEventListener('mouseover', () => {
      yesBtn.style.backgroundColor = '#6fa007';
    });
    yesBtn.addEventListener('mouseout', () => {
      yesBtn.style.backgroundColor = '#81c408';
    });
    yesBtn.addEventListener('click', () => {
      backdrop.remove();
      onConfirm(true);
    });

    // No button
    const noBtn = document.createElement('button');
    noBtn.textContent = 'No';
    noBtn.style.cssText = `
      padding: 10px 30px;
      background-color: #ccc;
      color: #333;
      border: none;
      border-radius: 5px;
      font-weight: bold;
      cursor: pointer;
      transition: background-color 0.3s ease;
    `;
    noBtn.addEventListener('mouseover', () => {
      noBtn.style.backgroundColor = '#bbb';
    });
    noBtn.addEventListener('mouseout', () => {
      noBtn.style.backgroundColor = '#ccc';
    });
    noBtn.addEventListener('click', () => {
      backdrop.remove();
      onConfirm(false);
    });

    buttonContainer.appendChild(yesBtn);
    buttonContainer.appendChild(noBtn);
    dialog.appendChild(buttonContainer);

    backdrop.appendChild(dialog);
    document.body.appendChild(backdrop);

    // Close when clicking outside
    backdrop.addEventListener('click', (e) => {
      if (e.target === backdrop) {
        backdrop.remove();
        onConfirm(false);
      }
    });
  }
  
  // Apply filters and sorting
  function applyFilters() {
    // Start with all purchases
    filteredPurchases = [...allPurchases];
    
    // Filter by category
    if (filterState.category !== 'all') {
      // Map dropdown values to actual category names
      const categoryMap = {
        'notes': 'Notes',
        'videos': 'Videos',
        'exam': 'Exam Papers',
        'others': 'Others'
      };
      const targetCategory = categoryMap[filterState.category];
      
      filteredPurchases = filteredPurchases.filter(purchase => {
        return purchase.category === targetCategory;
      });
    }
    
    // Filter by keyword (search in title and description)
    if (filterState.keyword.trim() !== '') {
      const keyword = filterState.keyword.toLowerCase();
      filteredPurchases = filteredPurchases.filter(purchase => {
        const title = (purchase.title || '').toLowerCase();
        const description = (purchase.description || '').toLowerCase();
        return title.includes(keyword) || description.includes(keyword);
      });
    }
    
    // Normalize sortBy value for dropdowns
    let sortKey = filterState.sortBy;
    if (sortKey === 'recent' || sortKey === 'most-recent') sortKey = 'recent';
    if (sortKey === 'oldest' || sortKey === 'oldest-first') sortKey = 'oldest';
    switch(sortKey) {
      case 'recent':
        filteredPurchases.sort((a, b) => new Date(b.purchased_at) - new Date(a.purchased_at));
        break;
      case 'oldest':
        filteredPurchases.sort((a, b) => new Date(a.purchased_at) - new Date(b.purchased_at));
        break;
      case 'price-asc':
        filteredPurchases.sort((a, b) => a.price - b.price);
        break;
      case 'price-desc':
        filteredPurchases.sort((a, b) => b.price - a.price);
        break;
    }
    
    renderTable();
  }
  
  // Update filter state and re-apply filters
  function updateFilter(type, value) {
    filterState[type] = value;
    applyFilters();
  }
  
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
  
  // Format file size
  function formatFileSize(bytes) {
    if (bytes === 0) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return Math.round((bytes / Math.pow(k, i)) * 100) / 100 + ' ' + sizes[i];
  }
  
  // Format date
  function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
  }
  
  // Load purchases from API
  async function loadPurchases() {
    try {
      const response = await fetch(API_GET_PURCHASES);
      const data = await response.json();
      
      if (data.success) {
        allPurchases = data.items || [];
        
        // Update statistics
        const statValues = document.querySelectorAll('.stat-value');
        if (statValues[0]) statValues[0].textContent = data.total_purchases;
        if (statValues[1]) statValues[1].textContent = Math.round(data.total_spent).toLocaleString();
        
        // Apply filters with the initial state
        applyFilters();
      } else {
        console.log('[Purchases] Not logged in or error loading purchases');
      }
    } catch (error) {
      console.error('[Purchases] Error loading purchases:', error);
    }
  }
  
  // Render purchases table
  function renderTable() {
    const tbody = document.querySelector('.purchases-table tbody');
    if (!tbody) return;
    
    // Remove all existing rows (keep header)
    const allRows = tbody.querySelectorAll('tr');
    allRows.forEach(row => row.remove());
    
    if (filteredPurchases.length === 0) {
      const tr = document.createElement('tr');
      const message = allPurchases.length === 0 ? 'No purchases yet' : 'No purchases match your filters';
      tr.innerHTML = `<td colspan="6" class="text-center py-4 text-muted">${message}</td>`;
      tbody.appendChild(tr);
      initializePurchasesPagination();
      return;
    }
    
    filteredPurchases.forEach(purchase => {
      const tr = document.createElement('tr');
      
      // Map category to pill class (always include category-badge)
      let categoryClass = 'category-badge';
      switch (purchase.category) {
        case 'Notes': categoryClass += ' category-notes'; break;
        case 'Videos': categoryClass += ' category-videos'; break;
        case 'Exam Papers': categoryClass += ' category-exam'; break;
        case 'Others': categoryClass += ' category-others'; break;
        default: break;
      }
      const fileSize = purchase.file_size ? formatFileSize(purchase.file_size) : 'Unknown';
      const purchaseDate = formatDate(purchase.purchased_at);

      tr.innerHTML = `
        <td class="course-title">${purchase.title}</td>
        <td class="text-center"><span class="${categoryClass}">${purchase.category}</span></td>
        <td class="text-center">
          <div class="price-with-coin d-flex justify-content-center align-items-center">
            <img src="assets/images/coin.png" alt="Coin" class="price-coin-icon">
            <span>${Math.round(purchase.price)}</span>
          </div>
        </td>
        <td class="purchase-date text-center">${purchaseDate}</td>
        <td class="file-size text-center">${fileSize}</td>
        <td class="text-center">
          <div class="action-icons d-flex justify-content-center align-items-center">
            <button class="action-btn download-btn" title="Download" data-material-id="${purchase.material_id}" data-title="${purchase.title.replace(/"/g, '&quot;')}" data-file="${purchase.file_path}"><i class="fas fa-download"></i></button>
            <button class="action-btn view-btn" title="View" data-material-id="${purchase.material_id}" data-title="${purchase.title.replace(/"/g, '&quot;')}" data-file="${purchase.file_path}"><i class="fas fa-eye"></i></button>
            <button class="action-btn delete-btn" title="Delete" data-purchase-item-id="${purchase.purchase_item_id}"><i class="fas fa-trash"></i></button>
          </div>
        </td>
      `;
      
      tbody.appendChild(tr);
    });
    
    // Attach event listeners
    attachEventListeners();
    
    // Re-initialize pagination
    initializePurchasesPagination();
  }
  
  // Attach event listeners for action buttons
  function attachEventListeners() {
    // Download buttons
    document.querySelectorAll('.download-btn').forEach(btn => {
      btn.addEventListener('click', function(e) {
        e.preventDefault();
        const filePath = this.getAttribute('data-file');
        const title = this.getAttribute('data-title');
        downloadFile(filePath, title);
      });
    });
    
    // View buttons
    document.querySelectorAll('.view-btn').forEach(btn => {
      btn.addEventListener('click', function(e) {
        e.preventDefault();
        const filePath = this.getAttribute('data-file');
        const title = this.getAttribute('data-title');
        viewFile(filePath, title);
      });
    });
    
    // Delete buttons
    document.querySelectorAll('.delete-btn').forEach(btn => {
      btn.addEventListener('click', function(e) {
        e.preventDefault();
        const purchaseItemId = this.getAttribute('data-purchase-item-id');
        showConfirmDialog('Are you sure you want to delete this purchase?', (confirmed) => {
          if (confirmed) {
            deletePurchase(purchaseItemId);
          }
        });
      });
    });
  }
  
  // Download file
  function downloadFile(filePath, title) {
    const link = document.createElement('a');
    link.href = '/NoteShare/' + filePath;
    link.download = title || 'download';
    link.click();
    showToast('Download started', 'success');
  }
  
  // View file in new tab with custom title
  function viewFile(filePath, title) {
    const encodedFile = encodeURIComponent('/NoteShare/' + filePath);
    const encodedTitle = encodeURIComponent(title || 'NoteShare - View File');
    const viewerUrl = `/NoteShare/viewer.html?file=${encodedFile}&title=${encodedTitle}`;
    window.open(viewerUrl, '_blank');
    showToast('Opening file...', 'success');
  }
  
  // Delete purchase
  function deletePurchase(purchaseItemId) {
    const formData = new FormData();
    formData.append('purchase_item_id', purchaseItemId);
    
    fetch(API_DELETE_PURCHASE, {
      method: 'POST',
      body: formData
    })
    .then(r => r.json())
    .then(data => {
      if (data.success) {
        showToast('Purchase deleted successfully.', 'success');
        // Reload purchases
        loadPurchases();
      } else {
        showToast('Error deleting purchase.', 'error');
      }
    })
    .catch(err => {
      console.error('[Purchases] Error deleting purchase:', err);
      showToast('Error deleting purchase', 'error');
    });
  }
  
  // Initialize custom dropdowns
  function initializeCustomDropdowns() {
    const dropdowns = document.querySelectorAll('.custom-dropdown');
    if (!dropdowns.length) return;

    dropdowns.forEach(drop => {
        const button = drop.querySelector('.custom-dropdown-button');
        const menu = drop.querySelector('.custom-dropdown-menu');
        const options = drop.querySelectorAll('.custom-dropdown-option');
        const hiddenSelect = drop.querySelector('select');

        if (!button || !menu || !hiddenSelect) return;

        // Toggle menu
        button.addEventListener('click', function(e) {
            e.preventDefault();
            document.querySelectorAll('.custom-dropdown-menu.active').forEach(m => {
                if (m !== menu) m.classList.remove('active');
            });
            menu.classList.toggle('active');
        });

        // Option click
        options.forEach(opt => {
            opt.addEventListener('click', function() {
                const value = this.getAttribute('data-value');
                const text = this.textContent;

                button.textContent = text;
                hiddenSelect.value = value;

                options.forEach(o => o.classList.remove('selected'));
                this.classList.add('selected');

                menu.classList.remove('active');
                
                // Trigger change event on the hidden select
                const event = new Event('change', { bubbles: true });
                hiddenSelect.dispatchEvent(event);
            });

            opt.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' || e.key === ' ') this.click();
            });
        });
    });

    document.addEventListener('click', function(e) {
        if (!e.target.closest('.custom-dropdown')) {
            document.querySelectorAll('.custom-dropdown-menu.active').forEach(m => m.classList.remove('active'));
        }
    });
  }

  // Purchases table pagination
  function initializePurchasesPagination() {
    const table = document.querySelector('.purchases-table');
    if (!table) return;

    const tbody = table.querySelector('tbody');
    if (!tbody) return;

    const rows = Array.from(tbody.querySelectorAll('tr'));
    const rowsPerPage = 10;
    let currentPage = 1;
    const totalPages = Math.max(1, Math.ceil(rows.length / rowsPerPage));

    function renderPage(page) {
        currentPage = Math.min(Math.max(1, page), totalPages);
        const start = (currentPage - 1) * rowsPerPage;
        const end = start + rowsPerPage;

        rows.forEach((r, i) => {
            r.style.display = (i >= start && i < end) ? '' : 'none';
        });

        renderPaginationControls();
    }

    function renderPaginationControls() {
        const container = document.getElementById('purchases-pagination');
        if (!container) return;
        container.innerHTML = '';

        const prevLi = document.createElement('li');
        prevLi.className = 'page-item' + (currentPage === 1 ? ' disabled' : '');
        const prevA = document.createElement('a');
        prevA.className = 'page-link';
        prevA.href = '#';
        prevA.textContent = '«';
        prevA.addEventListener('click', function(e) {
            e.preventDefault();
            if (currentPage > 1) renderPage(currentPage - 1);
        });
        prevLi.appendChild(prevA);
        container.appendChild(prevLi);

        for (let p = 1; p <= totalPages; p++) {
            const li = document.createElement('li');
            li.className = 'page-item' + (p === currentPage ? ' active' : '');
            const a = document.createElement('a');
            a.className = 'page-link';
            a.href = '#';
            a.textContent = String(p);
            a.dataset.page = String(p);
            a.addEventListener('click', function(e) {
                e.preventDefault();
                const targetPage = parseInt(this.dataset.page, 10);
                if (!isNaN(targetPage)) renderPage(targetPage);
            });
            li.appendChild(a);
            container.appendChild(li);
        }

        const nextLi = document.createElement('li');
        nextLi.className = 'page-item' + (currentPage === totalPages ? ' disabled' : '');
        const nextA = document.createElement('a');
        nextA.className = 'page-link';
        nextA.href = '#';
        nextA.textContent = '»';
        nextA.addEventListener('click', function(e) {
            e.preventDefault();
            if (currentPage < totalPages) renderPage(currentPage + 1);
        });
        nextLi.appendChild(nextA);
        container.appendChild(nextLi);
    }

    renderPage(1);
  }

  // Initialize on page load
  document.addEventListener('DOMContentLoaded', function() {
    initializeCustomDropdowns();
    
    // Setup filter event listeners
    setupFilterListeners();
    
    loadPurchases();
  });
  
  // Setup filter control listeners
  function setupFilterListeners() {
    // Category dropdown
    const categorySelect = document.getElementById('categorySelect');
    if (categorySelect) {
      categorySelect.addEventListener('change', function() {
        updateFilter('category', this.value);
      });
    }
    
    // Sort dropdown
    const sortSelect = document.getElementById('sortSelect');
    if (sortSelect) {
      sortSelect.addEventListener('change', function() {
        updateFilter('sortBy', this.value);
      });
    }
    
    // Search button and keyword input
    const searchBtn = document.querySelector('.btn-search');
    const keywordInput = document.querySelector('.filter-search');
    
    if (searchBtn && keywordInput) {
      searchBtn.addEventListener('click', function() {
        updateFilter('keyword', keywordInput.value);
      });
      
      // Allow Enter key in search box
      keywordInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
          updateFilter('keyword', keywordInput.value);
        }
      });
    }
  }
})();
