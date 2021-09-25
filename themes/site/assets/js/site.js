function resizeSideMenu() {
    let height = window.innerHeight || document.body.clientHeight;
    document.getElementById('side-toc').style.height = (height - document.getElementById('banner-wrapper').clientHeight) + 'px';
}

window.addEventListener('load', resizeSideMenu);
window.addEventListener('resize', resizeSideMenu);
