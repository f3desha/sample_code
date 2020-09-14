<div class="wrapper">
    <header class="header">
        <div class="container">
            <div class="header__wrap clearfix">
                <div class="header__logo">
                    <a href="https://carvoy.com/" target="_blank">
                        <img src="https://carvoy.com/statics/web/images/logo-black.png" alt="logo" />
                    </a>
                </div>
                <?=$data['header']?>
            </div>
        </div>
    </header>
    <main class="content">
        <div class="container">
            <div class="content__listing clearfix">
                <div class="content__column">
					<?=$data['left_block']?>
                </div>
                <div class="content__column second">
                    <?=$data['right_block']?>
                </div>
            </div>
        </div>
    </main>
    <footer class="footer">
        <div class="container">
            <?=$data['footer']?>
        </div>
    </footer>
</div>
