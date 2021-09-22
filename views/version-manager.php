<div class="wrap">
    <section class="svc-title">
        <h1 class="wp-heading">Site Version Control</h1>
    </section>

    <?php
        $versionDetails = array_reduce($sites, function($group, $site){
            array_push($group['failed'],       $site['details']['status'] != 200);
            array_push($group['site_url'],     $site['domain'] ?? null);
            array_push($group['found_url'],    $site['details']['response']['url'] ?? null);
            array_push($group['core_version'], $site['details']['response']['core']['version'] ?? null);
            array_push($group['db_version'],   $site['details']['response']['core']['database'] ?? null);
            array_push(
                $group['plugins'],
                array_map(
                    function($plugin){
                        return $plugin['Name'];
                    },
                    $site['details']['response']['plugins'] ?? []
                )
            );

            return $group;
        }, [
            'failed'       => [],
            'site_url'     => [],
            'found_url'    => [],
            'core_version' => [],
            'db_version'   => [],
            'plugins'      => []
        ]);

        $fieldsToDisplay = [
            'site_url'     => 'Requested Url',
            'found_url'    => 'Site Url',
            'core_version' => 'Wordpress Version',
            'db_version'   => 'Database Version'
        ];

        $allPlugins = array_merge(...$versionDetails['plugins']);
    ?>

    <div id="poststuff">
        <div id="post-body" class="svc-page">
            <section class="svc-main" id="post-body-content">
                <?php foreach(self::$messages as $message): ?>
                    <div class='notice notice-<?= $message['type']; ?> is-dismissible'>
                        <p><?= $message['message']; ?></p>
                    </div>
                <?php endforeach; ?>

                <?php require_once(SITE_VERSION_CONTROL_ENTRY . '/views/partials/wordpress-details.php'); ?>

                <?php require_once(SITE_VERSION_CONTROL_ENTRY . '/views/partials/plugin-details.php'); ?>
            </section>

            <section class="svc-sidebar">
                <article class="svc-sidebar--element">
                    <div class="postbox">
                        <div class="postbox-header">
                            <h2 class="svc-subtitle">Information</h2>
                        </div>
                        <div class="inside">
                            <div id="misc-publishing-actions">
                                <div class="svc-additional-information">
                                    <p>Paid plugins will typically not be downloadable through the Wordpress plugin directory (e.g. ACF) and these will need to be installed or copied manually</p>
                                    <p>When updating the version you will be redirected to the Wordpress update page where you will need to click the install now button below the version of the site you want to update to</p>
                                    <p>The recommended order to do things if you're copying an entire site is `Match Site Version`, `Match Plugin Versions`, `Copy Database`</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </article>

                <?php foreach($sites as $site_key => $site): ?>
                    <?php if($site['domain'] != get_option('siteurl')): ?>
                        <?php require(SITE_VERSION_CONTROL_ENTRY . '/views/partials/sidebar-site.php'); ?>
                    <?php endif; ?>
                <?php endforeach; ?>
            </section>
        </div>
    </div>
</div>
