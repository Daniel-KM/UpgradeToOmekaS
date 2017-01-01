<?php
queue_css_file('upgrade-to-omeka-s');
queue_js_file('upgrade-to-omeka-s');

$pageTitle = __('Upgrade to Omeka Semantic') . ' &middot; ' . __('Logs');
echo head(array(
    'title' => $pageTitle,
    'bodyclass' => 'upgrade logs',
));
?>
<?php echo common('upgrade-to-omeka-s-nav', array('isProcessing' => $isProcessing)); ?>
<div id="primary">
    <?php echo flash(); ?>

    <?php if ($isProcessing): ?>
        <?php if (empty($previousParams['isRemoving'])): ?>
        <p><?php echo __('An upgrade is processing in the background.'); ?></p>
        <?php if ($progress): ?>
            <p><?php
                echo __('Current task: %s / %s,  since %s.',
                    $progress['processor'],
                    str_replace('omeka s', 'Omeka S', Inflector::titleize(trim($progress['task'], '_'), 'first')),
                    $progress['start']
                );
            if (!empty($progress['total'])):
                $percent = $progress['current'] *100 / $progress['total'];
                $start = new DateTime($progress['start']);
                $now = new DateTime();
                if (empty($progress['current'])) {
                    echo ' ' . __('Progress: %s%d%%%s (%d / %d).',
                        '<strong>',
                        $percent,
                        '</strong>',
                        $progress['current'],
                        $progress['total']
                    );
                } else {
                    $since = $start->diff($now)->format('%s');
                    $remain = $progress['current'] == $progress['total']
                        ? 0
                        : ceil($since * (100 - $percent) / $percent);
                    echo ' ' . __('Progress: %s%d%%%s (%d / %d, about %s seconds remaining).',
                        '<strong>',
                        $percent,
                        '</strong>',
                        $progress['current'],
                        $progress['total'],
                        $remain
                    );
                }
            endif; ?></p>
        <?php endif; ?>
        <a class="medium red button" href="<?php echo url('/upgrade-to-omeka-s/index/stop'); ?>"><?php echo __('Stop Upgrade'); ?></a>
        <?php else: ?>
        <p><?php echo __('The previous upgrade is being removed.'); ?></p>
        <p><?php echo __('If it’s too long, stop the process to reset it and check rights of the folder or remove it yourself.'); ?></p>
        <a class="medium red button" href="<?php echo url('/upgrade-to-omeka-s/index/stop'); ?>"><?php echo __('Stop Removing'); ?></a>
        <?php endif; ?>
    <?php else: ?>
        <?php
            echo common('upgrade-to-omeka-s-status', array(
                'isProcessing' => $isProcessing,
                'isStopped' => $isStopped,
                'isCompleted' => $isCompleted,
                'isError' => $isError,
                'isReset' => $isReset,
                'hasPreviousUpgrade' => $hasPreviousUpgrade,
                'previousParams' => $previousParams,
            ));
        ?>
        <p><?php echo __('Go back to %supgrade to Omeka Semantic%s.', '<a href="' . url('/upgrade-to-omeka-s') . '">', '</a>'); ?></p>
    <?php endif; ?>

    <h2><?php echo __('Maintenance mode'); ?></h2>
    <?php if ($isSiteDown): ?>
    <p><?php echo __('The service is down.'); ?></p>
        <?php if ($isProcessing): ?>
    <p><?php echo __('It will wake up when the process will be ended.'); ?></p>
        <?php else: ?>
    <a class="medium red button" href="<?php echo url('/upgrade-to-omeka-s/index/wake-up'); ?>"><?php echo __('Wake Up Omeka Classic'); ?></a>
        <?php endif; ?>
    <?php else: ?>
    <p><?php echo __('The service is up.'); ?></p>
        <?php if ($isProcessing): ?>
    <p><?php echo __('The upgrade is processing, so the service should be down.'); ?></p>
        <?php endif; ?>
    <p class="explanation note"><?php echo __('Warning') . ': ' . __('All users will be logged out and the site will be set in maintenance mode, except for the super user.'); ?></p>
    <a class="medium blue button" href="<?php echo url('/upgrade-to-omeka-s/index/shutdown'); ?>"><?php echo __('Set Omeka Classic in maintenance mode'); ?></a>
    <?php endif; ?>

    <h2><?php echo __('Logs'); ?></h2>
    <?php if ($logs): ?>
    <table id="upgrade-logs">
        <thead>
            <tr>
                <th><?php echo __('#'); ?></th>
                <th><?php echo __('Date') . '<br />' . __('Time'); ?></th>
                <th><?php echo __('Priority'); ?></th>
                <th><?php echo __('Processor') . '<br />' . __('Task'); ?></th>
                <th><?php echo __('Message'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php
            foreach ($logs as $key => $log): ?>
            <tr class="<?php
                echo $key % 2 ? 'odd' : 'even';
                echo ' ' . $log['priority'];
            ?>">
                <td><?php echo $key + 1; ?></td>
                <td><?php echo strtok($log['date'], 'T'); ?>
                <br /><?php echo substr($log['date'], 11, 8); ?></td>
                <td><?php echo ucfirst($log['priority']); ?></td>
                <td><?php echo $log['processor']; ?>
                <br /><?php echo Inflector::titleize(trim($log['task'], '_'), 'first'); ?></td>
                <td><?php echo nl2br($log['message']); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php if ($isStopped): ?>
    <p><?php echo __('The upgrade process has been stopped, so this will be the last task.')?></p>
    <?php endif; ?>
    <?php else: ?>
    <p><?php echo __('No log yet.'); ?></p>
    <?php endif; ?>
    <?php if ($isLogEnabled): ?>
    <p><?php echo __('See the logs of Omeka Classic to check older logs.'); ?></p>
    <?php else: ?>
    <p class="explanation note"><?php echo __('Omeka logs are not enabled with the minimum level of "info", so previous messages won’t be kept in case of a new process.'); ?></p>
    <?php endif; ?>
</div>
<?php echo foot();
