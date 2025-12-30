// Loads `navBar.html` into the page placeholder and ensures `assets/js/navBar.js` runs after insertion.
(function () {
  'use strict';

  var NAV_PATH = 'navBar.html'; // relative path to the navbar fragment
  var NAV_SCRIPT = 'assets/js/navBar.js';

  function insertNav(html) {
    var placeholder = document.getElementById('nav-placeholder');
    if (!placeholder) return;
    placeholder.innerHTML = html;
  }

  function loadNavScriptAndInit() {
    // If navBar.js is already present, just trigger scroll so it can initialize
    var existing = document.querySelector('script[src="' + NAV_SCRIPT + '"]');
    if (existing) {
      if (window.jQuery) { jQuery(window).trigger('scroll'); }
      return;
    }

    var s = document.createElement('script');
    s.src = NAV_SCRIPT;
    s.defer = true;
    s.onload = function () {
      if (window.jQuery) { jQuery(window).trigger('scroll'); }
    };
    s.onerror = function () {
      console.error('Failed to load', NAV_SCRIPT);
    };
    document.body.appendChild(s);
  }

  function fetchAndInsert() {
    fetch(NAV_PATH).then(function (resp) {
      if (!resp.ok) throw new Error('navBar.html not found (' + resp.status + ')');
      return resp.text();
    }).then(function (html) {
      insertNav(html);
      loadNavScriptAndInit();
    }).catch(function (err) {
      // Try root-relative path as a fallback for nested pages
      if (NAV_PATH.charAt(0) !== '/') {
        fetch('/' + NAV_PATH).then(function (resp) {
          if (!resp.ok) throw err;
          return resp.text();
        }).then(function (html) {
          insertNav(html);
          loadNavScriptAndInit();
        }).catch(function (err2) {
          console.error('Could not load navBar.html (tried both relative and root):', err, err2);
        });
      } else {
        console.error('Could not load navBar.html:', err);
      }
    });
  }

  // Run after DOM is ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', fetchAndInsert);
  } else {
    fetchAndInsert();
  }

})();
