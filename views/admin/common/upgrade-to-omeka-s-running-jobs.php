<h2><?php echo __('Running Jobs'); ?></h2>
<table>
    <thead>
        <tr>
            <th><?php echo __('Id'); ?></th>
            <th><?php echo __('Class'); ?></th>
            <th><?php echo __('Date'); ?></th>
            <th><?php echo __('User'); ?></th>
            <th><?php echo __('User name'); ?></th>
            <th><?php echo __('pid'); ?></th>
        </tr>
    </thead>
    <tbody>
        <?php
        $key = 0;
        foreach ($runningJobs as $process):
            $arguments = $process->getArguments();
            $job = json_decode($arguments['job'], true);
            $classname = isset($job['className']) ? $job['className'] : '';
            $user = get_record_by_id('User', $process->user_id);
            $username = $user ? $user->username : __('deleted user');
        ?>
        <tr class="<?php echo ++$key % 2 ? 'odd' : 'even'; ?>">
            <td><?php echo $process->id; ?></td>
            <td><?php echo $classname; ?></td>
            <td><?php echo $process->started; ?></td>
            <td><?php echo $process->user_id; ?></td>
            <td><?php echo $username; ?></td>
            <td><?php echo $process->pid; ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<p><?php echo __('This button sets the status "error" to processes without a true process id (pid).'); ?></p>
<?php if (count($runningJobs) == 10): ?>
<p><?php echo __('Only the first ten running jobs are displayed and will be processed.'); ?></p>
<?php endif; ?>
<p><strong><?php echo __('Warning'); ?></strong>: <?php echo __('Before clicking this button, check all your background processes and the ones of the other users manually.'); ?></p>
<a class="medium blue button" href="<?php echo url('/upgrade-to-omeka-s/index/clean-jobs'); ?>"><?php echo __('Clean all the running jobs above'); ?></a>
