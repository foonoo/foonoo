let sideToc = document.getElementById('side-toc');
let modalBackgrounds = document.querySelectorAll(".modal");
let tocHamburger = document.getElementById('toc-hamburger');
let menuHamburger = document.getElementById('menu-hamburger');
let topMenu = document.getElementById('main-navigation');
let currentModal = {menu: null, button: null, modalBackground: null};

function resizeSideToc() {
     
    // Automatically adjust the height of the side menu
    // @todo find a way to prevent this from running when menu is hidden
    
    let height = window.innerHeight || document.body.clientHeight;
    height = height - document.getElementById('banner-wrapper').clientHeight;
    sideToc.style.height = height + 'px';
    modalBackgrounds.forEach(x => x.style.height = height + 'px');
}

//function toggleSideToc() {
//    sideToc.classList.toggle("active");
//    modalBackground.classList.toggle("active");
//    tocHamburger.classList.toggle('active');
//}

function toggleMenu(menu, modalBackground) {
    return function(e) {
        menu.classList.toggle("active");
        e.target.classList.toggle('active');
        modalBackground.classList.toggle('active');
        if(currentModal.menu !== null && currentModal.menu !== menu) {
            currentModal.menu.classList.toggle('active');
            currentModal.button.classList.toggle('active');
            currentModal.modalBackground.classList.toggle('active');
        }
        currentModal = {menu: menu, button: e.target, modalBackground: modalBackground};
    }
}

if(sideToc !== null) {
    window.addEventListener('load', resizeSideToc);
    window.addEventListener('resize', resizeSideToc);
    tocHamburger.addEventListener('click', toggleMenu(sideToc, document.getElementById('side-toc-modal')));//toggleSideToc);
}

if(topMenu !== null) {
    menuHamburger.addEventListener('click', toggleMenu(topMenu, document.getElementById('menu-modal')));
}

modalBackgrounds.forEach(x => x.addEventListener('click', e => {
        currentModal.button.classList.toggle("active");
        currentModal.menu.classList.toggle("active");
        e.target.classList.toggle("active");
        currentModal = {menu: null, button: null, modalBackground: null}
    })
);    
