/* force-pills.js
   Ensures browse filter pills use white background by applying inline styles
   with !important at runtime. This is a last-resort fix to override late-loaded
   stylesheets or runtime scripts that re-style the pills.
*/
(function(){
  function forcePills(){
    try{
      var els = document.querySelectorAll('.container-fluid.fruite .nav-pills .nav-link');
      if(!els || els.length === 0){
        console.info('force-pills: no pill elements found');
        return;
      }
      els.forEach(function(el){
        el.style.setProperty('background', '#ffffff', 'important');
        el.style.setProperty('background-color', '#ffffff', 'important');
        el.style.setProperty('border', '2px solid #ffffff', 'important');
        el.style.setProperty('color', '#46924B', 'important');
      });
      console.info('force-pills: applied white styles to', els.length, 'elements');
    }catch(e){
      console.error('force-pills error', e);
    }
  }

  if(document.readyState === 'loading'){
    document.addEventListener('DOMContentLoaded', function(){
      forcePills();
      setTimeout(forcePills, 500);
    });
  } else {
    forcePills();
    setTimeout(forcePills, 500);
  }
})();
