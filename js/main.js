// Consolidated Main JavaScript
(function ($) {
    "use strict";

    // Tab switching functionality for category navigation
    $('[data-bs-toggle="pill"]').on('click', function (e) {
        e.preventDefault();
        const tabId = $(this).attr('href');
        
        // Remove active class from all nav items
        $('.nav-item a').removeClass('active');
        
        // Add active class to clicked item
        $(this).addClass('active');
        
        // Hide all tab panes
        $('.tab-pane').removeClass('show active');
        
        // Show selected tab pane
        $(tabId).addClass('show active');
    });

})(jQuery);

