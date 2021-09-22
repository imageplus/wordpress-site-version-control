<?php

//we need the plugin upgrader and wordpress upgrader to preform updates
require_once ABSPATH . '/wp-admin/includes/class-wp-upgrader.php';
require_once ABSPATH . '/wp-admin/includes/class-plugin-upgrader.php';
require_once SITE_VERSION_CONTROL_ENTRY . '/classes/skins/PluginVersionInstallerSkin.php';
require_once SITE_VERSION_CONTROL_ENTRY . '/classes/skins/PluginVersionUpdaterSkin.php';

class PluginVersionHandler
{
    //the base url for wordpress plugins to download from
    public static $wordpressPluginRepositoryBase = 'https://downloads.wordpress.org/plugin/';

    public static function handlePlugin($title, $nonce, $plugin, $version)
    {
        //this is the url to download the version of the plugin from
        $url = self::attemptToFindUrl($title, $version);

        //if url is false we don't have a valid url so can't download the plugin
        if(!$url){
            return false;
        }

        //get the data for the skins to use
        $data = self::generatePluginData($title, $nonce, $plugin, $version);

        //every plugin outputs its name and information using the is_multi option
        //removes the title and footer information
        add_filter('upgrader_package_options', function($options){
            $options['is_multi'] = true;

            return $options;
        });

        //if the give path is a directory the plugin exists so we're changing it's version
        if(is_dir(plugin_dir_path(__DIR__) . "/{$plugin}")){
            return self::updateExistingPlugin($url, $data);
        } else {
            return self::installNewPlugin($url, $data);
        }
    }

    /**
     * attemptToFindUrl
     *
     * will validate if the urls provide a downloadable file and return the first valid url
     *
     * @param  string $title
     * @param  string $version
     * @return false|string
     */
    protected static function attemptToFindUrl($title, $version){
        $url = self::$wordpressPluginRepositoryBase . $title . '.' . $version . '.zip';

        //if the file exists with the version
        if(self::doesFileExist($url)){
            return $url;
        }

        $noVersionUrl = self::$wordpressPluginRepositoryBase . $title . '.zip';

        //if the file exists without the version
        if(self::doesFileExist($noVersionUrl)){
            return $noVersionUrl;
        }

        //we don't have a valid download
        return false;
    }

    /**
     * doesFileExist
     *
     * validates if the url provides a downloadable file
     * (application/octet-stream content type header)
     *
     * @param  string $url
     * @return bool
     */
    protected static function doesFileExist($url){
        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_NOBODY => 1,
        ]);

        curl_exec($ch);
        curl_close($ch);

        return curl_getinfo($ch, CURLINFO_CONTENT_TYPE) === 'application/octet-stream';
    }

    /**
     * generatePluginData
     *
     * returns the array of data required to be passed into the skin for the plugin updater to handle
     *
     * @param  string $title
     * @param  string $nonce
     * @param  string $plugin
     * @param  string $version
     * @return array
     */
    protected static function generatePluginData($title, $nonce, $plugin, $version){
        return [
            'title'    => $title,
            'nonce'    => $nonce,
            'url'      => admin_url('options-general.php?page=svc-index'),
            'plugin'   => $plugin,
            'version'  => $version
        ];
    }

    /**
     * installNewPlugin
     *
     * installs a new plugin to the site
     *
     * @param string $url
     * @param array $pluginData
     */
    protected static function installNewPlugin($url, $pluginData){
        //custom installer to hide the messages output by the default installer
        $installerSkin = new PluginVersionInstallerSkin($pluginData);

        $installer = new Plugin_Upgrader($installerSkin);
        return $installer->install($url, [
            'overwrite_package' => true
        ]);
    }

    /**
     * updateExistingPlugin
     *
     * Attempts to update a plugin to a specific version
     *
     * @param string $url
     * @param array $pluginData
     */
    protected static function updateExistingPlugin($url, $pluginData){
        self::addUpdateTransient(...$pluginData);

        //custom updater to hide the messages output by the default upgrader
        $updaterSkin = new PluginVersionUpdaterSkin($pluginData);

        $updater = new Plugin_Upgrader($updaterSkin);
        return $updater->upgrade($url, [
            'clear_update_cache' => true
        ]);
    }

    protected static function addUpdateTransient($title, $nonce, $url, $plugin, $version){
        //when updating plugins wordpress validates if the plugin requires an update so we need to add an 'update' for the plugin we wish to update
        add_filter('site_transient_update_plugins', function($value) use($title, $plugin, $version, $url) {

            //we can only append to this object if it exists
            //(can be false or null as well)
            if(is_object($value)){
                $currentPlugin = new stdClass();

                $currentPlugin->slug = $title;
                $currentPlugin->plugin = $plugin;

                //the 'new' version of the plugin is whatever we want to install
                $currentPlugin->new_version = $version;

                //the package is the url we want to download the plugin from
                $currentPlugin->package = $url;

                //append the plugin to the response array key'd by the plugin entry point
                //e.g. plugin/index.php
                $value->response[$currentPlugin->plugin] = $currentPlugin;
            }

            return $value;
        });
    }
}
