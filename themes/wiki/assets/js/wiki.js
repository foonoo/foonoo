document.addEventListener(
  'DOMContentLoaded',
  function(){
    var height = document.getElementById('header').offsetHeight;
    document.getElementById('side').style.height = (window.outerHeight - height) + 'px';
    document.getElementById('menu-wrapper').style.height = (window.outerHeight - height) + 'px';
    document.getElementById('side').style.top = height + 'px';
  }
);
  