<?php

class SiteUpdateManager
{
    /**
     * Adds the actions and filters required for updates
     */
    public function __construct()
    {
        //when using transient and options for update core we need to for forcing the core version
        add_filter('option_update_core', [$this, 'addCoreUpdateVersion']);
        add_filter('site_transient_update_core', [$this, 'addCoreUpdateVersion']);

        //when we've finished updating we need to remove the option so updates can work as normal
        add_action('upgrader_process_complete', [$this, 'removeUpdateVersion'], 10, 2);
    }

    /**
     * removeUpdateVersion
     *
     * removes the upgrade version from the database if we've just completed a core upgrade
     *
     * @param $action
     * @param $type
     */
    public function removeUpdateVersion($action, $type){
        if($action == 'update' && $type == 'core'){
            delete_option('svc_upgrade_version');
        }
    }

    /**
     * addCoreUpdateVersion
     *
     * sets the core version to download in the updates object so Wordpress downloads the right version
     *
     * @param  $updates
     * @return mixed|void
     */
    public function addCoreUpdateVersion($updates){
        global $wp_version;

        if($updates === false){
            return;
        }

        $newVersion = get_option('svc_upgrade_version');

        //no version was set so don't attempt to change to custom version
        if($newVersion < 1) {
            return $updates;
        }

        //we don't need to add a new version if they match
        if (version_compare( $wp_version, $newVersion ) == 0) {
            return $updates;
        }

        $url = "https://downloads.wordpress.org/release/en_GB/wordpress-{$newVersion}.zip";

        $updates->updates[0]->download = $url;
        $updates->updates[0]->packages->full = $url;
        $updates->updates[0]->packages->no_content = '';
        $updates->updates[0]->packages->new_bundled = '';
        $updates->updates[0]->current = $newVersion;

        return $updates;
    }

    /**
     * getDatabaseFromSite
     *
     * gets the database file from a given site
     *
     * @param  string|null $site
     * @return bool
     */
    public function getDatabaseFromSite($site = null){

        //get the site we want (either live or the passed site)
        if($site !== null){
            $site = FrontendManager::getSites()[$site] ?? false;
        } else {
            $site = FrontendManager::getLiveSite();
        }

        //we can only get and generate a database file if we have a valid site
        if($site !== false){
            //generate the database file
            $database = RestManager::getExternalSiteDetails($site['domain'], $site['key'], 'get-site-database');

            //if the request was successful we have a url to the database file
            if($database['response']['success']){

                //get and store the new database file
                 file_put_contents(
                    SITE_VERSION_CONTROL_ENTRY . DIRECTORY_SEPARATOR . '_temp.sql',
                    file_get_contents($site['domain'] . $database['response']['data']['path'])
                );

                //assuming wp-cli is installed locally / on staging as you should only be importing on these environments
                //and we control both with our staging servers or local environments but clients can host live themselves
                //so I can't assume wp-cli is installed

                $localDomain = get_option('siteurl');

                //empty current database
                $resetResponse = exec("wp db reset --yes");

                //import the new database
                $importResponse = exec("wp db import " . SITE_VERSION_CONTROL_ENTRY . DIRECTORY_SEPARATOR . '_temp.sql');

                //TODO: MUTLISITE SUPPORT https://developer.wordpress.org/cli/commands/search-replace/

                //replace the url
                exec("wp search-replace '{$site['domain']}' '{$localDomain}' --all-tables");

                //we've replaced the domain we've setup but wordpress may contain both https and http urls
                //so we'll replace both with the `siteurl`
                $secondaryReplacement = strpos($site['domain'], 'https://') !== false
                    ? str_replace('https://', 'http://', $site['domain'])
                    : str_replace('http://', 'https://', $site['domain']);

                exec("wp search-replace '{$secondaryReplacement}' '{$localDomain}' --all-tables");
                //exec("wp search-replace '{$live['domain']}' '{$localDomain}' --all-tables --log=" . SITE_VERSION_CONTROL_ENTRY . DIRECTORY_SEPARATOR . 'database_replace_log.txt');

                //delete the temporary file we created to handle the import
                unlink(SITE_VERSION_CONTROL_ENTRY . DIRECTORY_SEPARATOR . '_temp.sql');

                //TODO: Considering adding endpoint to delete the temp db file
                RestManager::getExternalSiteDetails(
                    $site['domain'],
                    $site['key'],
                    'delete-saved-database',
                    [
                        'database' => $database['response']['data']['path']
                    ]
                );

                return true;
            }
        }

        //we haven't successfully imported a new db file so return false
        return false;
    }

    /**
     * updatePlugins
     *
     * handles the update for the plugins on the site
     *
     * @param  array $plugins
     * @return array
     */
    public function updatePlugins($plugins){
        //we need the plugin updater here
        require_once SITE_VERSION_CONTROL_ENTRY . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR . 'PluginVersionHandler.php';

        $messages = [];
        foreach ($plugins as $name => $details) {
            //update an individual plugin to a given version
            $didUpdateSuccessfully = PluginVersionHandler::handlePlugin($name, "svc-plugin-version-{$name}", $details['plugin'], $details['version']);

            $messages[$name] = [
                'type'    => $didUpdateSuccessfully ? 'success' : 'error',
                'message' => $didUpdateSuccessfully
                    ? "{$name} Updated Successfully"
                    : "{$name} Failed To Update. Try Downloading This Plugin Manually"
            ];
        }

        return $messages;
    }
}
