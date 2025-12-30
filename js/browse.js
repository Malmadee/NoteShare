// Consolidated Main JavaScript
(function ($) {
    "use strict";

    // Config - Dynamically determine base path
    const basePath = window.location.pathname.includes('/NoteShare/') ? '/NoteShare' : '';
    const API_LIST = basePath + '/api/get-uploads.php';
    const API_GET = basePath + '/api/get-upload.php';
    const PER_PAGE = 9;

    // state
    let state = {
        page: 1,
        per_page: PER_PAGE,
        q: '',
        category: null,  // Start with null (no category active/all categories shown)
        sort: 'default',
        total: 0
    };

    // Utility: build tile HTML element
    function buildTile(item) {
        const col = document.createElement('div');
        col.className = 'col-lg-4 col-md-6 pb-4';

        const a = document.createElement('a');
        a.className = 'courses-list-item position-relative d-block overflow-hidden mb-2';
        a.href = '#';
        a.dataset.id = item.id;
        
        // Add click handler to open file details modal
        a.addEventListener('click', (e) => {
            e.preventDefault();
            if (window.fileDetailsModal) {
                window.fileDetailsModal.openFile(item);
            }
        });

        const spanCat = document.createElement('span');
        spanCat.className = 'badge-category';
        spanCat.textContent = item.category || 'Other';

        const spanPrice = document.createElement('span');
        spanPrice.className = 'badge-price';
        spanPrice.innerHTML = '<img src="assets/images/coin.png" class="badge-coin" alt="coin">' + Math.round(item.price || 0);

        // Create file type box instead of image
        const fileBox = document.createElement('div');
        fileBox.className = 'file-type-box';
        
        // Detect file type from title or file_path
        let fileType = 'pdf'; // default
        const fileExt = (item.file_path || item.title || '').toLowerCase();
        
        if (fileExt.includes('.pdf')) {
            fileType = 'pdf';
        } else if (fileExt.includes('.ppt') || fileExt.includes('.pptx')) {
            fileType = 'pptx';
        } else if (fileExt.includes('.doc') || fileExt.includes('.docx')) {
            fileType = 'docx';
        } else if (fileExt.includes('.xls') || fileExt.includes('.xlsx')) {
            fileType = 'xlsx';
        } else if (fileExt.includes('.mp4') || fileExt.includes('.avi') || fileExt.includes('.mov') || fileExt.includes('.webm')) {
            fileType = 'video';
        } else if (fileExt.includes('.jpg') || fileExt.includes('.jpeg') || fileExt.includes('.png') || fileExt.includes('.gif')) {
            fileType = 'image';
        } else if (fileExt.includes('.zip') || fileExt.includes('.rar') || fileExt.includes('.7z')) {
            fileType = 'archive';
        }
        
        fileBox.setAttribute('data-file-type', fileType);
        
        const fileIcon = document.createElement('img');
        fileIcon.className = 'file-icon';
        
        const iconMap = {
            pdf: 'assets/images/file-pdf.svg',
            pptx: 'assets/images/file-pptx.svg',
            docx: 'assets/images/file-docx.svg',
            xlsx: 'assets/images/file-xlsx.svg',
            video: 'assets/images/file-video.svg',
            image: 'assets/images/file-image.svg',
            archive: 'assets/images/file-archive.svg'
        };
        
        fileIcon.src = iconMap[fileType] || iconMap.pdf;
        fileIcon.alt = fileType.toUpperCase();
        fileBox.appendChild(fileIcon);
        
        const fileLabel = document.createElement('div');
        fileLabel.className = 'file-label';
        fileLabel.textContent = fileType.toUpperCase();
        fileBox.appendChild(fileLabel);

        const txt = document.createElement('div');
        txt.className = 'courses-text';
        const h4 = document.createElement('h4');
        h4.className = 'text-white px-3';
        h4.textContent = item.title;
        txt.appendChild(h4);

        a.appendChild(spanCat);
        a.appendChild(spanPrice);
        a.appendChild(fileBox);
        a.appendChild(txt);

        col.appendChild(a);
        return col;
    }

    // Render page of items
    function render(items) {
        const grid1 = document.getElementById('courses-grid-1');
        const grid2 = document.getElementById('courses-grid-2');
        const grid3 = document.getElementById('courses-grid-3');
        const grid4 = document.getElementById('courses-grid-4');
        // clear all grids and render into the active tab only for performance
        [grid1, grid2, grid3, grid4].forEach(g => { if (g) g.innerHTML = ''; });

        // Determine which tab to render to based on category slug mapping
        let target = grid1; // default
        if (state.category === 'videos') target = grid2;
        else if (state.category === 'exam-papers') target = grid3;
        else if (state.category === 'others') target = grid4;

        items.forEach(item => {
            target.appendChild(buildTile(item));
        });

        // (Old modal preview logic removed. Only fileDetailsModal is used now.)
    }

    // Switch to the appropriate tab based on category
    function switchTab() {
        let tabId = 'tab-1'; // default (notes or all)
        if (state.category === 'videos') tabId = 'tab-2';
        else if (state.category === 'exam-papers') tabId = 'tab-3';
        else if (state.category === 'others') tabId = 'tab-4';
        
        // Remove active class from all tabs
        document.querySelectorAll('.tab-pane').forEach(tab => {
            tab.classList.remove('show', 'active');
        });
        
        // Add active class to the selected tab
        const selectedTab = document.getElementById(tabId);
        if (selectedTab) {
            selectedTab.classList.add('show', 'active');
        }
    }

    // Pagination render
    function renderPagination() {
        const controls = document.getElementById('pagination-controls');
        if (!controls) return;
        const totalPages = Math.max(1, Math.ceil(state.total / state.per_page));
        controls.innerHTML = '';

        const makePageItem = (p, label, active, disabled)=>{
            const li = document.createElement('li');
            li.className = 'page-item' + (active ? ' active' : '') + (disabled ? ' disabled' : '');
            const a = document.createElement('a');
            a.className = 'page-link';
            a.href = '#';
            a.textContent = label;
            if (!disabled) {
                a.addEventListener('click', function(e){ 
                    e.preventDefault(); 
                    if (p !== state.page) { 
                        state.page = p; 
                        loadUploads();
                        // Scroll to show filters with some padding above
                        setTimeout(function() {
                            const filterSection = document.querySelector('.fruite-search-section');
                            if (filterSection) {
                                const offsetTop = filterSection.offsetTop - 150;
                                window.scrollTo({ top: offsetTop, behavior: 'smooth' });
                            }
                        }, 100);
                    } 
                });
            }
            li.appendChild(a);
            return li;
        };

        // prev
        controls.appendChild(makePageItem(Math.max(1, state.page-1), '«', false, state.page === 1));
        
        // current page only
        controls.appendChild(makePageItem(state.page, String(state.page), true, false));
        
        // next
        controls.appendChild(makePageItem(Math.min(totalPages, state.page+1), '»', false, state.page === totalPages));
    }

    // Load uploads from server
    function loadUploads() {
        const params = new URLSearchParams();
        params.set('page', state.page);
        params.set('per_page', state.per_page);
        if (state.q) params.set('q', state.q);
        // Only send category parameter if it's not null (active category set)
        if (state.category !== null) params.set('category', state.category);
        if (state.sort) params.set('sort', state.sort);

        const url = API_LIST + '?' + params.toString();
        console.log('Loading uploads from:', url);
        
        fetch(url)
            .then(r => {
                console.log('API response status:', r.status);
                return r.json();
            })
            .then(data => {
                console.log('API data received:', data);
                if (data && !data.error) {
                    state.total = data.total || 0;
                    console.log('Total items:', state.total, 'Items:', data.items);
                    render(data.items || []);
                    renderPagination();
                    
                    // Update pill active states based on current category
                    updatePillStates();
                    
                    // Switch to the appropriate tab based on category
                    switchTab();
                } else {
                    console.error('API error:', data?.error);
                }
            }).catch(err => {
                console.error('Failed to load uploads', err);
            });
    }

    // Update pill active states based on current category state
    function updatePillStates() {
        document.querySelectorAll('.nav-pills .nav-link').forEach(function(pill){
            const name = pill.textContent.trim().toLowerCase();
            let pillCategory = '';
            if (name === 'notes') pillCategory = 'notes';
            else if (name === 'videos') pillCategory = 'videos';
            else if (name === 'exam papers') pillCategory = 'exam-papers';
            else pillCategory = 'others';
            
            // Add active class only if this pill matches the current category AND category is not null
            if (state.category !== null && pillCategory === state.category) {
                pill.classList.add('active');
            } else {
                pill.classList.remove('active');
            }
        });
    }

    // Hook up filters
    document.addEventListener('DOMContentLoaded', function(){
        // Search box and button
        const searchInput = document.querySelector('.fruite-search-section input[type="search"]');
        const searchBtn = document.querySelector('.fruite-search-section button');
        if (searchBtn && searchInput) {
            searchBtn.addEventListener('click', function(e){
                e.preventDefault();
                state.q = (searchInput.value || '').trim();
                state.page = 1;
                loadUploads();
            });
        }

        // category pills
        document.querySelectorAll('.nav-pills .nav-link').forEach(function(el){
            el.addEventListener('click', function(e){
                e.preventDefault();
                e.stopPropagation();
                e.stopImmediatePropagation();
                
                const name = el.textContent.trim().toLowerCase();
                let pillCategory = '';
                if (name === 'notes') pillCategory = 'notes';
                else if (name === 'videos') pillCategory = 'videos';
                else if (name === 'exam papers') pillCategory = 'exam-papers';
                else pillCategory = 'others';
                
                console.log('Pill clicked:', name, 'Category:', pillCategory, 'Current state category:', state.category);
                
                // If clicking a pill that's already active, deactivate it (toggle off)
                if (state.category === pillCategory) {
                    console.log('Toggling off category - showing all');
                    state.category = null;  // Reset to null (all categories)
                } else {
                    // Otherwise, set this category as active (only one pill active at a time)
                    console.log('Setting category to:', pillCategory);
                    state.category = pillCategory;
                }
                
                state.page = 1;
                loadUploads();
            }, true); // Use capture phase to intercept before Bootstrap handles it
        });

        // sort dropdown - use event delegation on the menu itself
        const dropdownMenu = document.querySelector('.dropdown-menu');
        if (dropdownMenu) {
            dropdownMenu.addEventListener('click', function(e) {
                const item = e.target.closest('.dropdown-item');
                if (item) {
                    e.preventDefault();
                    e.stopPropagation();
                    const txt = item.textContent.trim();
                    const txtLower = txt.toLowerCase();
                    state.sort = txtLower;
                    state.page = 1;
                    // Update the button to show selected sort value
                    const sortValueSpan = document.querySelector('.btn-sort-by .sort-value');
                    if (sortValueSpan) {
                        sortValueSpan.textContent = txt;
                    }
                    loadUploads();
                }
            }, true); // capture phase
        }

        // initial load - start with all categories
        loadUploads();
    });

    // Keep existing UI niceties (dropdown sizing / topbar shadow behavior)
    $('.dropdown').on('show.bs.dropdown', function () {
        var $dropdown = $(this);
        var $btn = $dropdown.find('.btn-sort-by');
        var $sortValue = $btn.find('.sort-value');
        var $menu = $dropdown.find('.dropdown-menu');
        if ($sortValue.length && $menu.length) {
            var w = Math.ceil($sortValue.outerWidth()) + 36;
            $menu.css({ 'width': w + 'px', 'min-width': w + 'px' });
            $menu.addClass('dropdown-menu-end');
        }
    });
    $('.dropdown').on('hidden.bs.dropdown', function () { $(this).find('.dropdown-menu').css({ 'width': '', 'min-width': '' }); });
    
    // Throttled scroll handler for topbar
    var scrollTimeout;
    var lastScrollTop = 0;
    $(window).scroll(function () { 
        var currentScroll = $(this).scrollTop();
        
        if (scrollTimeout) clearTimeout(scrollTimeout);
        
        scrollTimeout = setTimeout(function() {
            if ($(window).width() < 992) { 
                if (currentScroll > 55) { 
                    $('.fixed-top').addClass('shadow'); 
                } else { 
                    $('.fixed-top').removeClass('shadow'); 
                } 
            } else { 
                if (currentScroll > 55) { 
                    $('.fixed-top').addClass('shadow').css('top', -50); 
                } else { 
                    $('.fixed-top').removeClass('shadow').css('top', 0); 
                } 
            }
            lastScrollTop = currentScroll;
        }, 10);
    });

})(jQuery);

