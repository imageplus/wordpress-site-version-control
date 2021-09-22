<?php

class FrontendManager
{
    protected static $details = null;

    /**
     * Contains the messages for the frontend to display
     * @var array
     */
    public static $messages = [];

    /**
     * Initialise the frontend
     */
    public function __construct(){
        //add the custom page to the sidebar
        add_action('admin_init', [$this, 'handleVersionRedirect']);

        //add the custom page to the sidebar
        add_action('admin_menu', [$this, 'addSettingsPage']);

        //register the settings used for the version of wordpress to update to
        add_action( 'admin_init', [$this, 'registerSettings']);

        //register the styles used for the frontend
        add_action( 'admin_init', [$this, 'registerStyles']);
    }

    /**
     * handleVersionRedirect
     *
     * When the settings for this page have been updated it means we need to complete a core update
     * so redirect to the core update page within Wordpress
     *
     * @return bool|void
     */
    public function handleVersionRedirect(){
        //if the settings have been updated and we're using this page redirect to update-core to finalise the update
        if(isset($_REQUEST['settings-updated']) && $_REQUEST['settings-updated'] && self::isSvcPage()){
            return wp_redirect(get_admin_url( null, '/update-core.php' ));
        }
    }

    /**
     * handleUpdateResponses
     *
     * Adds the for submission responses to the frontend forms added
     *
     * @return bool|void
     */
    public function handleUpdateResponses(){
        //if form_type is set and we're on the svc page
        if(isset($_REQUEST['form_type']) && self::isSvcPage()){
            global $svc_handlers;

            //we're updating the database
            if($_REQUEST['form_type'] == 'svc-database'){
                $response = $svc_handlers['update_manager']->getDatabaseFromSite($_REQUEST['site_id']);

                self::$messages[] = [
                    'type'    => $response ? 'success' : 'error',
                    'message' => $response ? 'Database Copied Successfully' : 'Database Copy Failed'
                ];
            }

            //we're updating the plugins
            else if($_REQUEST['form_type'] == 'svc-plugins'){
                $messages = $svc_handlers['update_manager']->updatePlugins($_REQUEST['plugin_versions']);

                foreach ($messages as $message){
                    self::$messages[] = $message;
                }
            }
        }
    }

    /**
     * registerSettings
     *
     * adds the custom setting for the core upgrade version
     */
    public function registerSettings(){
        register_setting('svc-settings-group', 'svc_upgrade_version');
    }

    /**
     * registerStyles
     *
     * Adds the stylesheet to the frontend is we're on the correct page
     */
    public function registerStyles(){
        if(self::isSvcPage()){
            wp_enqueue_style('svc-admin-styles', plugins_url('/assets/css/app.css', __DIR__));
        }
    }

    /**
     * addSettingsPage
     *
     * Adds the plugins page to the submenu inside of settings
     */
    public function addSettingsPage(){
        add_submenu_page(
            'options-general.php',
            'Site Version Control',
            'Site Version Control',
            'administrator',
            'svc-index',
            [$this, 'addFrontendPage']
        );
    }

    /**
     * addFrontendPage
     *
     * gets the site details and renders the frontend page
     */
    public function addFrontendPage(){
        $this->handleUpdateResponses();

        //remove the option set for updates whenever the page loads
        global $svc_handlers;

        $sites = self::getSiteDetails();

        //add the frontend page
        require SITE_VERSION_CONTROL_ENTRY . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR . 'version-manager.php';
    }

    /**
     * getSiteDetails
     *
     * gets the site details from the rest api on another site
     *
     * @return array|string[][]
     */
    protected static function getSiteDetails(): array
    {
        //if we haven't got the details we should generate them
        if(self::$details === null){
            //adds the external details to the sites array
            self::$details = array_map(
                function($site){
                    $site['details'] = RestManager::getExternalSiteDetails($site['domain'], $site['key'], $site['details-endpoint'] ?? 'get-site-details');

                    return $site;
                },
                self::getSites()
            );
        }

        return self::$details;
    }

    /**
     * getSites
     *
     * gets the credentials for the sites before the rest api takes over
     *
     * @return array[]
     */
    public static function getSites(): array
    {
        $currentSite = !defined('SITE_VERSION_CONTROL_SITES')
            ? [
                [
                    'domain' => get_option('siteurl'),
                    'key'    => SITE_VERSION_CONTROL_PASSWORD,
                    'live'   => false
                ]
            ] : SITE_VERSION_CONTROL_SITES;

        return apply_filters('site_version_control_sites', $currentSite);
    }

    /**
     * getLiveSite
     *
     * Attempts to get the live site in the array of sites
     *
     * @return array|false
     */
    public static function getLiveSite(): ?array
    {
        $live = array_search(true, array_column(self::getSiteDetails(), 'live'));

        return $live !== false
            ? self::getSiteDetails()[$live]
            : false;
    }

    /**
     * isSvcPage
     *
     * checks if the current page is the one registered against this plugin
     *
     * @return bool
     */
    public static function isSvcPage(){
        return isset($_REQUEST['page']) && $_REQUEST['page'] === 'svc-index' && is_admin();
    }
}
