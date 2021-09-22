<article class="svc-plugin-details">
    <h2 class="svc-subtitle">Plugin Details</h2>

    <table class="wp-list-table widefat fixed striped table-view-list pages">
        <thead>
        <tr>
            <th>Plugin</th>
            <?php foreach ($sites as $site): ?>
                <th><?= $site['domain']; ?></th>
            <?php endforeach; ?>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($allPlugins as $pluginKey => $name): ?>
            <tr>
                <th><?= $name; ?></th>
                <?php foreach($sites as $index => $site): ?>
                    <?php $failedToGetData = $versionDetails['failed'][$index]; ?>
                    <td class="<?= $failedToGetData ? 'failed' : ''; ?>">
                        <?php if($failedToGetData): ?>
                            <span class="svg-failed-to-retrieve-data">Failed To Retrieve Data</span>
                        <?php elseif(!isset($site['details']['response']['plugins'][$pluginKey])): ?>
                            <span class="dashicons dashicons-dismiss" alt="Plugin Not Found"></span>
                        <?php else: ?>
                            <?= $site['details']['response']['plugins'][$pluginKey]['Version']; ?>
                        <?php endif; ?>
                    </td>
                <?php endforeach; ?>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</article>
