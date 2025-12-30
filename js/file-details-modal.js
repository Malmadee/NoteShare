// File Details Modal Logic
class FileDetailsModal {
    constructor() {
        this.modal = new bootstrap.Modal(document.getElementById('fileDetailsModal'));
        this.currentFile = null;
        this.currentPageStart = 0;
        this.previewPageCount = 1;
        this.totalPages = 0;
        
        this.setupEventListeners();
    }

    setupEventListeners() {
        const purchaseBtn = document.getElementById('purchaseBtn');
        if (purchaseBtn) {
            purchaseBtn.addEventListener('mouseenter', () => {
                purchaseBtn.style.backgroundColor = '#fcbd40';
            });
            purchaseBtn.addEventListener('mouseleave', () => {
                purchaseBtn.style.backgroundColor = '#81c408';
            });
        }
        document.getElementById('cancelBtn')?.addEventListener('click', () => this.modal.hide());
        purchaseBtn?.addEventListener('click', () => this.purchaseFile());
        
        // Add event listeners for pagination buttons
        const prevBtn = document.getElementById('prevPageBtn');
        const nextBtn = document.getElementById('nextPageBtn');
        if (prevBtn) prevBtn.addEventListener('click', () => this.previousPage());
        if (nextBtn) nextBtn.addEventListener('click', () => this.nextPage());
        
        // Add event listener for close button inside modal
        const closeBtn = document.querySelector('.btn-close-modal');
        if (closeBtn) {
            closeBtn.addEventListener('click', () => this.modal.hide());
        }
        
        // Add event listeners for modal show/hide to manage back-to-top and prevent page shift
        const modalEl = document.getElementById('fileDetailsModal');
        modalEl.addEventListener('show.bs.modal', () => {
            console.log('[Modal] show.bs.modal event fired - hiding scrollbar');
            // Hide back-to-top button when modal opens
            const backToTopBtn = document.getElementById('backToTop');
            if (backToTopBtn) {
                backToTopBtn.classList.remove('visible');
            }
            // Prevent page shift by maintaining scrollbar width
            const scrollbarWidth = window.innerWidth - document.documentElement.clientWidth;
            document.documentElement.style.overflow = 'hidden';
            document.documentElement.style.paddingRight = scrollbarWidth + 'px';
            document.body.style.overflow = 'hidden';
            document.body.style.paddingRight = scrollbarWidth + 'px';
        });
        modalEl.addEventListener('hidden.bs.modal', () => {
            console.log('[Modal] hidden.bs.modal event fired - restoring scrollbar');
            // Restore body styles
            document.documentElement.style.overflow = '';
            document.documentElement.style.paddingRight = '';
            document.body.style.overflow = '';
            document.body.style.paddingRight = '';
            
            // Re-show back-to-top button if scroll position is past threshold
            const backToTopBtn = document.getElementById('backToTop');
            if (backToTopBtn) {
                const st = window.pageYOffset || document.documentElement.scrollTop;
                if (st > 220) {
                    backToTopBtn.classList.add('visible');
                }
            }
            // Pause and cleanup any preview videos inside the modal to stop playback
            try {
                const videos = modalEl.querySelectorAll('video');
                videos.forEach(v => {
                    try {
                        v.pause();
                        v.currentTime = 0;
                        // remove src to free resources (renderPreviewPages will recreate on next open)
                        v.removeAttribute('src');
                        v.load();
                    } catch (e) {
                        console.warn('Error stopping preview video:', e);
                    }
                });
            } catch (e) {
                console.warn('Error cleaning up modal videos:', e);
            }
        });
    }
    
    // Determine how many preview pages to show based on total pages
    getPreviewPageCount(totalPages) {
        if (totalPages <= 5) return totalPages; // Show all pages for short PDFs
        if (totalPages <= 10) return 2;
        if (totalPages <= 15) return 3;
        return 5;
    }

    // Open modal with file details
    async openFile(fileData) {
        console.log('[FileDetailsModal] openFile called with:', fileData);
        this.currentFile = fileData;
        this.currentPageStart = 0;

        if (!fileData.file_path) {
            alert('Error: file_path is missing for this file. Cannot preview.');
            console.error('[FileDetailsModal] file_path is missing:', fileData);
            return;
        }

        // Increment view count
        this.incrementViews(fileData.id);

        // Get total pages. For PPT/PPTX try to discover pre-generated thumbnails first.
        const fileExt = (fileData.file_path || '').toLowerCase().split('.').pop();
        if (fileExt === 'ppt' || fileExt === 'pptx') {
            // Prefer pages_count from DB if available, otherwise probe thumbnails folder
            if (fileData.pages_count && Number(fileData.pages_count) > 0) {
                this.totalPages = Number(fileData.pages_count);
            } else {
                this.totalPages = await this.getPptPageCount(fileData.id);
            }
        } else {
            // PDF / other types
            this.totalPages = await this.getTotalPages(fileData.file_path);
        }
        this.previewPageCount = this.getPreviewPageCount(this.totalPages);

        // Populate file details
        document.getElementById('fileTitle').textContent = fileData.title;
        document.getElementById('modalHeaderTitle').textContent = fileData.title;
        document.getElementById('fileDescription').textContent = fileData.description || 'No description available';
        
        // Populate pills
        document.getElementById('fileCategoryPill').textContent = `Category: ${fileData.category || 'N/A'}`;
        document.getElementById('fileCreatorPill').textContent = `Creator: ${fileData.creator || 'Unknown'}`;
        
        // Format duration/pages
        const ext = (fileData.file_path || '').toLowerCase().split('.').pop();
        const isVideo = ['mp4', 'avi', 'mov', 'webm'].includes(ext);
        let durationText = `Pages: ${this.totalPages}`;
        
        if (isVideo) {
            // For videos, get metadata
            const videoDuration = await this.getVideoDuration(fileData.file_path);
            const hours = Math.floor(videoDuration / 3600);
            const minutes = Math.floor((videoDuration % 3600) / 60);
            const seconds = Math.floor(videoDuration % 60);
            
            if (hours > 0) {
                durationText = `Duration: ${hours}h ${minutes}m`;
            } else if (minutes > 0) {
                durationText = `Duration: ${minutes}m ${seconds}s`;
            } else {
                durationText = `Duration: ${seconds}s`;
            }
        }
        
        document.getElementById('fileDurationPill').textContent = durationText;
        
        document.getElementById('filePrice').textContent = fileData.price || 0;

        // Render preview pages
        this.renderPreviewPages();

        // Show modal
        this.modal.show();
    }

    // Get total pages from PDF
    async getTotalPages(filePath) {
        try {
            console.log('[getTotalPages] Getting page count for:', filePath);
            
            // Try using PDF.js if available
            if (typeof pdfjsLib !== 'undefined') {
                try {
                    const pdf = await pdfjsLib.getDocument(`api/proxy-file.php?file=${encodeURIComponent(filePath)}`).promise;
                    const pageCount = pdf.numPages;
                    console.log('[getTotalPages] PDF.js found', pageCount, 'pages');
                    return pageCount;
                } catch (pdfError) {
                    console.warn('[getTotalPages] PDF.js failed:', pdfError);
                }
            }
            
            // Fallback to PHP API
            const response = await fetch(`api/get-pdf-pages.php?file=${encodeURIComponent(filePath)}`);
            console.log('[getTotalPages] API response status:', response.status);
            
            if (!response.ok) {
                console.error('[getTotalPages] API returned status', response.status);
                return 1;
            }
            
            const data = await response.json();
            console.log('[getTotalPages] API returned:', data);
            return data.pages || 1;
        } catch (e) {
            console.error('[getTotalPages] Error getting page count:', e);
            return 1;
        }
    }

    // Probe thumbnails for PPT/PPTX to determine number of pages (thumbnails must be pre-generated)
    async getPptPageCount(materialId) {
        try {
            const basePath = window.location.pathname.includes('/NoteShare/') ? '/NoteShare' : '';
            let count = 0;
            const maxProbe = 50; // don't probe forever
            for (let i = 1; i <= maxProbe; i++) {
                const url = `${basePath}/uploads/thumbnails/${materialId}_page_${i}.jpg`;
                try {
                    const resp = await fetch(url, { method: 'HEAD', cache: 'no-store' });
                    if (resp.ok) {
                        count = i;
                        continue;
                    } else {
                        // 404 or not found -> stop probing
                        break;
                    }
                } catch (e) {
                    // If HEAD isn't allowed, try GET but don't load body
                    try {
                        const r2 = await fetch(url, { method: 'GET', cache: 'no-store' });
                        if (r2.ok) { count = i; continue; }
                        break;
                    } catch (e2) {
                        break;
                    }
                }
            }
            return Math.max(1, count);
        } catch (e) {
            console.warn('[getPptPageCount] Error probing thumbnails:', e);
            return 1;
        }
    }

    // Get file type from file path
    getFileType(filePath) {
        const ext = filePath.toLowerCase().split('.').pop();
        const typeMap = {
            'pdf': 'PDF Document',
            'pptx': 'PowerPoint Presentation',
            'ppt': 'PowerPoint Presentation',
            'docx': 'Word Document',
            'doc': 'Word Document',
            'xlsx': 'Excel Spreadsheet',
            'xls': 'Excel Spreadsheet',
            'mp4': 'Video',
            'jpg': 'Image',
            'jpeg': 'Image',
            'png': 'Image'
        };
        return typeMap[ext] || ext.toUpperCase();
    }

    // Get video duration
    async getVideoDuration(filePath) {
        return new Promise((resolve) => {
            const video = document.createElement('video');
            video.src = filePath;
            video.onloadedmetadata = () => {
                resolve(video.duration || 0);
            };
            video.onerror = () => {
                console.warn('Failed to load video metadata');
                resolve(0);
            };
        });
    }

    // Increment view count for material
    async incrementViews(materialId) {
        try {
            const basePath = window.location.pathname.includes('/NoteShare/') ? '/NoteShare' : '';
            const response = await fetch(basePath + '/api/increment-views.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ id: materialId })
            });
            const result = await response.json();
            if (result.success) {
                console.log('[incrementViews] View count incremented for material', materialId);
            }
        } catch (error) {
            console.warn('[incrementViews] Error incrementing view count:', error);
        }
    }    // Render preview pages
    async renderPreviewPages() {
        const container = document.getElementById('pagePreviewContainer');
        console.log('[renderPreviewPages] Container found:', !!container);
        if (!container) {
            console.error('[renderPreviewPages] pagePreviewContainer not found in DOM!');
            return;
        }
        container.innerHTML = '';

        // Detect file type
        const filePath = this.currentFile.file_path || '';
        const ext = filePath.toLowerCase().split('.').pop();
        const isPDF = ext === 'pdf';
        const isPpt = ['ppt', 'pptx'].includes(ext);
        const isImage = ['jpg', 'jpeg', 'png', 'gif'].includes(ext);
        const isVideo = ['mp4', 'avi', 'mov', 'webm'].includes(ext);

        if (isPDF) {
            // PDF preview logic
            // Show previewPageCount unlocked pages starting from currentPageStart
            // Then show 1 locked page if there are more pages available
            const unlockPageCount = Math.min(this.previewPageCount, this.totalPages - this.currentPageStart);
            
            // Create a grid container for pages
            const pagesGrid = document.createElement('div');
            pagesGrid.style.display = 'grid';
            pagesGrid.style.gridTemplateColumns = '1fr'; // Always show 1 page per row
            pagesGrid.style.gap = '10px';
            pagesGrid.style.marginBottom = '15px';
            
            // Render unlocked pages
            for (let i = 0; i < unlockPageCount; i++) {
                const pageNum = this.currentPageStart + i + 1;
                
                const pageDiv = document.createElement('div');
                pageDiv.className = 'preview-page mb-3';
                pageDiv.style.position = 'relative';
                pageDiv.style.background = '#f5f5f5';
                pageDiv.style.border = '1px solid #ddd';
                pageDiv.style.borderRadius = '4px';
                pageDiv.style.overflow = 'hidden';
                pageDiv.style.aspectRatio = '210/297'; // A4 ratio
                pageDiv.style.minHeight = '200px';
                
                // Create canvas for page thumbnail
                const canvas = document.createElement('canvas');
                canvas.style.width = '100%';
                canvas.style.height = '100%';
                canvas.style.objectFit = 'contain';
                pageDiv.appendChild(canvas);
                
                // Render actual page
                this.renderPage(canvas, this.currentFile.file_path, pageNum);
                pagesGrid.appendChild(pageDiv);
            }
            
            // If there are more pages, show one locked page as preview
            const nextPageNum = this.currentPageStart + unlockPageCount + 1;
            if (nextPageNum <= this.totalPages) {
                const lockedPageDiv = document.createElement('div');
                lockedPageDiv.className = 'preview-page mb-3';
                lockedPageDiv.style.position = 'relative';
                lockedPageDiv.style.background = '#f5f5f5';
                lockedPageDiv.style.border = '1px solid #ddd';
                lockedPageDiv.style.borderRadius = '4px';
                lockedPageDiv.style.overflow = 'hidden';
                lockedPageDiv.style.aspectRatio = '210/297'; // A4 ratio
                lockedPageDiv.style.minHeight = '200px';
                
                // Create canvas for locked page (blurred)
                const canvas = document.createElement('canvas');
                canvas.style.width = '100%';
                canvas.style.height = '100%';
                canvas.style.objectFit = 'contain';
                canvas.style.filter = 'blur(8px)'; // Blur the page content
                lockedPageDiv.appendChild(canvas);
                
                // Render the page but blurred
                this.renderPage(canvas, this.currentFile.file_path, nextPageNum);
                
                // Show lock overlay
                const lockOverlay = document.createElement('div');
                lockOverlay.className = 'lock-overlay';
                lockOverlay.style.position = 'absolute';
                lockOverlay.style.inset = '0';
                lockOverlay.style.background = 'rgba(0, 0, 0, 0.3)';
                lockOverlay.style.display = 'flex';
                lockOverlay.style.flexDirection = 'column';
                lockOverlay.style.alignItems = 'center';
                lockOverlay.style.justifyContent = 'center';
                lockOverlay.style.color = '#888';
                lockOverlay.innerHTML = `
                    <i class="fas fa-lock" style="font-size: 2rem; margin-bottom: 0.5rem; color: #888;"></i>
                    <p style="text-align: center; margin: 0; font-size: 12px; color: #888;">Purchase to View Full Content</p>
                `;
                lockedPageDiv.appendChild(lockOverlay);
                pagesGrid.appendChild(lockedPageDiv);
            }
            
            container.appendChild(pagesGrid);
            
            // Update page indicator
            const pageIndicator = document.getElementById('pageIndicator');
            if (pageIndicator) {
                pageIndicator.textContent = `Pages ${this.currentPageStart + 1} - ${this.currentPageStart + unlockPageCount} of ${this.totalPages}`;
            }
        } else if (isImage) {
            // Image preview
            const imgDiv = document.createElement('div');
            imgDiv.className = 'preview-page mb-3';
            imgDiv.style.position = 'relative';
            imgDiv.style.background = '#f5f5f5';
            imgDiv.style.border = '1px solid #ddd';
            imgDiv.style.borderRadius = '4px';
            imgDiv.style.overflow = 'hidden';
            imgDiv.style.aspectRatio = '210/297';
            const img = document.createElement('img');
            img.src = filePath;
            img.alt = 'Preview Image';
            img.style.width = '100%';
            img.style.height = '100%';
            img.style.objectFit = 'contain';
            imgDiv.appendChild(img);
            container.appendChild(imgDiv);
            const pageIndicator = document.getElementById('pageIndicator');
            if (pageIndicator) pageIndicator.textContent = 'Image Preview';
            document.getElementById('prevPageBtn').disabled = true;
            document.getElementById('nextPageBtn').disabled = true;
        } else if (isVideo) {
            // Video preview with 1-minute preview limit
            const vidDiv = document.createElement('div');
            vidDiv.className = 'preview-page mb-3';
            vidDiv.style.position = 'relative';
            vidDiv.style.background = '#f5f5f5';
            vidDiv.style.border = '1px solid #ddd';
            vidDiv.style.borderRadius = '4px';
            vidDiv.style.overflow = 'hidden';
            vidDiv.style.aspectRatio = '210/297';
            
            const video = document.createElement('video');
            video.src = filePath;
            video.controls = true;
            video.style.width = '100%';
            video.style.height = '100%';
            video.style.objectFit = 'contain';
            video.id = 'previewVideo_' + Math.random().toString(36).substr(2, 9);
            
            vidDiv.appendChild(video);
            
            // Add lock overlay
            const lockOverlay = document.createElement('div');
            lockOverlay.className = 'lock-overlay';
            lockOverlay.style.position = 'absolute';
            lockOverlay.style.inset = '0';
            lockOverlay.style.background = 'rgba(0, 0, 0, 0.7)';
            lockOverlay.style.display = 'none';
            lockOverlay.style.flexDirection = 'column';
            lockOverlay.style.alignItems = 'center';
            lockOverlay.style.justifyContent = 'center';
            lockOverlay.style.color = 'gray';
            lockOverlay.style.zIndex = '10';
            lockOverlay.innerHTML = `
                <i class="fas fa-lock" style="font-size: 3rem; margin-bottom: 0.5rem; color: #999;"></i>
                <p style="text-align: center; margin: 0; font-size: 14px; color: #999; font-weight: 600;">Purchase to View Full Content</p>
            `;
            vidDiv.appendChild(lockOverlay);
            
            // Setup preview limit (1 minute)
            video.addEventListener('play', () => {
                const checkInterval = setInterval(() => {
                    if (video.currentTime >= 60) {
                        video.pause();
                        video.style.filter = 'blur(8px)';
                        lockOverlay.style.display = 'flex';
                        clearInterval(checkInterval);
                    }
                }, 100);
            });
            
            vidDiv.appendChild(video);
            container.appendChild(vidDiv);
            const pageIndicator2 = document.getElementById('pageIndicator');
            if (pageIndicator2) pageIndicator2.textContent = 'Video Preview (1 min limit)';
            document.getElementById('prevPageBtn').disabled = true;
            document.getElementById('nextPageBtn').disabled = true;
        } else {
            // PPT/PPTX: attempt to show pre-generated thumbnail pages if available
            if (isPpt) {
                const materialId = this.currentFile.id;
                const basePath = window.location.pathname.includes('/NoteShare/') ? '/NoteShare' : '';
                // Show previewPageCount unlocked pages
                const unlockPageCount = Math.min(this.previewPageCount, this.totalPages - this.currentPageStart);
                const pagesGrid = document.createElement('div');
                pagesGrid.style.display = 'grid';
                pagesGrid.style.gridTemplateColumns = '1fr';
                pagesGrid.style.gap = '10px';
                pagesGrid.style.marginBottom = '15px';

                for (let i = 0; i < unlockPageCount; i++) {
                    const pageNum = this.currentPageStart + i + 1;
                    const pageDiv = document.createElement('div');
                    pageDiv.className = 'preview-page mb-3';
                    pageDiv.style.position = 'relative';
                    pageDiv.style.background = '#f5f5f5';
                    pageDiv.style.border = '1px solid #ddd';
                    pageDiv.style.borderRadius = '4px';
                    pageDiv.style.overflow = 'hidden';
                    pageDiv.style.aspectRatio = '210/297';
                    pageDiv.style.minHeight = '200px';

                    const img = document.createElement('img');
                    img.src = `${basePath}/uploads/thumbnails/${materialId}_page_${pageNum}.jpg`;
                    img.alt = `Slide ${pageNum}`;
                    img.style.width = '100%';
                    img.style.height = '100%';
                    img.style.objectFit = 'contain';
                    // If image fails to load, show placeholder
                    img.onerror = () => {
                        img.style.display = 'none';
                        const ph = document.createElement('div');
                        ph.style.width = '100%';
                        ph.style.height = '100%';
                        ph.style.display = 'flex';
                        ph.style.alignItems = 'center';
                        ph.style.justifyContent = 'center';
                        ph.style.color = '#777';
                        ph.textContent = `Slide ${pageNum} not available`;
                        pageDiv.appendChild(ph);
                    };
                    pageDiv.appendChild(img);
                    pagesGrid.appendChild(pageDiv);
                }

                // Locked page if more available
                const nextPageNum = this.currentPageStart + unlockPageCount + 1;
                if (nextPageNum <= this.totalPages) {
                    const lockedPageDiv = document.createElement('div');
                    lockedPageDiv.className = 'preview-page mb-3';
                    lockedPageDiv.style.position = 'relative';
                    lockedPageDiv.style.background = '#f5f5f5';
                    lockedPageDiv.style.border = '1px solid #ddd';
                    lockedPageDiv.style.borderRadius = '4px';
                    lockedPageDiv.style.overflow = 'hidden';
                    lockedPageDiv.style.aspectRatio = '210/297';
                    lockedPageDiv.style.minHeight = '200px';

                    const img = document.createElement('img');
                    img.src = `${basePath}/uploads/thumbnails/${materialId}_page_${nextPageNum}.jpg`;
                    img.alt = `Slide ${nextPageNum}`;
                    img.style.width = '100%';
                    img.style.height = '100%';
                    img.style.objectFit = 'contain';
                    img.style.filter = 'blur(8px)';
                    lockedPageDiv.appendChild(img);

                    const lockOverlay = document.createElement('div');
                    lockOverlay.className = 'lock-overlay';
                    lockOverlay.style.position = 'absolute';
                    lockOverlay.style.inset = '0';
                    lockOverlay.style.background = 'rgba(0, 0, 0, 0.3)';
                    lockOverlay.style.display = 'flex';
                    lockOverlay.style.flexDirection = 'column';
                    lockOverlay.style.alignItems = 'center';
                    lockOverlay.style.justifyContent = 'center';
                    lockOverlay.style.color = '#888';
                    lockOverlay.innerHTML = `
                        <i class="fas fa-lock" style="font-size: 2rem; margin-bottom: 0.5rem; color: #888;"></i>
                        <p style="text-align: center; margin: 0; font-size: 12px; color: #888;">Purchase to View Full Content</p>
                    `;
                    lockedPageDiv.appendChild(lockOverlay);
                    pagesGrid.appendChild(lockedPageDiv);
                }

                container.appendChild(pagesGrid);
                const pageIndicator = document.getElementById('pageIndicator');
                if (pageIndicator) {
                    pageIndicator.textContent = `Slides ${this.currentPageStart + 1} - ${this.currentPageStart + unlockPageCount} of ${this.totalPages}`;
                }
                return;
            }
            // Other file types: show SVG icon
            const iconDiv = document.createElement('div');
            iconDiv.className = 'preview-page mb-3 d-flex flex-column align-items-center justify-content-center';
            iconDiv.style.background = '#f5f5f5';
            iconDiv.style.border = '1px solid #ddd';
            iconDiv.style.borderRadius = '4px';
            iconDiv.style.height = '300px';
            iconDiv.style.justifyContent = 'center';
            iconDiv.style.alignItems = 'center';
            // Map extension to icon
            const iconMap = {
                pptx: 'assets/images/file-pptx.svg',
                docx: 'assets/images/file-docx.svg',
                xlsx: 'assets/images/file-xlsx.svg',
                archive: 'assets/images/file-archive.svg',
                pdf: 'assets/images/file-pdf.svg',
                image: 'assets/images/file-image.svg',
                video: 'assets/images/file-video.svg'
            };
            let iconType = ext;
            if (['ppt', 'pptx'].includes(ext)) iconType = 'pptx';
            else if (['doc', 'docx'].includes(ext)) iconType = 'docx';
            else if (['xls', 'xlsx'].includes(ext)) iconType = 'xlsx';
            else if (['zip', 'rar', '7z'].includes(ext)) iconType = 'archive';
            else if (['jpg', 'jpeg', 'png', 'gif'].includes(ext)) iconType = 'image';
            else if (['mp4', 'avi', 'mov', 'webm'].includes(ext)) iconType = 'video';
            const iconSrc = iconMap[iconType] || iconMap.pdf;
            const iconImg = document.createElement('img');
            iconImg.src = iconSrc;
            iconImg.alt = iconType.toUpperCase();
            iconImg.style.width = '120px';
            iconImg.style.height = '120px';
            iconDiv.appendChild(iconImg);
            // Label
            const label = document.createElement('div');
            label.className = 'file-label mt-2';
            label.textContent = iconType.toUpperCase();
            iconDiv.appendChild(label);
            container.appendChild(iconDiv);
            const pageIndicator3 = document.getElementById('pageIndicator');
            if (pageIndicator3) pageIndicator3.textContent = iconType.toUpperCase() + ' Preview';
            document.getElementById('prevPageBtn').disabled = true;
            document.getElementById('nextPageBtn').disabled = true;
        }
    }

    // Render a specific page using PDF.js
    async renderPage(canvas, filePath, pageNum) {
        try {
            console.log('[renderPage] Rendering page', pageNum, 'from', filePath, 'PDF.js available:', typeof pdfjsLib !== 'undefined');
            // This requires PDF.js library to be loaded
            if (typeof pdfjsLib !== 'undefined') {
                const pdf = await pdfjsLib.getDocument(`api/proxy-file.php?file=${encodeURIComponent(filePath)}`).promise;
                const page = await pdf.getPage(pageNum);
                
                const viewport = page.getViewport({ scale: 1.5 });
                canvas.width = viewport.width;
                canvas.height = viewport.height;
                
                const context = canvas.getContext('2d');
                const renderContext = {
                    canvasContext: context,
                    viewport: viewport
                };
                
                await page.render(renderContext).promise;
            } else {
                // Fallback: show placeholder if PDF.js not available
                canvas.getContext('2d').fillStyle = '#ddd';
                canvas.getContext('2d').fillRect(0, 0, canvas.width, canvas.height);
                canvas.getContext('2d').fillStyle = '#999';
                canvas.getContext('2d').font = '20px Arial';
                canvas.getContext('2d').textAlign = 'center';
                canvas.getContext('2d').fillText('Page ' + pageNum, canvas.width / 2, canvas.height / 2);
            }
        } catch (e) {
            console.error('Error rendering page:', e);
        }
    }

    previousPage() {
        if (this.currentPageStart > 0) {
            this.currentPageStart = Math.max(0, this.currentPageStart - this.previewPageCount);
            this.renderPreviewPages();
        }
    }

    nextPage() {
        const pagesToShow = Math.min(this.previewPageCount, this.totalPages - this.currentPageStart);
        if (this.currentPageStart + pagesToShow < this.totalPages) {
            this.currentPageStart += this.previewPageCount;
            this.renderPreviewPages();
        }
    }

    purchaseFile() {
        if (!this.currentFile) return;
        
        // Add to cart instead of redirecting
        this.addToCart(this.currentFile);
    }
    
    async addToCart(fileData) {
        try {
            const formData = new FormData();
            formData.append('material_id', fileData.id);
            
            const response = await fetch('/NoteShare/api/add-to-cart.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                console.log('[Modal] Item added to cart. Cart count:', data.cart_count);
                this.showToast(`"${fileData.title}" has been added to your cart!`, 'success');
                
                // Broadcast to all pages that cart has been updated
                if (typeof BroadcastChannel !== 'undefined') {
                    const channel = new BroadcastChannel('cart_updates');
                    channel.postMessage({
                        action: 'item_added',
                        material_id: fileData.id,
                        cart_count: data.cart_count
                    });
                    channel.close();
                }
                
                // Close the modal
                this.modal.hide();
            } else {
                this.showToast('Error: ' + (data.message || 'Could not add to cart'), 'error');
            }
        } catch (error) {
            console.error('[Modal] Error adding to cart:', error);
            this.showToast('Error adding to cart', 'error');
        }
    }
    
    showToast(message, type = 'info') {
        // Create toast container if it doesn't exist
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
        
        // Create toast element
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
        
        // Auto-remove after 4 seconds
        setTimeout(() => {
            toast.style.animation = 'slideOut 0.3s ease-out forwards';
            setTimeout(() => toast.remove(), 300);
        }, 4000);
    }
}

// Initialize modal when page loads
document.addEventListener('DOMContentLoaded', () => {
    window.fileDetailsModal = new FileDetailsModal();
});
