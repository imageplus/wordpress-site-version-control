<article id="svc-sidebar--element">
    <div class="postbox">
        <div class="postbox-header">
            <h2 class="svc-subtitle">Site Actions</h2>
        </div>
        <div class="inside">
            <div id="misc-publishing-actions">
                <div>
                    <span class="dashicons dashicons-admin-site-alt3"></span>
                    <a href="<?= $site['domain']; ?>" target="_blank" rel="nofollow"><?= $site['domain']; ?></a>
                </div>

                <div>
                    <span class="dashicons dashicons-wordpress"></span>
                    <span><?= $site['details']['response']['core']['version'] ?? ''; ?></span>
                </div>
            </div>
        </div>
        <div id="major-publishing-actions">
            <form method="POST" action="options.php">
                <?php settings_fields('svc-settings-group'); ?>

                <input type="hidden" maxlength="6"  pattern="[-+]?[0-9]*[.]?[0-9]?[.]?[0-9]+" name="svc_upgrade_version" value="<?= $site['details']['response']['core']['version']; ?>" />

                <button class="svc-form-button button button-primary button-large" type="submit">Match Site Version</button>
            </form>

            <form method="POST">
                <input type="hidden" name="form_type" value="svc-database" />
                <input type="hidden" name="site_id" value="<?= $site_key; ?>" />

                <button class="svc-form-button button button-secondary button-large" type="submit">Copy Database</button>
            </form>

            <form method="POST">
                <input type="hidden" name="form_type" value="svc-plugins" />

                <?php foreach ($site['details']['response']['plugins'] as $name => $plugin): ?>
                    <input type="hidden" name="plugin_versions[<?= explode('/', $name)[0]; ?>][version]" value="<?= $plugin['Version']; ?>">
                    <input type="hidden" name="plugin_versions[<?= explode('/', $name)[0]; ?>][plugin]" value="<?= $name; ?>">
                <?php endforeach; ?>

                <button class="svc-form-button button button-primary button-large" type="submit">Match Plugin Versions</button>
            </form>
        </div>
    </div>
</article>
