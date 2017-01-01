<nav id="section-nav" class="navigation vertical">
<?php
    $navArray = array();
    if (!$isProcessing) {
        $navArray[] = array(
            'label' => __('Upgrade'),
            'action' => 'index',
            'module' => 'upgrade-to-omeka-s',
        );
    }
    $navArray[] = array(
        'label' => __('Logs'),
        'action' => 'logs',
        'module' => 'upgrade-to-omeka-s',
    );
    echo nav($navArray, 'admin_navigation_settings');
?>
</nav>
