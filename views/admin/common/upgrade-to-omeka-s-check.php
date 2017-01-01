<h2><?php echo __('Core & Server'); ?></h2>
<?php if (isset($prechecks['Core']) || isset($checks['Core'])): ?>
<p><?php echo __('Omeka can’t be upgraded.'); ?></p>
<ul><?php
// Checks contains prechecks.
if (isset($prechecks['Core'])):
    echo '<li>' . implode('</li><li>', $prechecks['Core']) . '</li>';
endif;
if (isset($checks['Core'])):
    echo '<li>' . implode('</li><li>', $checks['Core']) . '</li>';
endif;
?></ul>
<?php else: ?>
<p><?php echo __('The prechecks processor deems that Omeka Classic can be upgraded on this server.'); ?></p>
    <?php if ($hasErrors == 'form'): ?>
    <p><?php echo __('Nevertheless, the form should be checked.'); ?></p>
    <?php endif; ?>
<?php endif; ?>
<h2><?php echo __('Plugins'); ?></h2>
<table>
    <thead>
        <tr>
            <th><?php echo __('Plugin'); ?></th>
            <th><?php echo __('Installed'); ?></th>
            <th><?php echo __('Active'); ?></th>
            <th><?php echo __('Current version'); ?></th>
            <th><?php echo __('Required min version'); ?></th>
            <th><?php echo __('Required max version'); ?></th>
            <th><?php echo __('Processor'); ?></th>
            <th><?php echo __('Upgradable'); ?></th>
        </tr>
    </thead>
    <tbody>
        <?php
        $key = 0;
        foreach ($plugins as $name => $plugin):
            $rowClass = $plugin['skip'] ? 'upgrade-skip' . ' ' : '';
            $rowClass .= $plugin['upgradable'] ? 'upgrade-true' : 'upgrade-false';
            $rowClass .= ' ' . (++$key % 2 ? 'odd' : 'even');
        ?>
        <tr class="<?php echo $rowClass; ?>">
            <td<?php echo !$plugin['skip'] && !$plugin['upgradable'] ? ' rowspan="2"' : ''; ?>><?php echo $plugin['name']; ?></td>
            <td><?php echo $plugin['installed'] ? __('Yes') : __('No'); ?></td>
            <td><?php echo $plugin['active'] ? __('Yes') : __('No'); ?></td>
            <td><?php echo $plugin['version']; ?></td>
            <td><?php echo isset($processors[$name]) ? $processors[$name]->minVersion : ''; ?></td>
            <td><?php echo isset($processors[$name]) ? $processors[$name]->maxVersion : ''; ?></td>
            <td><?php echo !$plugin['skip'] ? __('Yes') : __('No'); ?></td>
            <td><?php echo $plugin['upgradable'] ? __('Yes') : __('No'); ?></td>
        </tr>
        <?php if (!$plugin['skip'] && !$plugin['upgradable']): ?>
        <tr>
            <td colspan="7" class="check-error"><div>
                <?php
                // An empty check means no processor.
                if (!empty($checks[$name])):
                    echo implode ('</div><div>', $checks[$name]);
                endif;
                ?>
            </div></td>
        </tr>
        <?php endif; ?>
        <?php endforeach; ?>
    </tbody>
</table>
