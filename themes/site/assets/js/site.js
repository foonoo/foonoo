let sideToc = document.getElementById('side-toc');
let modal = document.getElementById('side-toc-modal');
let hamburger = document.getElementById('hamburger');

function resizeSideToc() {
     
    // Automatically adjust the height of the side menu
    // @todo find a way to prevent this from running when menu is hidden
    
    let height = window.innerHeight || document.body.clientHeight;
    height = height - document.getElementById('banner-wrapper').clientHeight;
    sideToc.style.height = height + 'px';
    modal.style.height = height + 'px';
}

function toggleSideToc() {
    sideToc.classList.toggle("active");
    modal.classList.toggle("active");
    hamburger.classList.toggle('active');
}

if(sideToc !== null) {
    window.addEventListener('load', resizeSideToc);
    window.addEventListener('resize', resizeSideToc);

    hamburger.addEventListener('click', toggleSideToc);
    modal.addEventListener('click', toggleSideToc);    
}

