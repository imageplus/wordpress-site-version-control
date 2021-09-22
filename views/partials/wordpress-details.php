<article class="svc-wordpress-details">
    <h2 class="svc-subtitle">Wordpress Details</h2>
    <table class="wp-list-table widefat fixed striped table-view-list pages">
        <tbody>
        <?php foreach ($fieldsToDisplay as $field => $label): ?>
            <tr>
                <th><?= $label; ?></th>
                <?php foreach($versionDetails[$field] as $index => $detail): ?>
                    <?php $failedToGetData = $versionDetails['failed'][$index]; ?>
                    <td class="<?= $field == 'site_url' && $failedToGetData ? 'failed' : ''; ?>">
                        <?php if($failedToGetData && $field != 'site_url'): ?>
                            <span class="svg-failed-to-retrieve-data">Failed To Retrieve Data</span>
                        <?php else: ?>
                            <?= $detail . ($field == 'site_url' && $failedToGetData ? '(No Data)' : ''); ?>
                        <?php endif; ?>
                    </td>
                <?php endforeach; ?>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</article>
