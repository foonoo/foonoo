const sideToc = document.getElementById('side-toc');
const modalBackgrounds = document.querySelectorAll(".modal");
const tocHamburger = document.getElementById('toc-hamburger');
const menuHamburger = document.getElementById('menu-hamburger');
const topMenu = document.getElementById('main-navigation');
const currentModal = {menu: null, button: null, modalBackground: null};


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
    tocHamburger.addEventListener('click', toggleMenu(sideToc, document.getElementById('side-toc-modal')));
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
