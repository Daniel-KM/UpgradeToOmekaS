<?php

/**
 * List of each file to upgrade individualy in themes of Omeka S.
 *
 * For Core files.
 */

$foot = 'echo $this->upgrade()->foot();';
$upgrade = array();

$replace = <<<'OUTPUT'
foreach ($links as $link) {
    echo $this->hyperlink($link['label'], $link['uri']);
}

OUTPUT;

$upgrade['view/common/admin-bar.phtml'] = array(
    'preg_replace' => array(
        '~(' . preg_quote('echo $this->upgrade()->nav($links, \'public_navigation_admin_bar\');') . ')~' => '// \1' . PHP_EOL . $replace,
    ),
);

$prepend = <<<'OUTPUT'
<?php
// TODO This file can be removed, because this is only a copy of the file "application/view-shared/common/advanced-search.phtml".

?>

OUTPUT;

$upgrade['view/common/advanced-search.phtml'] = array(
    'comment' => true,
    'file' => 'application/view-shared/common/advanced-search.phtml',
    'replace' => array(
        'echo $this->upgrade()->js_tag(\'items-search\');' => '$this->headScript()->appendFile($this->assetUrl(\'js/advanced-search.js\')); ',
    ),
    'prepend' => $prepend,
);

$append = <<<'OUTPUT'
<?php // Adapted from Omeka Classic application/views/scripts/common/output-format-list.php. ?>

<?php if ($output_formats): ?>
    <?php
    $params = $this->params()->fromRoute();
    $base = $this->basePath() . '/api/' . $this->upgrade()->mapModel($params['__CONTROLLER__'], true, 'underscore');
    $isSearch = in_array($params['action'], array('browse', 'search'));
    $totalOutputFormats = count($output_formats);
    ?>
    <?php if ($list): ?>
<ul id="output-format-list">
    <?php foreach ($output_formats as $key => $output_format): ?>
    <li class="<?php echo $key % 2 ? 'odd' : 'even'; ?>">
        <?php
        $url = $base;
        switch ($output_format):
            case 'json-ld':
                $url .= $isSearch ? '?' . http_build_query($query) : '/' . $params['id'];
                break;
        endswitch;
        echo $this->hyperlink($output_format, $url);
        ?>
    </li>
    <?php endforeach; ?>
</ul>
    <?php else: ?>
<p id="output-format-list">
    <?php foreach ($output_formats as $key => $output_format): ?>
        <?php
        $url = $base;
        switch ($output_format):
            case 'json-ld':
                $url .= $isSearch ? '?' . http_build_query($query) : '/' . $params['id'];
                break;
        endswitch;
        echo $this->hyperlink($output_format, $url);
        echo $key != ($totalOutputFormats - 1) ? $delimiter : '';
        ?>
    <?php endforeach; ?>
</p>
    <?php endif; ?>
<?php endif; ?>
OUTPUT;

$upgrade['view/common/output-format-list.phtml'] = array(
    'remove' => true,
    'append' => $append,
);

$append = <<<'OUTPUT'
<?php // Adapted from application/view-shared/common/pagination.phtml (added ul/li). ?>
<?php // Remove this file to use the Omeka S template. ?>

<nav class="pagination pagination-nav" role="navigation" aria-label="<?php echo $this->upgrade()->stranslate('Pagination'); ?>">
<?php if ($totalCount): ?>
    <ul class="pagination">
        <li class="page-input">
    <form method="GET" action="">
        <?php
        $from = $offset + 1;
        $to = ($currentPage < $pageCount) ? $offset + $perPage : $totalCount;
        ?>
        <span class="row-count"><?php echo sprintf('%s–%s of %s', $from, $to, $totalCount); ?></span>
        <?php echo $this->queryToHiddenInputs(['page']); ?>
        <input type="text" name="page" id="page-input-top" value="<?php echo $currentPage; ?>" size="4" <?php echo ($pageCount == 1) ? 'readonly' : ''; ?>>
        <span class="page-count"><?php echo sprintf($this->translate('of %s'), $pageCount); ?></span>
    </form>
        </li>

    <?php if ($currentPage != 1): ?>
        <li class="pagination_previous">
    <a href="<?php echo $this->escapeHtml($previousPageUrl); ?>" class="previous o-icon-prev button" aria-label=" <?php echo $this->escapeHtml($this->translate('Previous')); ?>"></a>
        </li>
    <?php else: ?>
        <li class="pagination_previous">
    <span class="previous o-icon-prev button inactive"></span>
        </li>
    <?php endif; ?>

    <?php if ($currentPage < $pageCount): ?>
        <li class="pagination_next">
    <a href="<?php echo $this->escapeHtml($nextPageUrl); ?>" class="next o-icon-next button" aria-label=" <?php echo $this->escapeHtml($this->translate('Next')); ?>"></a>
        </li>
    <?php else: ?>
        <li class="pagination_next">
    <span class="next o-icon-next button inactive"></span>
        </li>
    <?php endif; ?>
    </ul>
<?php else: ?>
    <span class="row-count no-result"><?php echo $this->translate('0 results'); ?></span>
<?php endif; ?>
</nav>

OUTPUT;

$upgrade['view/common/pagination.phtml'] = array(
    'comment' => true,
    'append' => PHP_EOL . $append,
);

$prepend = <<<'OUTPUT'
<?php
// Remove this file to use the Omeka S template "application/view-shared/common/resource-values.phtml".
// In that case, either replace `all_element_texts($record)` by `$record->displayValues()`
// in item/show.phtml, item-set/show.phtml and media/show.phtml, or set the
// option `'partial' => 'common/resource-values.phtml'` as its second argument.
// The file "resource-values" can be customized: simply copy it in the theme.
//
// Else, this file can be kept and customized too. It uses the properties of the
// resource template if any, so the alternate labels can be used. Other data may
// be useful: the resource class (that corresponds to the item type) and the
// languages.
?>

OUTPUT;

$upgrade['view/common/record-metadata.phtml'] = array(
    'replace' => array(
        'escapeHtml /* TODO the plugin must be loaded. */ (' => 'escapeHtml(',
    ),
    'prepend' => $prepend,
);

$append = <<<'OUTPUT'
<?php // Adapted from application/view-shared/common/search-filters.phtml (added ul/li). ?>
<?php // Remove this file to use the Omeka S template. ?>

<?php $escape = $this->plugin('escapeHtml'); ?>
<?php if (count($filters) > 0): ?>
<div class="search-filters">
<ul>
    <?php foreach ($filters as $filterLabel => $filterValues): ?>
    <li class="filter">
        <span class="filter-label"><?php echo $escape($filterLabel); ?></span>
        <ul>
        <?php foreach ($filterValues as $filterValue): ?>
            <li>
        <span class="filter-value"><?php echo $escape($filterValue); ?></span>
            </li>
        <?php endforeach; ?>
        </ul>
    </li>
    <?php endforeach; ?>
</ul>
</div>
<?php endif; ?>

OUTPUT;

$upgrade['view/common/search-filters.phtml'] = array(
    'comment' => true,
    'append' => PHP_EOL . $append,
);

$append = <<<'OUTPUT'
<?php // Adapted from application/view-admin/layout/header.phtml and application/view-shared/layout/layout.phtml. ?>

<?php $escape = $this->plugin('escapeHtml'); ?>
<?php $showAdvanced = isset($options['show_advanced']) ? $options['show_advanced'] : $this->upgrade()->get_option('use_advanced_search'); ?>
<?php $searchResourceTypes = $this->upgrade()->get_custom_search_record_types(); ?>
<?php if ($showAdvanced && $searchResourceTypes): ?>
<!-- <div id="search"> -->
<div id="search-container">
    <form action="" id="search-form">
        <?php $searchValue = isset($_GET['property'][0]['in'][0]) ? $_GET['property'][0]['in'][0] : ''; ?>
        <input type="text" name="property[0][in][]" value="<?php echo $escape($searchValue); ?>">
        <button type="button" id="advanced"><?php echo $this->translate('Advanced Options'); ?></button>
        <button type="submit"><?php echo $this->translate('Search'); ?></button>
        <fieldset id="advanced-options" style="display: none;">
            <legend><?php echo $this->translate('Resource Type'); ?></legend>
        <?php foreach ($searchResourceTypes as $searchResourceType => $labelResourceType): ?>
            <?php switch ($searchResourceType):
                    case 'Item': ?>
            <input type="radio" name="resource-type" id="search-items" value="item" checked="checked"
                data-input-placeholder="<?php echo $escape($this->translate('Search Items')); ?>"
                data-action="<?php echo $escape($this->url('site/resource', ['controller' => 'item', 'action' => 'browse'], true)); ?>">
            <label for="search-items"><?php echo $this->translate('Items'); ?></label>
                <?php break; ?>
                <?php case 'ItemSet': ?>
            <input type="radio" name="resource-type" id="search-item-sets" value="item-set"
                data-input-placeholder="<?php echo $escape($this->translate('Search Collections')); ?>"
                data-action="<?php echo $escape($this->url('site/resource', ['controller' => 'item-set', 'action' => 'browse'], true)); ?>">
            <label for="search-item-sets"><?php echo $this->translate('Collections'); ?></label>
                <?php break; ?>
                <?php case 'Media': ?>
            <input type="radio" name="resource-type" id="search-media" value="media"
                data-input-placeholder="<?php echo $escape($this->translate('Search Files')); ?>"
                data-action="<?php echo $escape($this->url('site/resource', ['controller' => 'media', 'action' => 'browse'], true)); ?>">
            <label for="search-media"><?php echo $this->translate('Files'); ?></label>
                <?php break; ?>
            <?php /*
                <?php case 'Page': ?>
            <input type="radio" name="resource-type" id="search-page" value="page"
                data-input-placeholder="<?php echo $escape($this->translate('Search Pages')); ?>"
                data-action="<?php echo $escape($this->url('site/resource', ['controller' => 'page', 'action' => 'browse'], true)); ?>">
            <label for="search-page"><?php echo $this->translate('Page'); ?></label>
                <?php break; ?>
            */ ?>
            <?php endswitch; ?>
        <?php endforeach; ?>
        </fieldset>
    </form>
</div>
<?php // TODO Merge the javascript for advanced form. See application/asset/js/global.js. ?>
<script type="text/javascript">
<!--
var OmekaS = {
    updateSearch: function () {
        var checkedOption = $("#advanced-options input[type='radio']:checked ");
        $("#search-form").attr("action", checkedOption.data('action'));
        $("#search-form > input[type='text']").attr("placeholder", checkedOption.data('inputPlaceholder'));
    }
};

(function($, window, document) {
    $(function() {
        $('#search-form').change(OmekaS.updateSearch);
        OmekaS.updateSearch();
    });
}(window.jQuery, window, document));

$('#advanced').click(function(event) {
    event.preventDefault();
    $('#advanced-options').slideToggle('slow');
});
//-->
</script>
<?php else: ?>
<!-- <div id="search"> -->
<div id="search-container">
    <form action="<?php echo $this->escapeHtml($this->url('site/resource', ['controller' => 'item','action' => 'browse'], true)); ?>" id="search-form">
        <?php $searchValue = isset($_GET['property'][0]['in'][0]) ? $_GET['property'][0]['in'][0] : ''; ?>
        <input type="text" name="property[0][in][]" value="<?php echo $escape($searchValue); ?>" placeholder="<?php echo $this->translate('Search items'); ?>">
        <button type="submit"><?php echo $this->translate('Search'); ?></button>
    </form>
</div>
<?php endif; ?>

OUTPUT;

$upgrade['view/common/search-main.phtml'] = array(
    'comment' => true,
    'append' => PHP_EOL . $append,
);

$replace = <<<'OUTPUT'
?>
<pre>
<?php echo $this->exception; ?>
</pre>
<?php

echo $this->upgrade()->foot();

OUTPUT;

$upgrade['view/error/403.phtml'] = array(
    'replace' => array(
        $foot => $replace,
    ),
);

$replace = <<<'OUTPUT'
?>
<?php if (isset($this->message)): ?>
<p><?php echo $this->message; ?></p>
<?php endif; ?>

<?php if (isset($this->reason)): ?>
<p><?php echo sprintf($this->translate('Reason: %s'), "<tt>$this->reason</tt>"); ?></p>
<?php endif; ?>

<?php if ($this->exception): ?>
<pre><?php echo $this->exception; ?></pre>
<?php endif; ?>
<?php

echo $this->upgrade()->foot();

OUTPUT;

$upgrade['view/error/404.phtml'] = array(
    'replace' => array(
        $foot => $replace,
    ),
);

$upgrade['view/error/405.phtml'] = array(
    'replace' => array(
        $foot => $replace,
    ),
);

$replace = <<<'OUTPUT'
<?php
echo $this->form()->openTag($form);
echo $this->formCollection($form, false);
?>
<button><?php echo $this->translate('Activate'); ?></button>
<?php echo $this->form()->closeTag(); ?>

OUTPUT;

$upgrade['view/omeka/login/create-password.phtml'] = array(
    'preg_replace' => array(
        '~\<form.+\</form\>~s' => $replace,
    ),
);

$replace = <<<'OUTPUT'
<?php
echo $this->form()->openTag($form);
echo $this->formCollection($form, false);
?>
<button><?php echo $this->translate('Send password reset email'); ?></button>
<?php echo $this->form()->closeTag(); ?>

OUTPUT;

$upgrade['view/omeka/login/forgot-password.phtml'] = array(
    'preg_replace' => array(
        '~\<form.+\</form\>~s' => $replace,
    ),
);

$upgrade['view/omeka/login/login.phtml'] = array(
    'replace' => array(
        'echo $this->form' => 'echo $this->form($form); // echo $this->form',
    ),
);

$upgrade['view/omeka/site/index/index.phtml'] = array(
    'prepend' => '<?php echo $this->upgrade()->head(); ?>' . PHP_EOL,
    'append' => '<?php echo $this->upgrade()->foot(); ?>' . PHP_EOL,
);

$prepend = <<<'OUTPUT'
<?php
// This template is used for item/browse and item-sets/show (see routes.config.ini).
// The item set metadata are displayed only when the variable is set. To use the
// item-set/show template, load it as a partial and skip the current one.

// TODO Avoid to repeat the query: get the result from the paginator.
$query = $this->params()->fromQuery();
unset($query['page']);
$total_results = $this->api()->search('items', $query)->getTotalResults();
?>

<?php
// Copied from application/view-shared/omeka/site/item/browse.phtml
$escape = $this->plugin('escapeHtml');
$this->htmlElement('body')->appendAttribute('class', 'item resource browse');
$query = $this->params()->fromQuery();
if (isset($itemSet)):
    $this->htmlElement('body')->appendAttribute('class', 'item-set');
    $query['item_set_id'] = $itemSet->id();
endif;
?>

OUTPUT;

$pregReplace = <<<'OUTPUT'
<?php if (isset($itemSet)): ?>
    <?php echo $this->pageTitle($itemSet->displayTitle(), 1); ?>
    <h2><?php echo $this->translate('Item Set'); ?></h2>
    <div class="metadata">
        <?php // echo $itemSet->displayValues(); // TODO Use $itemSet->displayValues(); ?>
        <?php echo $this->upgrade()->all_element_texts($itemSet); ?>
    </div>
    <h2>\1</h2>
<?php else: ?>
    <h1>\1</h1>
<?php endif; ?>
OUTPUT;

$pregReplaceSingle = <<<'OUTPUT'
echo $this->hyperlink($this->translate('Advanced search'), $this->url('site/resource', ['controller' => 'item', 'action' => 'search'], ['query' => $query], true), ['class' => 'advanced-search']);
OUTPUT;

$upgrade['view/omeka/site/item/browse.phtml'] = array(
    'preg_replace_single' => array(
        '~(' . preg_quote('echo $this->pagination(') . ')~' => $pregReplaceSingle . ' \1',
    ),
    'preg_replace' => array(
        '~\<h1\>(.*?)\</h1\>~' => $pregReplace,
        '~\bforeach~' => '$this->trigger(\'view.browse.before\'); foreach',
        '~\bendforeach;~' => 'endforeach; $this->trigger(\'view.browse.after\');',
    ),
    'prepend' => $prepend,
);

$replace = <<<'OUTPUT'
?>
<form id="advanced-search" method="get" action="<?php echo $this->escapeHtml($this->url(null, ['action' => 'browse'], true)); ?>">
<?php
OUTPUT;

$pregReplace = <<<'OUTPUT'
?>
<div id="page-actions">
    <input type="submit" name="submit" value="<?php echo $this->escapeHtml($this->translate('Search')); ?>">
</div>
</form>
<?php
OUTPUT;

$upgrade['view/omeka/site/item/search.phtml'] = array(
    'preg_replace' => array(
        '~(?:\'|")formAttributes(?:\'|")~' => '\'query\' => $this->params()->fromQuery(), \'resourceType\' => \'item\', \'formAttributes\'',
        '~(' . preg_quote('echo $this->upgrade()->foot(') . ')~' => $pregReplace . ' \1',
    ),
    'replace' => array(
        'echo $this->partial(' => $replace . ' echo $this->partial(',
        'items/search-form.php' => 'common/advanced-search',
        '<?php ?>' => '',
    ),
    'prepend' => '<?php $this->headScript()->appendFile($this->assetUrl(\'js/advanced-search.js\', \'Omeka\')); /* NOTE The old js is not used. */ ?>',
);

$upgrade['view/omeka/site/item/show.phtml'] = array(
    'preg_replace' => array(
        '~\b(' . preg_quote('echo $this->upgrade()->all_element_texts') . ')~' => '$this->trigger(\'view.show.before\'); \1',
        '~(' . preg_quote('$this->upgrade()->fire_plugin_hook(\'public_items_show\'') . '.*?\)\;)~' => '$this->trigger(\'view.show.after\');',
    ),
);

$prepend = <<<'OUTPUT'
<?php
// The equivalent of the collections/show is the item/browse template, where the
// metadata of the item set can be displayed when it is set.
// This template is used mainly for the result of the search on item sets.

// TODO Avoid to repeat the query: get the result from the paginator.
$query = $this->params()->fromQuery();
unset($query['page']);
$total_results = $this->api()->search('item-sets', $query)->getTotalResults();
?>

OUTPUT;

$replace = <<<'OUTPUT'
echo $this->searchFilters(); echo $this->hyperlink($this->translate('Advanced search'), $this->url(null, ['action' => 'search'], true), ['class' => 'advanced-search']);
OUTPUT;

$upgrade['view/omeka/site/item-set/browse.phtml'] = array(
    'preg_replace_single' => array(
        '~(' . preg_quote('echo $this->pagination(') . ')~' => $replace . ' \1',
    ),
    'preg_replace' => array(
        '~\bforeach~' => '$this->trigger(\'view.browse.before\'); foreach',
        '~\bendforeach;~' => 'endforeach; $this->trigger(\'view.browse.after\');',
    ),
    'prepend' => $prepend,
);

$prepend = <<<'OUTPUT'
<?php
// NOTE In Omeka S, there is no template to show the properties of an item set:
// it uses the item/browse template instead.

?>

OUTPUT;

$upgrade['view/omeka/site/item-set/show.phtml'] = array(
    'preg_replace' => array(
        '~\b(' . preg_quote('echo $this->upgrade()->all_element_texts') . ')~' => '$this->trigger(\'view.show.before\'); \1',
        '~(' . preg_quote('$this->upgrade()->fire_plugin_hook(\'public_collections_show\'') . '.*?\)\;)~' => '$this->trigger(\'view.show.after\');',
    ),
    'prepend' => $prepend,
);

$upgrade['view/omeka/site/media/show.phtml'] = array(
    'preg_replace' => array(
        '~\b(' . preg_quote('echo $this->upgrade()->all_element_texts') . ')~' => '$this->trigger(\'view.show.before\'); \1',
        '~(' . preg_quote('echo $this->upgrade()->foot()') . ')~' => '$this->trigger(\'view.show.after\'); \1',
    ),
);

$prepend = <<<'OUTPUT'
<?php
// NOTE The template for the homepage has been removed and replaced by a normal
// page, where any block can now be added. This template can still be used as a
// block, but this feature needs a module.

?>

OUTPUT;

$upgrade['view/omeka/site/page/homepage.phtml'] = array(
    'prepend' => $prepend,
);

return $upgrade;
