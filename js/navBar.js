// Signal that the script file was fetched (helps diagnose caching / load issues)
if (window && window.console && window.console.log) {
    console.log('navBar.js fetched');
}

(function () {
    'use strict';

    // Helper: add/remove class on element
    function _hasClass(el, cls) { return el && el.classList && el.classList.contains(cls); }

    // Topbar behavior: use jQuery if available, otherwise use DOM APIs (guarded)
    if (typeof window.jQuery === 'function') {
        var $ = window.jQuery;
        $(window).on('scroll resize load', function () {
            var topbar = $('.topbar');
            var fixed = $('.fixed-top');
            var topbarHeight = 55;
            if (topbar.length) topbarHeight = Math.round(topbar.outerHeight());

            var scrollTop = $(this).scrollTop();
            var windowWidth = $(window).width();

            if (windowWidth < 992) {
                if (scrollTop > topbarHeight) fixed.addClass('shadow'); else fixed.removeClass('shadow');
            } else {
                if (scrollTop > topbarHeight) fixed.addClass('shadow').css('top', -topbarHeight + 'px'); else fixed.removeClass('shadow').css('top', '0');
            }
        });
    } else {
        // fallback: minimal topbar hide using DOM
        function domTopbarHandler() {
            try {
                var topbar = document.querySelector('.topbar');
                var fixed = document.querySelector('.fixed-top');
                if (!topbar || !fixed) return;
                var topbarHeight = Math.max(55, Math.round(topbar.getBoundingClientRect().height));
                var scrollTop = window.pageYOffset || document.documentElement.scrollTop;
                var windowWidth = window.innerWidth || document.documentElement.clientWidth;
                if (windowWidth < 992) {
                    if (scrollTop > topbarHeight) fixed.classList.add('shadow'); else fixed.classList.remove('shadow');
                } else {
                    if (scrollTop > topbarHeight) { fixed.classList.add('shadow'); fixed.style.top = (-topbarHeight) + 'px'; }
                    else { fixed.classList.remove('shadow'); fixed.style.top = '0'; }
                }
            } catch (e) { /* ignore */ }
        }
        window.addEventListener('scroll', domTopbarHandler);
        window.addEventListener('resize', domTopbarHandler);
        window.addEventListener('load', domTopbarHandler);
    }

    // Plain-JS implementation of spinner/back-to-top injection (no jQuery)
    function injectSiteUI() {
        try {
            // don't inject twice
            if (document.getElementById('siteSpinner') || document.getElementById('backToTop')) return;

            var css = `
/* Page UI: spinner & back-to-top */
.site-spinner-overlay {
    position: fixed;
    inset: 0;
    background: rgba(255, 255, 255, 0.95);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 10500;
    opacity: 0;
    visibility: hidden;
    pointer-events: none;
}

.site-spinner-overlay.visible {
    opacity: 1;
    visibility: visible;
    pointer-events: auto;
}

.site-spinner {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 10px;
}

.spinner-dots {
    display: flex;
    gap: 6px;
    align-items: center;
    height: 24px;
}

.spinner-dot {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: #80c157;
    animation: bounce 1.4s infinite ease-in-out both;
}

.spinner-dot:nth-child(1) {
    animation-delay: -0.32s;
}

.spinner-dot:nth-child(2) {
    animation-delay: -0.16s;
}

@keyframes bounce {
    0%, 80%, 100% {
        transform: scale(0);
        opacity: 0.5;
    }
    40% {
        transform: scale(1);
        opacity: 1;
    }
}

.spinner-ring {
    display: none;
}

.spinner-text {
    display: none;
    font-weight: 700;
    color: rgba(0, 0, 0, 0.65);
}

.back-to-top {
    position: fixed;
    right: 18px;
    bottom: 22px;
    width: 44px;
    height: 44px;
    border-radius: 50%;
    background: #80c157;
    color: #fff;
    border: none;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 6px 18px rgba(0, 0, 0, 0.12);
    opacity: 0;
    visibility: hidden;
    transform: translateY(8px);
    transition: opacity 0.2s, transform 0.2s;
    z-index: 10500;
    cursor: pointer;
}

.back-to-top.visible {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}

.back-to-top:hover {
    transform: translateY(-4px);
    background: #6ba645;
}

.back-to-top i {
    font-size: 1rem;
}
`;

            var style = document.createElement('style');
            style.type = 'text/css';
            style.appendChild(document.createTextNode(css));
            document.head.appendChild(style);

            // spinner element
            var spinner = document.createElement('div');
            spinner.id = 'siteSpinner';
            spinner.className = 'site-spinner-overlay';
            spinner.setAttribute('aria-hidden', 'true');
            spinner.innerHTML = '<div class="site-spinner">'
                + '<div class="spinner-dots" role="img" aria-label="Loading">'
                + '<div class="spinner-dot"></div>'
                + '<div class="spinner-dot"></div>'
                + '<div class="spinner-dot"></div>'
                + '</div>'
                + '<div class="spinner-text">Loading...</div>'
                + '</div>';

            // back to top
            var back = document.createElement('button');
            back.id = 'backToTop';
            back.className = 'back-to-top';
            back.setAttribute('aria-label', 'Back to top');
            back.innerHTML = '<i class="fa fa-chevron-up" aria-hidden="true"></i>';

            // prepend spinner and append back button
            if (document.body) {
                document.body.insertBefore(spinner, document.body.firstChild);
                document.body.appendChild(back);
            } else {
                document.addEventListener('DOMContentLoaded', function () {
                    document.body.insertBefore(spinner, document.body.firstChild);
                    document.body.appendChild(back);
                });
            }

            // hide spinner immediately on page load (don't show it on initial load)
            spinner.classList.remove('visible');
            
            // show spinner when navigating to new page
            window.addEventListener('beforeunload', function () { 
                spinner.classList.add('visible'); 
            });

            // hide spinner when new page completes loading
            window.addEventListener('load', function () { 
                setTimeout(function() {
                    spinner.classList.remove('visible');
                }, 1200);
            });

            // scroll handler for back-to-top visibility
            window.addEventListener('scroll', function () {
                var st = window.pageYOffset || document.documentElement.scrollTop;
                if (st > 220) back.classList.add('visible'); else back.classList.remove('visible');
            });

            back.addEventListener('click', function (e) { e.preventDefault(); window.scrollTo({ top: 0 }); });

            if (window && window.console && window.console.info) console.info('navBar.js: injectSiteUI executed');
        } catch (err) {
            if (window && window.console && window.console.warn) console.warn('injectSiteUI error', err);
        }
    }

    // expose for diagnostics
    try { window.__ns_injectSiteUI = injectSiteUI; } catch (e) { /* ignore */ }

    // Try to initialize immediately and also on DOMContentLoaded as a fallback
    try { injectSiteUI(); } catch (e) { /* ignore */ }
    document.addEventListener('DOMContentLoaded', function () { try { injectSiteUI(); } catch (e) { /* ignore */ } });

})();
// End of navBar.js

// Global logout handler
function handleLogout() {
    // Call the logout endpoint on the server
    fetch('/NoteShare/authentication/logout.php', {
        method: 'POST',
        credentials: 'include'
    })
    .then(response => response.json())
    .then(data => {
        // Clear any client-side data
        try { localStorage.clear(); } catch (e) { /* ignore */ }
        try { sessionStorage.clear(); } catch (e) { /* ignore */ }
        
        // Redirect to authentication page
        window.location.href = '/NoteShare/authentication.php';
    })
    .catch(error => {
        console.error('Logout error:', error);
        // Still redirect even if API call fails
        window.location.href = '/NoteShare/authentication.php';
    });
}
