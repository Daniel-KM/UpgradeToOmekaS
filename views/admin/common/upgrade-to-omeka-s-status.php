<?php if ($isCompleted): ?>
<h2><?php echo __('Completed!'); ?></h2>
<p><?php echo __('The previous upgrade finished successfully!'); ?></p>
<p><?php echo __('Go to your %snew site%s built on Omeka Semantic.', '<a href="' . get_option('upgrade_to_omeka_s_process_url') . '" target="_blank">', '</a>'); ?></p>
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
<a class="medium blue button" href="<?php echo url('/upgrade-to-omeka-s/index/reset'); ?>"><?php echo __('Reset Status'); ?></a>
<?php endif; ?>
