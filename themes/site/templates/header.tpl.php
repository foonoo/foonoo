<?php if(isset($site_title) && $site_title->u() != ""): ?>
<div id="banner-container">
    <div id="banner-wrapper" class="<?= $has_toc ? "has-toc" : "" ?>">
        <header id="banner">
            <span id="site-title"><?= $site_title ?></span>
        </header>
        <?php if(!empty($site_menu->u())): ?>
            <nav id="main-navigation">
                <ul><?php foreach($site_menu as $menu_item): ?><li><a href="<?= $menu_item['url'] ?>"><?= $menu_item["title"] ?></a></li><?php endforeach; ?></ul>
            </nav>
            <svg id="menu-hamburger" class="hamburger" width="32px" height="32px" viewBox="0 0 512 512" xmlns="http://www.w3.org/2000/svg">
                <path fill="#000" d="M32 96v64h448V96H32zm0 128v64h448v-64H32zm0 128v64h448v-64H32z"/>
            </svg>
            <div id="menu-modal" class="modal"></div>        
        <?php endif; ?>
        
    </div>            
</div>
<?php endif ?>
