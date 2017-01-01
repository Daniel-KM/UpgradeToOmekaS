<?php
queue_css_file('upgrade-to-omeka-s');
queue_js_file('upgrade-to-omeka-s');

$pageTitle = __('Upgrade to Omeka Semantic');
echo head(array(
    'title' => $pageTitle,
    'bodyclass' => 'upgrade form' . ($isConfirmation ? ' confirm' : ''),
));
?>
<div id="primary">
    <?php echo flash(); ?>

    <?php
        echo common('upgrade-to-omeka-s-status', array(
            'isProcessing' => $isProcessing,
            'isCompleted' => $isCompleted,
            'isError' => $isError,
        ));
    ?>

    <?php
        echo common('upgrade-to-omeka-s-check', array(
            'hasErrors' => $hasErrors,
            'prechecks' => $prechecks,
            'processors' => $processors,
            'checks' => $checks,
            'plugins' => $plugins,
        ));
    ?>

    <?php if (empty($prechecks)): ?>
        <h2><?php echo __('Set Options'); ?></h2>
        <?php echo $form; ?>
        <?php if ($isConfirmation): ?>
            <?php if (!$isSiteDown): ?>
            <p class="explanation note"><?php echo __('Warning') . ': ' . __('The site will be set in maintenance mode.'); ?></p>
            <?php endif;?>
        <?php else: ?>
        <p class="explanation note"><?php echo __('A confirmation will be required in the next step.'); ?></p>
        <?php endif; ?>
    <?php endif;?>

    <h2><?php echo __('Logs'); ?></h2>
    <p><?php echo __('You may want to see the logs.'); ?></p>
    <?php if (!$isLogEnabled): ?>
    <p class="explanation note"><?php echo __('Omeka logs are not enabled with the minimum level of "info", so previous messages won’t be kept in case of a new process.'); ?></p>
    <?php endif; ?>
    <a class="medium green button" href="<?php echo url('/upgrade-to-omeka-s/index/logs'); ?>"><?php echo __('Display Logs'); ?></a>

    <h2><?php echo __('Maintenance mode'); ?></h2>
    <?php if ($isSiteDown): ?>
    <p>
        <?php echo __('The service is down.'); ?>
        <?php echo __('It will wake up when the process will be ended.'); ?>
    </p>
        <?php // Add a button in case of a manual stop or an error. ?>
        <?php if (!$isProcessing): ?>
    <a class="medium red button" href="<?php echo url('/upgrade-to-omeka-s/index/wake-up'); ?>"><?php echo __('Wake Up Omeka Classic'); ?></a>
        <?php endif; ?>
    <?php else: ?>
    <p>
        <?php echo __('The service is open.'); ?>
        <?php echo __('You can put it down now.'); ?>
    </p>
    <p>
        <?php echo __('The site will be automatically down when the process will be launched.'); ?>
    </p>
    <p class="explanation note"><?php echo __('Warning') . ': ' . __('All users will be logged out and the site will be set in maintenance mode, except for the super user.'); ?></p>
    <a class="medium blue button" href="<?php echo url('/upgrade-to-omeka-s/index/shutdown'); ?>"><?php echo __('Set Omeka Classic in maintenance mode'); ?></a>
    <?php endif; ?>

    <?php
        if ($livingRunningJobs):
            echo common('upgrade-to-omeka-s-running-jobs', array(
                'type' => 'living',
                'runningJobs' => $livingRunningJobs,
                'isStopped' => $isStopped,
            ));
        endif;
        if ($deadRunningJobs):
            echo common('upgrade-to-omeka-s-running-jobs', array(
                'type' => 'dead',
                'runningJobs' => $deadRunningJobs,
                'isStopped' => $isStopped,
            ));
        endif;
    ?>
</div>
<?php echo foot();
