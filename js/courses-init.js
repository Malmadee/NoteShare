// Pagination for courses grid (3 items per row, 6 items per page)
$(document).ready(function(){
    const itemsPerPage = 6;
    const coursesPerRow = 3;
    let currentPage = 1;
    
    // Get all course items
    const courseItems = $('#courses-grid .col-lg-4');
    const totalPages = Math.ceil(courseItems.length / itemsPerPage);
    
    // Hide all items initially
    courseItems.hide();
    
    // Show items for the current page
    function showPage(page) {
        courseItems.hide();
        const start = (page - 1) * itemsPerPage;
        const end = start + itemsPerPage;
        courseItems.slice(start, end).show();
        updatePagination(page);
    }
    
    // Update pagination controls
    function updatePagination(page) {
        const paginationContainer = $('#pagination-controls');
        paginationContainer.empty();
        
        // Previous button
        const prevBtn = $('<li class="page-item' + (page === 1 ? ' disabled' : '') + '"><a class="page-link" href="#">&laquo;</a></li>');
        if (page > 1) {
            prevBtn.click(function(e) {
                e.preventDefault();
                currentPage--;
                showPage(currentPage);
            });
        }
        paginationContainer.append(prevBtn);
        
        // Page number buttons
        for (let i = 1; i <= totalPages; i++) {
            const pageBtn = $('<li class="page-item' + (i === page ? ' active' : '') + '"><a class="page-link" href="#">' + i + '</a></li>');
            pageBtn.click(function(e) {
                e.preventDefault();
                currentPage = i;
                showPage(currentPage);
            });
            paginationContainer.append(pageBtn);
        }
        
        // Next button
        const nextBtn = $('<li class="page-item' + (page === totalPages ? ' disabled' : '') + '"><a class="page-link" href="#">&raquo;</a></li>');
        if (page < totalPages) {
            nextBtn.click(function(e) {
                e.preventDefault();
                currentPage++;
                showPage(currentPage);
            });
        }
        paginationContainer.append(nextBtn);
    }
    
    // Show first page on load
    if (totalPages > 0) {
        showPage(1);
    }
});

