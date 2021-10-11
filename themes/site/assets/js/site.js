function resizeSideMenu() {
     
    // Automatically adjust the height of the side menu
    // @todo find a way to prevent this from running when menu is hidden
    
    let height = window.innerHeight || document.body.clientHeight;
    height = height - document.getElementById('banner-wrapper').clientHeight;
    document.getElementById('side-toc').style.height = height + 'px';
    document.getElementById('side-toc-modal').style.height = height + 'px';
}

function toggleSideMenu() {
    document.getElementById("side-toc").classList.toggle("active");
    document.getElementById('side-toc-modal').classList.toggle("active");
}

window.addEventListener('load', resizeSideMenu);
window.addEventListener('resize', resizeSideMenu);

document.getElementById('hamburger').addEventListener('click', toggleSideMenu);
document.getElementById('side-toc-modal').addEventListener('click', toggleSideMenu);

