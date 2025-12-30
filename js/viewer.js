// Simple viewer script: reads ?file=...&title=... and sets iframe + document title
(function(){
  function getQueryParam(name){
    const params = new URLSearchParams(window.location.search);
    return params.get(name);
  }

  const file = getQueryParam('file');
  const title = getQueryParam('title') || 'Viewer';

  // Set title in tab and top area
  document.title = title;
  const titleEl = document.getElementById('viewerTitle');
  if (titleEl) titleEl.textContent = title;

  // Set iframe src (sanitized minimally)
  const iframe = document.getElementById('viewerFrame');
  if (iframe && file) {
    try {
      const url = decodeURIComponent(file);
      iframe.src = url;
    } catch(e){
      iframe.src = file;
    }
  } else if (iframe) {
    iframe.srcdoc = '<p style="padding:20px;font-family:Arial">No file specified</p>';
  }
})();
