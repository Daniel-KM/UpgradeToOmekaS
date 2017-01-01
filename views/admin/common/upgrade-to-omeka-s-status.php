<?php if ($isCompleted): ?>
<h2><?php echo __('Completed!'); ?></h2>
<p><?php echo __('The previous upgrade finished successfully!'); ?></p>
<p><?php echo __('Go to your %snew site%s built on Omeka Semantic and %slogin%s to see the new world.',
    '<a href="' . $previousParams['url'] . '" target="_blank">', '</a>',
    '<a href="' . $previousParams['url'] . '/login" target="_blank">', '</a>'); ?> </p>
<p class="explanation note"><?php echo __('Note') . ': ' . __('The url may be wrong if the config of the server is hardly customized.'); ?></p>
<?php endif; ?>

<?php if ($isError): ?>
<h2><?php echo __('Error!'); ?></h2>
<p><?php
    echo __('An error occurred during the previous upgrade.');
    echo ' ' . __('Check the logs and clean your install if needed.');
?></p>
<p><?php echo __('Your current install is never modified.'); ?></p>
<?php endif; ?>

<?php if ($isError || $isCompleted): ?>
<p><?php echo __('You may want to reset the main status of the upgrade to retry it with different parameters.'); ?></p>
<p><?php echo __('To reset the process is required if you want to remove automatically the created tables and the copied files.'); ?></p>
<a class="medium blue button" href="<?php echo url('/upgrade-to-omeka-s/index/reset'); ?>"><?php echo __('Reset Status'); ?></a>
<?php endif; ?>

<?php if ($isReset && $hasPreviousUpgrade): ?>
<p><?php echo __('You can safely remove automatically the created tables (%s), the Omeka S folder (%s) and the copied files of the previous process, if wished.',
    $previousParams['database']['type'] == 'shared'
        ? __('shared database')
        : $previousParams['database']['host'] . (empty($previousParams['database']['port']) ? '' : ':' . $previousParams['database']['port']) . ' / ' . $previousParams['database']['dbname'],
    $previousParams['base_dir']); ?></p>
<p><?php echo __('The database itself won’t be removed.'); ?></p>
<a class="medium red button" href="<?php echo url('/upgrade-to-omeka-s/index/remove'); ?>" onclick="return confirm('<?php echo __('Are you sure to remove all tables and files of Omeka S?'); ?>');">
    <?php echo __('Remove Tables and Files of Omeka Semantic'); ?>
</a>
<?php endif; ?>
