// PDF Thumbnail Generator - generates thumbnails on client-side
// Uses PDF.js library

(function() {
    // Load PDF.js library
    const PDFJS_VERSION = '3.11.174';
    const pdfScriptUrl = `https://cdnjs.cloudflare.com/ajax/libs/pdf.js/${PDFJS_VERSION}/pdf.min.js`;
    const pdfWorkerUrl = `https://cdnjs.cloudflare.com/ajax/libs/pdf.js/${PDFJS_VERSION}/pdf.worker.min.js`;

    let pdfLibLoaded = false;
    let pdfLibLoading = false;
    const pdfLibPromise = new Promise((resolve, reject) => {
        function loadPDF() {
            if (pdfLibLoaded) {
                resolve();
                return;
            }
            if (pdfLibLoading) {
                // Wait for existing load
                const checkInterval = setInterval(() => {
                    if (pdfLibLoaded) {
                        clearInterval(checkInterval);
                        resolve();
                    }
                }, 100);
                return;
            }
            
            pdfLibLoading = true;
            const script = document.createElement('script');
            script.src = pdfScriptUrl;
            script.onload = () => {
                window.pdfjsLib.GlobalWorkerOptions.workerSrc = pdfWorkerUrl;
                pdfLibLoaded = true;
                resolve();
            };
            script.onerror = () => {
                pdfLibLoading = false;
                reject(new Error('Failed to load PDF.js'));
            };
            document.head.appendChild(script);
        }
        loadPDF();
    });

    // Generate thumbnail for a PDF file
    window.generatePDFThumbnail = async function(fileUrl, elementId) {
        try {
            console.log('Generating PDF thumbnail for:', fileUrl, 'Element:', elementId);
            
            // Ensure PDF.js is loaded
            await pdfLibPromise;

            // Load PDF with proper fetch options
            const pdf = await window.pdfjsLib.getDocument({
                url: fileUrl,
                withCredentials: false
            }).promise;
            
            console.log('PDF loaded, pages:', pdf.numPages);
            
            // Get first page
            const page = await pdf.getPage(1);
            
            // Set up canvas
            const scale = 1.5;
            const viewport = page.getViewport({ scale });
            const canvas = document.createElement('canvas');
            const context = canvas.getContext('2d');
            
            canvas.height = viewport.height;
            canvas.width = viewport.width;

            // Render page to canvas
            await page.render({
                canvasContext: context,
                viewport: viewport
            }).promise;

            // Convert to blob and create URL
            return new Promise((resolve) => {
                canvas.toBlob(function(blob) {
                    const url = URL.createObjectURL(blob);
                    
                    // Set image if element provided
                    if (elementId) {
                        const img = document.getElementById(elementId);
                        if (img) {
                            console.log('Setting image src for:', elementId);
                            img.src = url;
                        }
                    }
                    
                    resolve(url);
                }, 'image/jpeg', 0.85);
            });
        } catch (error) {
            console.error('PDF thumbnail generation failed:', error);
            return null;
        }
    };

    // Generate thumbnail for video file (extract first frame)
    window.generateVideoThumbnail = function(videoUrl, elementId) {
        return new Promise((resolve) => {
            console.log('Generating video thumbnail for:', videoUrl);
            
            const video = document.createElement('video');
            video.crossOrigin = 'anonymous';
            video.src = videoUrl;
            video.currentTime = 1;
            
            video.addEventListener('loadeddata', () => {
                console.log('Video loaded, generating thumbnail');
                try {
                    const canvas = document.createElement('canvas');
                    canvas.width = video.videoWidth || 400;
                    canvas.height = video.videoHeight || 300;
                    const ctx = canvas.getContext('2d');
                    ctx.drawImage(video, 0, 0);
                    
                    canvas.toBlob(function(blob) {
                        const url = URL.createObjectURL(blob);
                        
                        if (elementId) {
                            const img = document.getElementById(elementId);
                            if (img) {
                                console.log('Setting video thumbnail for:', elementId);
                                img.src = url;
                            }
                        }
                        
                        resolve(url);
                    }, 'image/jpeg', 0.85);
                } catch (e) {
                    console.error('Error drawing video frame:', e);
                    resolve(null);
                }
            }, { once: true });
            
            video.addEventListener('error', (e) => {
                console.error('Video load error:', e);
                resolve(null);
            }, { once: true });
            
            video.addEventListener('abort', () => {
                console.warn('Video load aborted');
                resolve(null);
            }, { once: true });
        });
    };
})();
