/* ========================================
   UPLOADS PAGE JAVASCRIPT
   ======================================== */

document.addEventListener('DOMContentLoaded', function() {
    // Fix layout shifts caused by scrollbar appearing/disappearing on modals
    fixModalLayoutShift();
    
    // Navbar behavior is handled by shared `navBar.js` to keep behavior consistent across pages
    // (avoid calling initializeNavbarScroll here to prevent conflicts)
    
    // Ensure Chart.js is loaded, prefer local but fall back to CDN if missing
    ensureChartJsLoaded().then(function(loaded){
        if (loaded) {
            try { initializeCharts(); } catch(e) { console.error('initializeCharts failed:', e); }
        } else {
            console.warn('Chart.js failed to load, charts will not be displayed');
        }
    });
    
    // Setup file upload handlers
    setupFileUpload();
    
    // Setup form submission
    setupFormSubmission();
    
    // Setup action buttons
    setupActionButtons();
    
    // Setup modal handlers
    setupModalHandlers();
    
    // Intercept view button clicks and open viewer.html with title and file
    document.addEventListener('click', function(e) {
        const v = e.target.closest('.view-btn');
        if (!v) return;
        e.preventDefault();
        const file = v.getAttribute('href') || v.dataset.file;
        const title = v.getAttribute('data-title') || v.dataset.title || 'Viewer';
        if (!file) {
            // fallback, just open normally
            window.open(v.href || '#', '_blank');
            return;
        }
        const url = 'viewer.html?file=' + encodeURIComponent(file) + '&title=' + encodeURIComponent(title);
        window.open(url, '_blank');
    });
    
    // Load categories and uploads from server
    if (typeof loadCategories === 'function') loadCategories();
    if (typeof loadUploads === 'function') loadUploads();
    if (typeof loadSalesEarnings === 'function') loadSalesEarnings();
    // Initialize submit button state
    if (typeof updateSubmitState === 'function') updateSubmitState();
});

// Toast helper (lower-left)
function showToast(message, type='info', timeout=5000) {
    let container = document.querySelector('.upload-toast-container');
    if (!container) {
        container = document.createElement('div');
        container.className = 'upload-toast-container';
        document.body.appendChild(container);
    }
    const t = document.createElement('div');
    t.className = 'upload-toast ' + (type || 'info');
    t.textContent = message;
    
    // Add size class based on message length
    if (message.includes('Upload Successful') || message.includes('Preview') || message.includes('Edit')) {
        t.classList.add('toast-small');
    } else if (message.includes('Deleted Successfully') || message.includes('Delete failed') || message.includes('failed')) {
        t.classList.add('toast-large');
    }
    
    container.appendChild(t);
    // animate in
    requestAnimationFrame(() => t.classList.add('show'));
    setTimeout(() => {
        t.classList.remove('show');
        setTimeout(() => { try { t.remove() } catch(e){} }, 220);
    }, timeout);
}

// Custom confirm dialog using the modal added to uploads.html
function showConfirm(message, onYes, onNo) {
    const modalEl = document.getElementById('confirmModal');
    const msgEl = document.getElementById('confirmModalMessage');
    const yesBtn = document.getElementById('confirmYesBtn');
    const noBtn = document.getElementById('confirmCancelBtn');
    if (!modalEl || !msgEl || !yesBtn || !noBtn) {
        // Fallback to native confirm
        if (confirm(message)) { if (onYes) onYes(); } else { if (onNo) onNo(); }
        return;
    }
    msgEl.textContent = message;
    const modal = new bootstrap.Modal(modalEl);
    
    // Store previous active element to restore focus later
    const previousActiveElement = document.activeElement;
    
    // Create handlers
    const yesHandler = () => { 
        // Move focus away before hiding modal to avoid aria-hidden conflict
        document.body.focus();
        if (onYes) onYes(); 
        // Close modal after callback completes
        setTimeout(() => {
            modal.hide();
            // Try to restore focus if possible
            if (previousActiveElement && previousActiveElement.focus) {
                try { previousActiveElement.focus(); } catch(e) {}
            }
        }, 50);
    };
    
    const noHandler = () => { 
        // Move focus away before hiding modal
        document.body.focus();
        if (onNo) onNo(); 
        modal.hide();
        // Try to restore focus if possible
        if (previousActiveElement && previousActiveElement.focus) {
            try { previousActiveElement.focus(); } catch(e) {}
        }
    };
    
    yesBtn.addEventListener('click', yesHandler);
    noBtn.addEventListener('click', noHandler);
    modal.show();
}

/* ========================================
   MODAL LAYOUT SHIFT FIX
   ======================================== */

/**
 * Ensure Chart.js is available. Returns a Promise that resolves true when Chart is loaded.
 * Tries the global `Chart` first, then attempts to load CDN copy as a fallback.
 */
function ensureChartJsLoaded() {
    return new Promise((resolve) => {
        if (typeof Chart !== 'undefined') {
            return resolve(true);
        }

        // Try to load CDN version
        var cdn = 'https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js';
        loadExternalScript(cdn, function(){
            console.log('Chart.js loaded from CDN fallback');
            // small delay to allow script to evaluate
            setTimeout(function(){ resolve(typeof Chart !== 'undefined'); }, 50);
        }, function(){
            console.warn('Chart.js CDN fallback failed');
            resolve(false);
        });
    });
}

function loadExternalScript(src, onload, onerror) {
    try {
        var s = document.createElement('script');
        s.src = src;
        s.async = false;
        if (onload) s.onload = onload;
        if (onerror) s.onerror = onerror;
        document.head.appendChild(s);
    } catch (e) {
        if (onerror) onerror(e);
    }
}

/**
 * Navbar scroll behavior - topbar slides up on scroll down, slides down on scroll up
 */
function initializeNavbarScroll() {
    const topbar = document.querySelector('.topbar');
    const fixedContainer = document.querySelector('.container-fluid.fixed-top');
    if (!topbar || !fixedContainer) return;

    // Find the nav element inside the fixed container (the main navbar)
    const navEl = fixedContainer.querySelector('nav');
    // Ensure smooth transition on both topbar and nav
    topbar.style.transition = 'transform 0.3s ease';
    if (navEl) navEl.style.transition = 'transform 0.3s ease';

    let lastScrollTop = window.pageYOffset || document.documentElement.scrollTop || 0;
    let isTopbarVisible = true;

    window.addEventListener('scroll', function() {
        let currentScroll = window.pageYOffset || document.documentElement.scrollTop || 0;

        if (currentScroll > lastScrollTop) {
            // Scrolling DOWN - hide only the topbar and lift the nav up
            if (isTopbarVisible) {
                const h = topbar.offsetHeight || 60;
                topbar.style.transform = `translateY(-${h}px)`; // hide topbar
                if (navEl) navEl.style.transform = `translateY(-${h}px)`; // move nav up to fill gap
                isTopbarVisible = false;
            }
        } else {
            // Scrolling UP - show topbar
            if (!isTopbarVisible) {
                topbar.style.transform = 'translateY(0)';
                if (navEl) navEl.style.transform = 'translateY(0)';
                isTopbarVisible = true;
            }
        }

        lastScrollTop = currentScroll <= 0 ? 0 : currentScroll;
    });
}

/**
 * Layout shift fix disabled — Bootstrap handles modal scrollbar compensation natively
 * via the .modal-open class. Manual fixes cause double-shifting jank.
 */
function fixModalLayoutShift() {
    // Intentionally empty — Bootstrap's native .modal-open class handles scrollbar compensation
}

/**
 * Calculates the width of the scrollbar (with fallback to 17px if unable to calculate)
 */
function getScrollbarWidth() {
    // Create test element
    const div = document.createElement('div');
    div.style.overflow = 'scroll';
    div.style.width = '100px';
    div.style.height = '100px';
    div.style.visibility = 'hidden';
    document.body.appendChild(div);
    
    // Get scrollbar width
    const scrollbarWidth = div.offsetWidth - div.clientWidth;
    document.body.removeChild(div);
    
    // Return calculated width or fallback to 17px
    return scrollbarWidth > 0 ? scrollbarWidth : 17;
}

/* ========================================
   CUSTOM DROPDOWN HANDLER
   ======================================== */

function initializeCustomDropdown() {
    const button = document.getElementById('categoryDropdownButton');
    const menu = document.querySelector('.custom-dropdown-menu');
    const options = document.querySelectorAll('.custom-dropdown-option');
    const hiddenSelect = document.getElementById('categorySelect');
    
    if (!button || !menu) return;
    
    // Clear any existing listeners by removing and re-adding button
    const newButton = button.cloneNode(true);
    button.parentNode.replaceChild(newButton, button);
    const refreshedButton = document.getElementById('categoryDropdownButton');
    
    // Toggle dropdown on button click
    refreshedButton.addEventListener('click', function(e) {
        e.preventDefault();
        menu.classList.toggle('active');
    });
    
    // Handle option selection
    options.forEach(option => {
        option.addEventListener('click', function() {
            const value = this.getAttribute('data-value');
            const text = this.textContent;
            
            // Update button text
            refreshedButton.textContent = text;
            
            // Update hidden select and trigger change event
            hiddenSelect.value = value;
            const event = new Event('change', { bubbles: true });
            hiddenSelect.dispatchEvent(event);
            
            // Update active state
            options.forEach(opt => opt.classList.remove('selected'));
            this.classList.add('selected');
            
            // Close menu
            menu.classList.remove('active');
            
            // Update submit button state
            updateSubmitState();
        });
        
        option.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                this.click();
            }
        });
    });
    
    // Close dropdown when clicking outside
    const closeDropdownHandler = function(e) {
        if (!e.target.closest('.custom-dropdown')) {
            menu.classList.remove('active');
        }
    };
    document.removeEventListener('click', closeDropdownHandler);
    document.addEventListener('click', closeDropdownHandler);
    
    // Set initial selected option based on hidden select value (if present)
    const firstOption = options[0];
    if (firstOption) {
        const preferred = hiddenSelect && hiddenSelect.value ? hiddenSelect.value : null;
        let initial = null;
        if (preferred) {
            initial = Array.from(options).find(opt => opt.getAttribute('data-value') === preferred);
        }
        if (!initial) initial = firstOption;
        initial.classList.add('selected');
        if (refreshedButton) refreshedButton.textContent = initial.textContent;
        if (hiddenSelect) hiddenSelect.value = initial.getAttribute('data-value') || hiddenSelect.value;
    }
}

/* ========================================
   FILE UPLOAD HANDLERS
   ======================================== */

function setupFileUpload() {
    const fileInput = document.getElementById('fileInput');
    const fileUploadWrapper = document.querySelector('.file-upload-wrapper');
    const filePreview = document.getElementById('filePreview');

    // Click to upload
    fileUploadWrapper.addEventListener('click', () => fileInput.click());

    // Drag and drop
    fileUploadWrapper.addEventListener('dragover', (e) => {
        e.preventDefault();
        e.stopPropagation();
        fileUploadWrapper.classList.add('dragover');
    });

    fileUploadWrapper.addEventListener('dragleave', (e) => {
        e.preventDefault();
        e.stopPropagation();
        fileUploadWrapper.classList.remove('dragover');
    });

    fileUploadWrapper.addEventListener('drop', (e) => {
        e.preventDefault();
        e.stopPropagation();
        fileUploadWrapper.classList.remove('dragover');
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            // Only accept the first dropped file (single upload per submit)
            const dt = new DataTransfer();
            dt.items.add(files[0]);
            fileInput.files = dt.files;
            handleFileSelect();
        }
    });

    // File input change
    fileInput.addEventListener('change', handleFileSelect);
}

function handleFileSelect() {
    const fileInput = document.getElementById('fileInput');
    const filePreview = document.getElementById('filePreview');
    const file = fileInput.files[0];

    if (file) {
        const fileSize = formatFileSize(file.size);
        const fileIcon = getFileIcon(file.type);
        
        const previewHTML = `
            <div class="file-preview-item">
                <div class="file-preview-icon">
                    ${fileIcon}
                </div>
                <div class="file-preview-info">
                    <div class="file-preview-name">${file.name}</div>
                    <div class="file-preview-size">${fileSize}</div>
                </div>
                <button type="button" class="file-preview-remove" onclick="clearFileInput()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;
        
        filePreview.innerHTML = previewHTML;
        filePreview.classList.add('active');
    }
}

function clearFileInput() {
    document.getElementById('fileInput').value = '';
    document.getElementById('filePreview').innerHTML = '';
    document.getElementById('filePreview').classList.remove('active');
}

function clearFormFields() {
    console.log('clearFormFields called');
    
    // Clear title
    const titleInput = document.getElementById('titleInput');
    if (titleInput) {
        titleInput.value = '';
        console.log('Cleared title');
    }
    
    // Clear description
    const descriptionInput = document.getElementById('descriptionInput');
    if (descriptionInput) {
        descriptionInput.value = '';
        console.log('Cleared description');
    }
    
    // Clear price
    const priceInput = document.getElementById('priceInput');
    if (priceInput) {
        priceInput.value = '';
        console.log('Cleared price');
    }
    
    // Reset category dropdown
    const categorySelect = document.getElementById('categorySelect');
    const categoryDropdownButton = document.getElementById('categoryDropdownButton');
    
    if (categorySelect && categorySelect.options && categorySelect.options.length > 0) {
        categorySelect.selectedIndex = 0;
        const firstOptionValue = categorySelect.options[0].value;
        const firstOptionText = categorySelect.options[0].text;
        categorySelect.value = firstOptionValue;
        console.log('Reset category select to:', firstOptionValue);
        
        if (categoryDropdownButton) {
            categoryDropdownButton.textContent = firstOptionText;
            console.log('Reset dropdown button text to:', firstOptionText);
        }
    }
    
    // Update custom dropdown options styling
    const allOptions = document.querySelectorAll('.custom-dropdown-option');
    if (allOptions.length > 0) {
        allOptions.forEach((opt, idx) => {
            opt.classList.remove('selected');
            if (idx === 0) {
                opt.classList.add('selected');
            }
        });
        console.log('Reset custom dropdown options');
    }
    
    // Close dropdown menu if open
    const dropdownMenu = document.querySelector('.custom-dropdown-menu');
    if (dropdownMenu) {
        dropdownMenu.classList.remove('active');
        console.log('Closed dropdown menu');
    }
}

function getFileIcon(mimeType) {
    if (mimeType.includes('pdf')) {
        return '<i class="fas fa-file-pdf" style="color: #ff6b6b;"></i>';
    } else if (mimeType.includes('video')) {
        return '<i class="fas fa-file-video" style="color: #3498db;"></i>';
    } else if (mimeType.includes('word') || mimeType.includes('document')) {
        return '<i class="fas fa-file-word" style="color: #2ecc71;"></i>';
    } else if (mimeType.includes('sheet')) {
        return '<i class="fas fa-file-excel" style="color: #27ae60;"></i>';
    } else if (mimeType.includes('presentation')) {
        return '<i class="fas fa-file-powerpoint" style="color: #e74c3c;"></i>';
    } else {
        return '<i class="fas fa-file" style="color: #95a5a6;"></i>';
    }
}

function formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return Math.round((bytes / Math.pow(k, i)) * 100) / 100 + ' ' + sizes[i];
}

/* ========================================
   FORM SUBMISSION
   ======================================== */

function setupFormSubmission() {
    const uploadForm = document.getElementById('uploadForm');
    const submitBtn = uploadForm.querySelector('button[type="submit"]');
    // ensure button is disabled until required fields are filled
    if (submitBtn) submitBtn.disabled = true;
    // wire inputs to update submit state
    const inputsToWatch = ['titleInput','descriptionInput','categorySelect','priceInput','fileInput'];
    inputsToWatch.forEach(id => {
        const el = document.getElementById(id);
        if (!el) return;
        el.addEventListener('input', updateSubmitState);
        el.addEventListener('change', updateSubmitState);
    });
    
    uploadForm.addEventListener('submit', function(e) {
        e.preventDefault();
        const title = document.getElementById('titleInput').value.trim();
        const description = document.getElementById('descriptionInput').value.trim();
        const categorySelect = document.getElementById('categorySelect');
        const category = categorySelect ? categorySelect.value.trim() : '';
        let price = document.getElementById('priceInput').value.trim();
        const fileInput = document.getElementById('fileInput');

        if (!title || !description || !category || !price || !fileInput.files[0]) {
            showToast('Please fill all required fields', 'error');
            console.log('Form validation failed:', {title: !!title, description: !!description, category: !!category, price: !!price, file: !!fileInput.files[0]});
            return;
        }

        // Disable submit button to prevent double submissions
        if (submitBtn) submitBtn.disabled = true;
        
        // Show toast INSTANTLY
        showToast('Upload Successful', 'success');

        // Save form data before any clearing
        const file = fileInput.files[0];
        const formData = new FormData();
        formData.append('file', file);
        formData.append('title', title);
        formData.append('description', description);
        formData.append('category', category);
        // ensure price is an integer (whole coins only)
        price = parseInt(price, 10) || 0;
        formData.append('price', price);
        
        // Debug log
        console.log('Sending upload:', {title, description, category, price, fileName: file.name});
        
        // Debug: Log FormData contents
        console.log('FormData contents:');
        for (let [key, value] of formData.entries()) {
            if (key === 'file') {
                console.log(`  ${key}: File(${value.name}, ${value.type}, ${value.size} bytes)`);
            } else {
                console.log(`  ${key}: ${value}`);
            }
        }

        // Clear form INSTANTLY (while server processes in background)
        uploadForm.reset();
        clearFileInput();
        clearFormFields();
        updateSubmitState();
        document.getElementById('titleInput').value = '';
        document.getElementById('descriptionInput').value = '';
        document.getElementById('priceInput').value = '';
        document.getElementById('fileInput').value = '';

        // Send to server
        fetch('api/handle-upload.php', {
            method: 'POST',
            body: formData,
            credentials: 'same-origin'
        }).then(r => {
            console.log('Response status:', r.status);
            return r.json();
        })
        .then(data => {
            console.log('Response data:', data);
            if (data.success) {
                console.log('Upload successful, reloading uploads...');
                // Add small delay to ensure database commit completes
                setTimeout(() => {
                    loadUploads();
                    loadSalesEarnings();
                    refreshCharts();
                }, 100);
                if (submitBtn) submitBtn.disabled = true;
                
                // Trigger async thumbnail generation in the background (don't wait for it)
                if (data.material_id) {
                    console.log('Triggering async thumbnail generation for material ID:', data.material_id);
                    generateThumbnailAsync(data.material_id).catch(err => {
                        console.error('Async thumbnail generation failed:', err);
                        // This is not critical - the user can see the upload even without a thumbnail
                    });
                }
            } else {
                showToast('Upload failed: ' + (data.message || 'Unknown error'), 'error');
                if (submitBtn) submitBtn.disabled = false;
            }
        }).catch(err => {
            console.error('Upload error:', err);
            showToast('Upload error. See console for details.', 'error');
            if (submitBtn) submitBtn.disabled = false;
        });
    });

    // Ensure price input only accepts whole numbers while typing
    const priceEl = document.getElementById('priceInput');
    if (priceEl) {
        priceEl.addEventListener('input', function(){
            // remove any decimal point and trailing fractions
            if (this.value && this.value.indexOf('.') !== -1) {
                this.value = this.value.split('.')[0];
            }
            // remove non-digit chars
            this.value = this.value.replace(/[^0-9]/g, '');
        });
    }
}

// Enable/disable submit button based on required fields being present
function updateSubmitState(){
    const uploadForm = document.getElementById('uploadForm');
    if (!uploadForm) return;
    const submitBtn = uploadForm.querySelector('button[type="submit"]');
    const title = document.getElementById('titleInput').value.trim();
    const description = document.getElementById('descriptionInput').value.trim();
    const categorySelect = document.getElementById('categorySelect');
    const category = categorySelect ? categorySelect.value.trim() : '';
    const price = document.getElementById('priceInput').value.trim();
    const fileInput = document.getElementById('fileInput');
    const filePresent = fileInput && fileInput.files && fileInput.files.length > 0;
    
    const ok = title && description && category && price && filePresent;
    if (submitBtn) submitBtn.disabled = !ok;
}

/* ========================================
   ACTION BUTTONS (DELETE, EDIT, VIEW)
   ======================================== */

function setupActionButtons() {
    // Delete buttons
    const deleteButtons = document.querySelectorAll('.delete-btn');
    deleteButtons.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const id = this.dataset.id;
            showConfirm('Are you sure you want to delete this file?', function(){
                deleteUpload(id, function() {
                    showToast('File Deleted Successfully', 'success', 5000);
                });
            });
        });
    });
    
    // Edit buttons
    const editButtons = document.querySelectorAll('.edit-btn');
    editButtons.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            showToast('Edit functionality coming soon!', 'info');
        });
    });
    
    // View buttons
    const viewButtons = document.querySelectorAll('.view-btn');
    viewButtons.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            showToast('Preview functionality coming soon!', 'info');
        });
    });
}

/* ========================================
   MODAL HANDLERS
   ======================================== */

function setupModalHandlers() {
    // Get the stat card (Total Uploads)
    const totalUploadsCard = document.querySelector('.stat-card .uploads-icon').closest('.stat-card');
    const viewAllUploadsBtn = document.getElementById('viewAllUploadsBtn');
    const allUploadsModalElement = document.getElementById('allUploadsModal');
    const allUploadsModal = new bootstrap.Modal(allUploadsModalElement);
    
    // Bootstrap handles scrollbar compensation natively via .modal-open class
    // No need for additional layout-shift fix listeners
    
    // Click on Total Uploads stat card to open modal
    if (totalUploadsCard) {
        totalUploadsCard.addEventListener('click', function(e) {
            e.preventDefault();
            populateAllUploadsModal();
            allUploadsModal.show();
        });
        totalUploadsCard.style.cursor = 'pointer';
    }
    
    // Click on View All Uploads button to open modal
    if (viewAllUploadsBtn) {
        viewAllUploadsBtn.addEventListener('click', function(e) {
            e.preventDefault();
            populateAllUploadsModal();
            allUploadsModal.show();
        });
    }
}

function populateAllUploadsModal() {
    const container = document.getElementById('allUploadsContainer');
    const timestamp = Date.now();
    
    // Fetch all uploads from server to show them in modal (with cache-busting)
    fetch('api/get-user-uploads.php?t=' + timestamp, { 
        method: 'POST', 
        credentials: 'same-origin',
        headers: {
            'Cache-Control': 'no-cache, no-store, must-revalidate',
            'Pragma': 'no-cache'
        }
    })
    .then(r => r.json())
    .then(data => {
        if (data.success && data.uploads && data.uploads.length > 0) {
            renderAllUploads(data.uploads);
        } else {
            container.innerHTML = '<p class="text-center text-muted" style="padding: 40px 20px;">No uploads yet. Start by uploading your first material!</p>';
        }
    })
    .catch(err => {
        console.error('Error loading uploads for modal:', err);
        container.innerHTML = '<p class="text-center text-muted" style="padding: 40px 20px;">Error loading uploads</p>';
    });
}
/* ========================================
   CHARTS INITIALIZATION
   ======================================== */

// Chart instances - store globally so we can update them
let salesByCategory_chart = null;
let monthlyEarnings_chart = null;

function initializeCharts() {
    // Ensure Chart.js is available before attempting to use it
    if (typeof Chart === 'undefined') {
        console.warn('Chart.js not available yet, will retry...');
        // Retry after a short delay
        setTimeout(function() {
            if (typeof Chart !== 'undefined') {
                initializeCharts();
            }
        }, 500);
        return;
    }
    
    // Sales by Category Chart - fetch real data
    const categoryCtx = document.getElementById('categoryChart');
    if (categoryCtx) {
        if (typeof Chart !== 'undefined') {
            fetch('api/get-sales-by-category.php', {
                method: 'POST',
                credentials: 'same-origin'
            })
            .then(r => {
                if (!r.ok) throw new Error(`HTTP ${r.status}`);
                return r.json();
            })
            .then(data => {
                console.log('Sales by category data:', data);
                let chartData = [0, 0, 0, 0]; // Default: Notes, Videos, Exam Papers, Others
                
                if (data.success && data.sales) {
                    chartData = data.sales;
                }
                
                // Destroy old chart if it exists
                if (salesByCategory_chart) {
                    salesByCategory_chart.destroy();
                }
                
                salesByCategory_chart = new Chart(categoryCtx, {
                    type: 'doughnut',
                    data: {
                        labels: ['Notes', 'Videos', 'Exam Papers', 'Others'],
                        datasets: [{
                            data: chartData,
                            backgroundColor: [
                                '#80C157',
                                '#fcbd40',
                                '#3498db',
                                '#e74c3c'
                            ],
                            borderColor: '#ffffff',
                            borderWidth: 3,
                            hoverOffset: 15
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    fontFamily: "'Open Sans', sans-serif",
                                    fontSize: 12,
                                    padding: 15,
                                    usePointStyle: true,
                                    pointStyle: 'circle'
                                }
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(context) {
                                        const label = context.label || '';
                                        const value = context.parsed || 0;
                                        return ' ' + label + ': ' + value + ' sales';
                                    }
                                },
                                padding: 12,
                                titleFont: { size: 13, weight: 'bold' },
                                bodyFont: { size: 12 },
                                displayColors: true,
                                boxPadding: 5
                            }
                        }
                    }
                });
            })
            .catch(err => {
                console.error('Failed to load sales by category:', err);
                // Create chart with empty data on error
                if (salesByCategory_chart) {
                    salesByCategory_chart.destroy();
                }
                
                salesByCategory_chart = new Chart(categoryCtx, {
                    type: 'doughnut',
                    data: {
                        labels: ['Notes', 'Videos', 'Exam Papers', 'Others'],
                        datasets: [{
                            data: [0, 0, 0, 0],
                            backgroundColor: [
                                '#80C157',
                                '#fcbd40',
                                '#3498db',
                                '#e74c3c'
                            ],
                            borderColor: '#ffffff',
                            borderWidth: 3,
                            hoverOffset: 15
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    fontFamily: "'Open Sans', sans-serif",
                                    fontSize: 12,
                                    padding: 15,
                                    usePointStyle: true,
                                    pointStyle: 'circle'
                                }
                            }
                        }
                    }
                });
            });
        } else {
            console.warn('Chart.js not loaded — skipping Sales by Category chart initialization');
        }
    }
    
    // Monthly Earnings Chart - fetch real data
    const earningsCtx = document.getElementById('earningsChart');
    if (earningsCtx) {
        if (typeof Chart !== 'undefined') {
            fetch('api/get-monthly-earnings.php', {
                method: 'POST',
                credentials: 'same-origin'
            })
            .then(r => {
                if (!r.ok) throw new Error(`HTTP ${r.status}`);
                return r.json();
            })
            .then(data => {
                console.log('Monthly earnings data:', data);
                let earningsData = Array(12).fill(0); // Default: 0 for all months
                
                if (data.success && data.earnings) {
                    earningsData = data.earnings;
                }
                
                // Destroy old chart if it exists
                if (monthlyEarnings_chart) {
                    monthlyEarnings_chart.destroy();
                }
                
                monthlyEarnings_chart = new Chart(earningsCtx, {
                    type: 'line',
                    data: {
                        labels: ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
                        datasets: [{
                            label: 'Coins Earned',
                            data: earningsData,
                            borderColor: '#80C157',
                            backgroundColor: 'rgba(128, 193, 87, 0.1)',
                            borderWidth: 3,
                            fill: true,
                            tension: 0.4,
                            pointRadius: 5,
                            pointBackgroundColor: '#80C157',
                            pointBorderColor: '#ffffff',
                            pointBorderWidth: 2,
                            pointHoverRadius: 7
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    fontFamily: "'Open Sans', sans-serif",
                                    fontSize: 12,
                                    stepSize: 200
                                },
                                grid: {
                                    color: 'rgba(0, 0, 0, 0.05)'
                                }
                            },
                            x: {
                                ticks: {
                                    fontFamily: "'Open Sans', sans-serif",
                                    fontSize: 12,
                                    callback: function(value) {
                                        const monthLabels = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                                        return monthLabels[value];
                                    }
                                },
                                grid: {
                                    display: false
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                display: false
                            },
                            tooltip: {
                                callbacks: {
                                    title: function(context) {
                                        return context[0].label;
                                    },
                                    label: function(context) {
                                        const value = context.parsed.y || 0;
                                        return ' Coins Earned: ' + value;
                                    }
                                },
                                padding: 12,
                                titleFont: { size: 13, weight: 'bold' },
                                bodyFont: { size: 12 },
                                displayColors: true,
                                boxPadding: 5
                            }
                        }
                    }
                });
            })
            .catch(err => {
                console.error('Failed to load monthly earnings:', err);
                // Create chart with empty data on error
                if (monthlyEarnings_chart) {
                    monthlyEarnings_chart.destroy();
                }
                
                monthlyEarnings_chart = new Chart(earningsCtx, {
                    type: 'line',
                    data: {
                        labels: ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],
                        datasets: [{
                            label: 'Coins Earned',
                            data: Array(12).fill(0),
                            borderColor: '#80C157',
                            backgroundColor: 'rgba(128, 193, 87, 0.1)',
                            borderWidth: 3,
                            fill: true,
                            tension: 0.4,
                            pointRadius: 5,
                            pointBackgroundColor: '#80C157',
                            pointBorderColor: '#ffffff',
                            pointBorderWidth: 2,
                            pointHoverRadius: 7
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    fontFamily: "'Open Sans', sans-serif",
                                    fontSize: 12,
                                    stepSize: 200
                                },
                                grid: {
                                    color: 'rgba(0, 0, 0, 0.05)'
                                }
                            },
                            x: {
                                ticks: {
                                    fontFamily: "'Open Sans', sans-serif",
                                    fontSize: 12
                                },
                                grid: {
                                    display: false
                                }
                            }
                        },
                        plugins: {
                            legend: {
                                display: false
                            }
                        }
                    }
                });
            });
        } else {
            console.warn('Chart.js not loaded — skipping Monthly Earnings chart initialization');
        }
    }
}

/* ========================================
   UTILITY FUNCTIONS
   ======================================== */

/**
 * Generate thumbnail asynchronously without blocking the UI
 * Calls the server in the background - doesn't wait for response
 */
function generateThumbnailAsync(materialId) {
    return fetch('api/generate-thumbnail-async.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'material_id=' + encodeURIComponent(materialId),
        credentials: 'same-origin'
    }).then(r => r.json())
    .then(data => {
        if (data.success) {
            console.log('Thumbnail generated successfully for material ID:', materialId);
        } else {
            console.warn('Failed to generate thumbnail for material ID:', materialId, data.message);
        }
        return data;
    })
    .catch(err => {
        console.error('Error generating thumbnail for material ID:', materialId, err);
        // Not critical - continue even if thumbnail generation fails
    });
}

// Format currency
function formatCurrency(amount) {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD'
    }).format(amount);
}

// Format date
function formatDate(date) {
    return new Intl.DateTimeFormat('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric'
    }).format(new Date(date));
}

// Log analytics event
function logAnalytics(eventName, eventData) {
    console.log(`Analytics Event: ${eventName}`, eventData);
    // Send to analytics service (e.g., Google Analytics, Mixpanel)
}

/* ========================================
   DYNAMIC RECENT UPLOADS HELPERS
   ======================================== */

/**
 * Add a new recent upload item into the UI and wire its action buttons.
 */
function addRecentUpload({name, timeAgo, price, fileUrl, mime}) {
    const list = document.querySelector('.recent-uploads-list');
    if (!list) return;

    const item = document.createElement('div');
    item.className = 'recent-upload-item';
    item.innerHTML = `
        <div class="item-info">
            <h5>${escapeHtml(name)}</h5>
            <small>${escapeHtml(timeAgo)}</small>
            <p class="item-price"><img src="assets/images/coin.png" alt="coins"> ${escapeHtml(price)}</p>
        </div>
        <div class="item-actions">
            <a href="#" class="action-btn delete-btn" title="Delete"><i class="fas fa-trash"></i></a>
        </div>
    `;

    // Prepend to list
    list.prepend(item);

    // Wire delete button for the newly added item
    const deleteBtn = item.querySelector('.delete-btn');

    // Delete removes the element and revokes object URL (use custom confirm)
    deleteBtn.addEventListener('click', function(e) {
        e.preventDefault();
        showConfirm('Are you sure you want to delete this file?', function(){
            item.remove();
            try { URL.revokeObjectURL(fileUrl); } catch (err) {}
            showToast('File Deleted Successfully', 'success');
        });
    });
}

function escapeHtml(unsafe) {
    return (unsafe || '').toString()
         .replace(/&/g, "&amp;")
         .replace(/</g, "&lt;")
         .replace(/>/g, "&gt;")
         .replace(/\"/g, "&quot;")
         .replace(/'/g, "&#039;");
}

/* ========================================
   EXPORT FOR TESTING
   ======================================== */

/* ========================================
   SERVER-SYNCED UPLOADS (load, render, delete)
   ======================================== */

function loadUploads(retryCount = 0) {
    const maxRetries = 3;
    const timestamp = Date.now();
    console.log('loadUploads called at:', timestamp, 'retry:', retryCount);
    
    // Use cache-busting parameter to ensure fresh data
    fetch('api/get-user-uploads.php?t=' + timestamp, { 
        method: 'POST', 
        credentials: 'same-origin',
        headers: {
            'Cache-Control': 'no-cache, no-store, must-revalidate',
            'Pragma': 'no-cache'
        }
    })
    .then(r => {
        console.log('get-user-uploads response status:', r.status);
        return r.json();
    })
    .then(data => {
        console.log('loadUploads data received:', data);
        if (data.success) {
            const uploads = data.uploads || [];
            console.log('Rendering', uploads.length, 'uploads');
            renderRecentUploads(uploads);
            renderAllUploads(uploads);
            const totalEl = document.getElementById('totalUploadsCount');
            if (totalEl) {
                totalEl.textContent = uploads.length;
                console.log('Updated total uploads count to:', uploads.length);
            }
            const statTotal = document.getElementById('statTotalUploads');
            if (statTotal) {
                statTotal.textContent = uploads.length;
                console.log('Updated stat total uploads to:', uploads.length);
            }
        } else {
            console.error('Failed loading uploads', data.message);
            // Retry if we got an error and haven't exceeded max retries
            if (retryCount < maxRetries) {
                console.log('Retrying loadUploads...');
                setTimeout(() => loadUploads(retryCount + 1), 200);
            }
        }
    }).catch(err => {
        console.error('Load uploads error:', err);
        // Retry on network error
        if (retryCount < maxRetries) {
            console.log('Retrying loadUploads due to network error...');
            setTimeout(() => loadUploads(retryCount + 1), 200);
        }
    });
}

function renderRecentUploads(uploads) {
    const list = document.querySelector('.recent-uploads-list');
    console.log('=== renderRecentUploads DEBUG ===');
    console.log('Selector: .recent-uploads-list');
    console.log('Container element:', list);
    console.log('Container HTML before:', list ? list.outerHTML.substring(0, 100) : 'NULL');
    console.log('Upload data:', uploads);
    
    if (!list) {
        console.error('CRITICAL: Recent uploads list container NOT FOUND in DOM!');
        return;
    }
    
    list.innerHTML = '';
    if (!uploads || uploads.length === 0) {
        console.log('No uploads to render in recent uploads');
        return;
    }
    
    console.log('Rendering', uploads.length, 'uploads (showing first 3)');
    const items = uploads.slice(0, 3);
    items.forEach((u, idx) => {
        console.log('Rendering upload item', idx + 1, ':', u.title);
        const div = document.createElement('div');
        div.className = 'recent-upload-item d-flex align-items-center justify-content-between';
        const relativeTime = getRelativeTime(u.created_at);
        div.innerHTML = `
            <div class="d-flex align-items-center">
                <div class="me-3 item-icon"><i class="fas fa-file"></i></div>
                <div>
                    <h5>${escapeHtml(u.title)}</h5>
                    <small>${escapeHtml(relativeTime)}</small>
                    <p class="item-price"><img src="assets/images/coin.png" alt="coins"> ${escapeHtml(formatPriceNoDecimals(u.price))}</p>
                </div>
            </div>
            <div class="item-actions">
                <a href="${escapeHtml(u.file_path)}" target="_blank" class="action-btn view-btn" title="View" data-title="${escapeHtml(u.title)}"><i class="fas fa-eye"></i></a>
                <a href="#" class="action-btn delete-btn" data-id="${u.id}" title="Delete"><i class="fas fa-trash"></i></a>
            </div>
        `;
        list.appendChild(div);
    });
    
    console.log('Container HTML after:', list.outerHTML.substring(0, 200));

    // wire delete handlers (use custom confirm modal)
    list.querySelectorAll('.delete-btn').forEach(btn => {
        btn.addEventListener('click', function(e){
            e.preventDefault();
            const id = this.dataset.id;
            showConfirm('Delete this upload?', function(){ deleteUpload(id); });
        });
    });
    console.log('=== renderRecentUploads COMPLETE ===\n');
}

function loadSalesEarnings() {
    console.log('loadSalesEarnings called');
    fetch('api/get-user-sales-earnings.php', { 
        method: 'POST', 
        credentials: 'same-origin'
    })
    .then(r => {
        if (!r.ok) {
            console.error('API response not OK:', r.status, r.statusText);
            throw new Error(`HTTP ${r.status}: ${r.statusText}`);
        }
        return r.json();
    })
    .then(data => {
        console.log('Sales/Earnings data received:', data);
        if (data.success) {
            const totalSalesEl = document.getElementById('statTotalSales');
            const totalEarningsEl = document.getElementById('statTotalEarnings');
            
            if (totalSalesEl) {
                totalSalesEl.textContent = data.total_sales || 0;
                console.log('Updated total sales to:', data.total_sales);
            }
            if (totalEarningsEl) {
                totalEarningsEl.textContent = data.total_earnings || 0;
                console.log('Updated total earnings to:', data.total_earnings);
            }
        } else {
            console.error('Failed loading sales/earnings:', data.message);
            // Set defaults to 0 on error
            const totalSalesEl = document.getElementById('statTotalSales');
            const totalEarningsEl = document.getElementById('statTotalEarnings');
            if (totalSalesEl) totalSalesEl.textContent = 0;
            if (totalEarningsEl) totalEarningsEl.textContent = 0;
        }
    })
    .catch(err => {
        console.error('Sales/Earnings fetch error:', err);
        // Set defaults to 0 on error
        const totalSalesEl = document.getElementById('statTotalSales');
        const totalEarningsEl = document.getElementById('statTotalEarnings');
        if (totalSalesEl) totalSalesEl.textContent = 0;
        if (totalEarningsEl) totalEarningsEl.textContent = 0;
    });
}

function refreshCharts() {
    console.log('Refreshing charts...');
    initializeCharts();
}

function renderAllUploads(uploads) {
    const container = document.getElementById('allUploadsContainer');
    console.log('renderAllUploads called with:', uploads);
    console.log('All uploads container found:', !!container);
    if (!container) {
        console.error('All uploads container not found!');
        return;
    }
    container.innerHTML = '';
    if (!uploads || uploads.length === 0) {
        console.log('No uploads to render in all uploads');
        container.innerHTML = '<p class="text-center text-muted" style="padding: 40px 20px;">No uploads yet. Start by uploading your first material!</p>';
        return;
    }
    console.log('Rendering', uploads.length, 'uploads in all uploads modal');
    uploads.forEach((u, idx) => {
        console.log('Rendering all upload item', idx + 1, ':', u.title);
        const row = document.createElement('div');
        row.className = 'recent-upload-item d-flex align-items-center justify-content-between';
        row.innerHTML = `
            <div class="d-flex align-items-center">
                <div class="me-3 item-icon"><i class="fas fa-file"></i></div>
                <div>
                    <h5>${escapeHtml(u.title)}</h5>
                    <small>${escapeHtml((new Date(u.created_at)).toLocaleString())}</small>
                    <p class="item-price"><img src="assets/images/coin.png" alt="coins"> ${escapeHtml(formatPriceNoDecimals(u.price))}</p>
                </div>
            </div>
            <div class="item-actions">
                <a href="${escapeHtml(u.file_path)}" target="_blank" class="action-btn view-btn" title="View" data-title="${escapeHtml(u.title)}"><i class="fas fa-eye"></i></a>
                <button class="action-btn delete-btn" data-id="${u.id}" title="Delete"><i class="fas fa-trash"></i></button>
            </div>
        `;
        container.appendChild(row);
    });

    container.querySelectorAll('.delete-btn').forEach(btn => {
        btn.addEventListener('click', function(){
            const id = this.dataset.id;
            showConfirm('Delete this upload?', function(){ deleteUpload(id); });
        });
    });
}

function deleteUpload(id, cb) {
    console.log('deleteUpload called with id:', id, 'type:', typeof id);
    const fd = new FormData(); 
    const materialId = parseInt(id, 10);
    console.log('Parsed material_id:', materialId);
    fd.append('material_id', materialId);
    fetch('api/delete-upload.php', { method: 'POST', body: fd, credentials: 'same-origin' })
    .then(r => {
        console.log('Delete response status:', r.status);
        return r.json();
    })
    .then(data => {
        console.log('Delete response data:', data);
        if (data.success) {
            // Show centered modal success message
            showDeleteSuccessModal();
            // Reload uploads to reflect deletion
            setTimeout(() => {
                loadUploads();
                loadSalesEarnings();
                refreshCharts();
            }, 500);
            // Call callback if provided (for additional cleanup)
            if (cb && typeof cb === 'function') cb();
        } else {
            showToast('Delete failed: ' + (data.message||'unknown'), 'error');
        }
    }).catch(err => { 
        console.error('Delete error:', err); 
        showToast('Delete request failed', 'error'); 
    });
}

/**
 * Show centered modal success message for delete
 */
function showDeleteSuccessModal() {
    // Use a modal for center-of-page message
    const html = `
        <div class="modal fade" id="deleteSuccessModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-sm">
                <div class="modal-content">
                    <div class="modal-body text-center">
                        <i class="fas fa-check-circle" style="color: #80c157; font-size: 3rem; margin-bottom: 15px;"></i>
                        <h5 style="margin-top: 15px;">Deleted Successfully</h5>
                        <p class="text-muted">Your file has been removed.</p>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Create and show modal
    let modal = document.getElementById('deleteSuccessModal');
    if (modal) modal.remove();
    
    document.body.insertAdjacentHTML('beforeend', html);
    modal = document.getElementById('deleteSuccessModal');
    const bsModal = new bootstrap.Modal(modal);
    bsModal.show();
    
    // Auto-hide after 2 seconds
    setTimeout(() => {
        bsModal.hide();
        setTimeout(() => modal.remove(), 300);
    }, 2000);
}

function formatPriceNoDecimals(val) {
    const n = Number(val);
    if (isNaN(n)) return '0';
    return Math.round(n).toString();
}

// Get relative time string like "Just now", "2 hours ago", "3 days ago"
function getRelativeTime(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const secondsAgo = Math.floor((now - date) / 1000);
    
    if (secondsAgo < 60) return 'Just now';
    
    const minutesAgo = Math.floor(secondsAgo / 60);
    if (minutesAgo < 60) return minutesAgo + ' minute' + (minutesAgo > 1 ? 's' : '') + ' ago';
    
    const hoursAgo = Math.floor(minutesAgo / 60);
    if (hoursAgo < 24) return hoursAgo + ' hour' + (hoursAgo > 1 ? 's' : '') + ' ago';
    
    const daysAgo = Math.floor(hoursAgo / 24);
    if (daysAgo < 7) return daysAgo + ' day' + (daysAgo > 1 ? 's' : '') + ' ago';
    
    const weeksAgo = Math.floor(daysAgo / 7);
    if (weeksAgo < 4) return weeksAgo + ' week' + (weeksAgo > 1 ? 's' : '') + ' ago';
    
    const monthsAgo = Math.floor(daysAgo / 30);
    if (monthsAgo < 12) return monthsAgo + ' month' + (monthsAgo > 1 ? 's' : '') + ' ago';
    
    const yearsAgo = Math.floor(monthsAgo / 12);
    return yearsAgo + ' year' + (yearsAgo > 1 ? 's' : '') + ' ago';
}

// Load category options from server and set default to 'Notes'
function loadCategories(){
    const select = document.getElementById('categorySelect');
    if (!select) return;
    fetch('api/get-categories.php', { credentials: 'same-origin' })
    .then(r => r.json())
    .then(data => {
        // Always use this specific order: Notes, Videos, Exam Papers, Others
        const categories = ['Notes', 'Videos', 'Exam Papers', 'Others'];
        // Populate hidden select
        select.innerHTML = '';
        categories.forEach(name => {
            const opt = document.createElement('option');
            opt.value = name;
            opt.textContent = name;
            select.appendChild(opt);
        });
        // Populate visible custom dropdown menu if present
        const menu = document.querySelector('.custom-dropdown-menu');
        const button = document.getElementById('categoryDropdownButton');
        if (menu) {
            menu.innerHTML = '';
            categories.forEach(name => {
                const div = document.createElement('div');
                div.className = 'custom-dropdown-option';
                div.setAttribute('data-value', name);
                div.setAttribute('tabindex', '0');
                div.textContent = name;
                menu.appendChild(div);
            });
        }
        // Default to Notes (always the first one)
        select.value = 'Notes';
        if (button) button.textContent = 'Notes';
        // Initialize custom dropdown interactivity now that menu exists
        if (typeof initializeCustomDropdown === 'function') initializeCustomDropdown();
        // Trigger submit state update after categories loaded
        updateSubmitState();
    }).catch(err => {
        console.error('Failed to load categories', err);
        const categories = ['Notes', 'Videos', 'Exam Papers', 'Others'];
        select.innerHTML = '';
        categories.forEach(name => {
            const opt = document.createElement('option');
            opt.value = name;
            opt.textContent = name;
            select.appendChild(opt);
        });
        select.value = 'Notes';
        const menu = document.querySelector('.custom-dropdown-menu');
        const button = document.getElementById('categoryDropdownButton');
        if (menu) {
            menu.innerHTML = '';
            categories.forEach(name => {
                const div = document.createElement('div');
                div.className = 'custom-dropdown-option';
                div.setAttribute('data-value', name);
                div.setAttribute('tabindex', '0');
                div.textContent = name;
                menu.appendChild(div);
            });
        }
        if (button) button.textContent = 'Notes';
        if (typeof initializeCustomDropdown === 'function') initializeCustomDropdown();
        updateSubmitState();
    });
}


// Make functions available for testing if needed
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        handleFileSelect,
        clearFileInput,
        getFileIcon,
        formatFileSize,
        formatCurrency,
        formatDate
    };
}
