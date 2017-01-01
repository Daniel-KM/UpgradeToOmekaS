<h2><?php echo __('Core & Server'); ?></h2>
<?php
if (!empty($prechecksCore)): ?>
    <p class="check-error"><?php echo __('Omeka can’t be upgraded.'); ?></p>
    <ul>
    <?php
    foreach ($prechecksCore as $name => $value):
        echo '<li>' . implode('</li><li>', $value) . '</li>';
    endforeach;
    ?>
    </ul><?php
elseif (!empty($checksCore)): ?>
    <p class="check-error"><?php echo __('Omeka can be upgraded, but some errors have been reported in the form.'); ?></p>
    <ul>
    <?php
    foreach ($checksCore as $name => $value):
        echo '<li>' . implode('</li><li>', $value) . '</li>';
    endforeach;
    ?>
    </ul><?php
else: ?>
    <p><?php echo __('The precheck processor deems that the core of Omeka Classic can be upgraded on this server.'); ?></p>
    <?php if ($hasErrors == 'form'): ?>
    <p class="check-error"><?php echo __('Nevertheless, the form should be checked.'); ?></p>
    <?php endif; ?>
<?php endif; ?>
<h2><?php echo __('Plugins'); ?></h2>
<?php
// A check for the message for the plugins, except core.
if ($prechecksPlugins or $checksPlugins):
    $totalErrorsPlugins = count($prechecksPlugins) + count($checksPlugins); ?>
<p class="check-error"><?php echo function_exists('plural')
    ? __(plural('%d plugin can’t be upgraded.', '%d plugins can’t be upgraded.', $totalErrorsPlugins), $totalErrorsPlugins)
    :  __('%d plugins can’t be upgraded.', $totalErrorsPlugins); ?>
</p>
<p><?php echo function_exists('plural')
    ? __(plural('Fix it before upgrade or skip it.', 'Fix them before upgrade or skip them.', $totalErrorsPlugins), $totalErrorsPlugins)
    : __('Fix them before upgrade or skip them.', $totalErrorsPlugins);
?></p>
<?php else:
    echo '<p>' . __('The precheck processor deems that all active plugins with an available processor can be upgraded.') . '</p>';
endif;
?>
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
            $rowClass = $plugin['processor'] ? 'upgrade-has-processor' . ' ' : '';
            $rowClass .= $plugin['upgradable'] ? 'upgrade-true' : 'upgrade-false';
            $rowClass .= ' ' . (++$key % 2 ? 'odd' : 'even');
            $error = $plugin['processor'] && $plugin['installed'] && $plugin['active'] && !$plugin['upgradable'];
            $pluginProcessor = !empty($plugin['processor']) ? $plugin['processor'] : null;
            $pluginProcessorPrecheck = $pluginProcessor ? $pluginProcessor->precheckProcessorPlugin() : null;
            $pluginProcessorNote = $pluginProcessor && !empty($pluginProcessor->module['note']) ? $pluginProcessor->module['note'] : null;
            $note = !empty($prechecksPlugins[$name]) || !empty($checksPlugins[$name]) || !empty($pluginProcessorPrecheck) || $pluginProcessorNote;
        ?>
        <tr class="<?php echo $rowClass; ?>">
            <td<?php echo $error || $note ? ' rowspan="2"' : ''; ?>><?php echo $plugin['name']; ?></td>
            <td><?php echo $plugin['installed'] ? __('Yes') : __('No'); ?></td>
            <td><?php echo $plugin['active'] ? __('Yes') : __('No'); ?></td>
            <td><?php echo $plugin['version']; ?></td>
            <td><?php echo $pluginProcessor ? $pluginProcessor->minVersion : ''; ?></td>
            <td><?php echo $pluginProcessor ? $pluginProcessor->maxVersion : ''; ?></td>
            <td><?php echo $pluginProcessor && !$pluginProcessorPrecheck ? __('Yes') : __('No'); ?></td>
            <td><?php echo $plugin['upgradable'] ? __('Yes') : __('No'); ?></td>
        </tr>
        <?php if ($error || $note): ?>
        <tr>
            <td colspan="7">
                <?php
                // Processor prechecks are done via the main prechecks too.
                if (!empty($pluginProcessorPrecheck) && empty($prechecksPlugins[$name])):
                    echo '<div class="check-warn">' . $pluginProcessorPrecheck . '</div>';
                endif;
                if (!empty($pluginProcessorNote)):
                    echo '<div class="processor-note">' . __('Upgrade note: %s', __($pluginProcessorNote)) . '</div>';
                endif;
                if (!empty($prechecksPlugins[$name])):
                    echo '<div class="check-error">' . implode ('</div><div class="check-error">', $prechecksPlugins[$name]) . '</div>';
                endif;
                if (!empty($checksPlugins[$name])):
                    echo '<div class="check-error">' . implode ('</div><div class="check-error">', $checksPlugins[$name]) . '</div>';
                endif;
                ?>
            </td>
        </tr>
        <?php endif; ?>
        <?php endforeach; ?>
    </tbody>
</table>
