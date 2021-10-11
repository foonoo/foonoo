let sideToc = document.getElementById('side-toc');
let modal = document.getElementById('side-toc-modal');
let hamburger = document.getElementById('hamburger');

function resizeSideMenu() {
     
    // Automatically adjust the height of the side menu
    // @todo find a way to prevent this from running when menu is hidden
    
    let height = window.innerHeight || document.body.clientHeight;
    height = height - document.getElementById('banner-wrapper').clientHeight;
    sideToc.style.height = height + 'px';
    modal.style.height = height + 'px';
}

function toggleSideMenu() {
    sideToc.classList.toggle("active");
    modal.classList.toggle("active");
    hamburger.classList.toggle('active');
}

window.addEventListener('load', resizeSideMenu);
window.addEventListener('resize', resizeSideMenu);

hamburger.addEventListener('click', toggleSideMenu);
modal.addEventListener('click', toggleSideMenu);

