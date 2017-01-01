<?php

/**
 * List of each file to upgrade individualy in themes of Omeka S.
 *
 * For Simple Pages.
 */

$prepend = <<<'OUTPUT'
<?php
$this->htmlElement('body')->appendAttribute('class', 'page simple-page');
$nav = $site->publicNav();
$container = $nav->getContainer();
$activePage = $nav->findActive($container);
?>

OUTPUT;

$replace = <<<'OUTPUT'
?>
<?php if ($activePage): ?>
    <?php if ($this->displayNavigation && $activePage['page']->hasPages()): ?>
    <nav class="sub-menu"><?php echo $nav->menu()->renderSubMenu(); ?></nav>
    <?php endif; ?>
<?php endif; ?>

<?php $this->trigger('view.show.before'); ?>
<?php echo $this->content; // $this->shortcodes($this->content); ?>
<?php $this->trigger('view.show.after'); ?>
<?php echo $this->sitePagePagination(); ?>
<?php

OUTPUT;

return array(
    'view/omeka/site/page/show.phtml' => array(
        'prepend' => $prepend,
        'preg_replace' => array(
            '~(' . preg_quote('$text = $this->upgrade()->metadata(') . '.*?page.*?\)\;' . ')~' => '// \1',
        ),
        'replace' => array(
            'echo $this->upgrade()->fallback(\'shortcodes\', $text);' => $replace,
        ),
    ),
);
