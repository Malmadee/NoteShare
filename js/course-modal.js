document.addEventListener('DOMContentLoaded', function() {
  // expose a global function so dynamic tiles can open the modal
  window.openCourseModal = function openCourseModal(data) {
    var backdrop = document.getElementById('courseModalBackdrop');
    if (!backdrop) return;
    // Populate fields
    document.getElementById('cm-title').textContent = data.title || '';
    // show category label using actual course category
    document.getElementById('cm-category').textContent = 'Category: ' + (data.category || 'Notes');
    // show creator label
    document.getElementById('cm-creator').textContent = 'Creator: ' + (data.creator || 'Unknown');
    // format price: remove trailing .00 and do not show currency symbol
    var p = parseFloat(data.price) || 0;
    var priceText = (p % 1 === 0) ? String(Math.round(p)) : String(p);
    document.getElementById('cm-price').textContent = priceText;
    document.getElementById('cm-description').textContent = data.description || '';

    // expose current upload id for add-to-cart
    try { window._ns_current_upload_id = data.id || null; } catch(e){}

    // populate left content (notes pages or video or image)
    var left = document.getElementById('cm-left');
      if (left) {
      left.innerHTML = '';
      // Build pages array: prefer explicit pages; otherwise, create a placeholder pages array from the image
      var pagesArr = (data.pages && data.pages.length) ? data.pages.slice() : [];
      if (!pagesArr.length && data.thumbnail) {
        pagesArr = [data.thumbnail];
      }
      // priority: pages array (if any), then video, then image
      if (pagesArr && pagesArr.length) {
        // Build a simplified viewer: left nav, single A4-aspect preview, right nav.
        var total = pagesArr.length;
        // Determine how many pages should be visible before lock, per user rules
        var visibleCount = 1;
        if (data.pages_count <= 5) visibleCount = 1;
        else if (data.pages_count <= 10) visibleCount = 2;
        else if (data.pages_count <= 15) visibleCount = 3;
        else visibleCount = 5;

        var viewer = document.createElement('div'); viewer.className = 'cm-pages';

        var navPrev = document.createElement('button');
        navPrev.className = 'cm-nav-btn cm-nav-prev';
        navPrev.setAttribute('aria-label','Previous page');
        navPrev.innerHTML = '&lt;';

        var navNext = document.createElement('button');
        navNext.className = 'cm-nav-btn cm-nav-next';
        navNext.setAttribute('aria-label','Next page');
        navNext.innerHTML = '&gt;';

        var large = document.createElement('div'); large.className = 'cm-page-large';
        var largeImg = document.createElement('img'); largeImg.className = 'cm-large-img'; largeImg.src = pagesArr[0] || '';
        largeImg.alt = 'Course preview page';
        large.appendChild(largeImg);

        var lockOverlay = document.createElement('div'); lockOverlay.className = 'cm-page-lock';
        lockOverlay.innerHTML = '<div class="lock-inner"><i class="fa fa-lock"></i></div>';
        large.appendChild(lockOverlay);

        viewer.appendChild(navPrev);
        viewer.appendChild(large);
        viewer.appendChild(navNext);
        left.appendChild(viewer);

        // navigation state
        var currentIndex = 0;

        function updatePreview() {
          largeImg.src = pagesArr[currentIndex] || '';
          // pages with index >= visibleCount are locked (0-based index)
          if (currentIndex >= visibleCount) lockOverlay.classList.add('show');
          else lockOverlay.classList.remove('show');
          // enable/disable nav
          navPrev.disabled = (currentIndex === 0);
          navNext.disabled = (currentIndex === total - 1);
        }

        navPrev.addEventListener('click', function(){ if (currentIndex > 0) { currentIndex--; updatePreview(); } });
        navNext.addEventListener('click', function(){ if (currentIndex < total - 1) { currentIndex++; updatePreview(); } });

        // allow clicking preview image to advance if unlocked
        largeImg.addEventListener('click', function(){
          if (currentIndex < visibleCount - 1 && currentIndex < total -1) {
            currentIndex++; updatePreview();
          }
        });

        // sizing: keep handled in CSS via aspect-ratio; ensure initial state
        largeImg.addEventListener('load', function(){
          updatePreview();
        });
        updatePreview();
      } else if (data.video) {
        var wrap = document.createElement('div'); wrap.className = 'cm-video-wrap';
        var vid = document.createElement('video'); vid.src = data.video; vid.controls = true; vid.playsInline = true; vid.preload = 'metadata';
        // show only first minute - lock after 60s
        var lockOverlay = document.createElement('div'); lockOverlay.className = 'cm-video-lock';
        lockOverlay.innerHTML = '<div class="lock-icon"><i class="fas fa-lock"></i></div><div class="lock-text">Locked - full video requires purchase</div>';
        wrap.appendChild(vid);
        wrap.appendChild(lockOverlay);
        left.appendChild(wrap);
        // attach timeupdate to lock at 60s
        vid.addEventListener('timeupdate', function(){
          if (vid.currentTime >= 60) {
            vid.pause();
            lockOverlay.classList.add('show');
            vid.controls = false;
          }
        });
        // ensure preview starts at 0
        vid.currentTime = 0;
      } else {
        // fallback to image
        var imwrap = document.createElement('div'); imwrap.className = 'cm-page-large';
        var img = document.createElement('img'); img.id = 'cm-image'; img.src = data.image || '';
        imwrap.appendChild(img);
        left.appendChild(imwrap);
      }
    }

    // ensure Add button has dataset id for cart
    var addBtnLocal = document.getElementById('cm-add-to-cart');
    if (addBtnLocal) addBtnLocal.dataset.uploadId = data.id || '';

    // show
    backdrop.classList.add('open');
    document.body.style.overflow = 'hidden';
    document.body.classList.add('modal-blur');
  }

  function closeCourseModal() {
    var backdrop = document.getElementById('courseModalBackdrop');
    if (!backdrop) return;
    // Pause any playing video inside the modal before closing
    try {
      var left = document.getElementById('cm-left');
      if (left) {
        var vids = left.querySelectorAll('video');
        vids.forEach(function(v){
          try { v.pause(); v.currentTime = 0; v.removeAttribute('src'); v.load(); } catch(e) { console.warn('Error stopping course modal video', e); }
        });
      }
    } catch(e) { console.warn('Error cleaning up modal videos', e); }

    backdrop.classList.remove('open');
    document.body.style.overflow = '';
    document.body.classList.remove('modal-blur');
  }

  // existing static elements may call openCourseModal; dynamic tiles call window.openCourseModal directly

  // Normalize displayed price text near the coin image (if present)
  document.querySelectorAll('.courses-list-item').forEach(function(el){
    var txt = el.querySelector('.courses-text');
    if (!txt) return;
    var coinImg = txt.querySelector('img.coin-icon');
    if (coinImg) {
      var parent = coinImg.parentNode;
      var raw = parent.textContent || '';
      var num = (raw||'').replace(/[^0-9\.]/g,'');
      var n = parseFloat(num) || 0;
      var priceStr = (n % 1 === 0) ? String(Math.round(n)) : String(n);
      parent.innerHTML = '<img src="assets/images/coin.png" alt="coin" class="coin-icon">' + ' ' + priceStr;
    }
  });

  // Close handlers
  document.addEventListener('click', function(e) {
    var backdrop = document.getElementById('courseModalBackdrop');
    if (!backdrop) return;
    if (e.target.matches('#courseModalBackdrop') || e.target.closest('.cm-close')) {
      closeCourseModal();
    }
  });

  // Add-to-cart (placeholder)
  var addBtn = document.getElementById('cm-add-to-cart');
  if (addBtn) {
    addBtn.addEventListener('click', function(e){
      e.preventDefault();
      // Add to cart using cart API (localStorage)
      var title = document.getElementById('cm-title').textContent || 'Item';
      var price = parseFloat(document.getElementById('cm-price').textContent) || 0;
      var idEl = document.getElementById('cm-title');
      // try to get upload id from data attribute on backdrop (set by openCourseModal)
      var uploadId = addBtn.dataset.uploadId || window._ns_current_upload_id || null;
      if (window.nsAddToCart) {
        window.nsAddToCart({ id: uploadId, title: title, price: price });
      }
      closeCourseModal();
    });
  }
});
