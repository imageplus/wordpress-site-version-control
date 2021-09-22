<?php

class RestManager
{
    /**
     * Contains the namespace the plugin uses to register the endpoint
     * @var string
     */
    public static $namespace = 'site-version-control';

    /**
     * getExternalSiteDetails
     *
     * makes a request to wp api for the given site to get the details to show
     *
     * @param $domain
     * @param $site_key
     * @param $endpoint
     * @return array
     */
    public static function getExternalSiteDetails($domain, $site_key, $endpoint, $parameters = []){

        //if the domain matches the site url we're using the same site so get data locally
        //rather than making a curl request to ourselves
        if($domain == get_option('siteurl')){
            global $svc_handlers;

            //jsonSerialize converts the field to an associative array
            $response = $svc_handlers['rest_manager']->getSiteDetails()->jsonSerialize();
        } else {
            $curl = curl_init();

            //build the query string for the request
            $query = http_build_query(array_merge([ 'key' => $site_key ], $parameters));

            curl_setopt_array($curl, [
                CURLOPT_URL            => $domain . DIRECTORY_SEPARATOR . 'wp-json' . DIRECTORY_SEPARATOR . self::$namespace . DIRECTORY_SEPARATOR . ($endpoint) . "?{$query}",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HEADER         => 0
            ]);

            $response = json_decode(curl_exec($curl), true);

            curl_close($curl);
        }

        return [
            'status'   => isset($curl) ? curl_getinfo($curl, CURLINFO_RESPONSE_CODE) : 200,
            'response' => $response
        ];
    }

    public function __construct(){

        //add a default password so we can't just access the rest api without some form of auth
        if(!defined('SITE_VERSION_CONTROL_PASSWORD')){
            define('SITE_VERSION_CONTROL_PASSWORD', 'password');
        }

        //create the route
        add_action('rest_api_init', [$this, 'addSiteDetailsRestRoute']);
        add_action('rest_api_init', [$this, 'addDatabaseFileRestRoute']);
        add_action('rest_api_init', [$this, 'addDeleteDatabaseFileRestRoute']);
    }

    /**
     * addRestRoute
     *
     * Adds custom rest api routes to given methods
     *
     * @param string $endpoint
     * @param string $method
     */
    public function addRestEndpoint($endpoint, $method){
        //register the rest route using the static details defined
        register_rest_route(
            self::$namespace,
            DIRECTORY_SEPARATOR . $endpoint,
            [
                'methods'             => 'GET',
                'callback'            => [$this, $method],

                //make sure the key is valid before attempting the rest functionality
                'permission_callback' => function() {
                    //if the key isn't set or doesn't match we don't have permission to view the api
                    return isset($_GET['key']) && $_GET['key'] == SITE_VERSION_CONTROL_PASSWORD;
                }
            ]
        );
    }

    /**
     * addSiteDetailsRestRoute
     *
     * Registers the route with Wordpress
     */
    public function addSiteDetailsRestRoute(){
        $this->addRestEndpoint('get-site-details', 'getSiteDetails');
    }

    public function addDatabaseFileRestRoute(){
        $this->addRestEndpoint('get-site-database', 'getSiteDatabase');
    }

    public function addDeleteDatabaseFileRestRoute(){
        $this->addRestEndpoint('delete-saved-database', 'deleteSavedSiteDatabaseFile');
    }

    /**
     * getSiteDetails
     *
     * Generates the rest response for the api
     *
     * @return WP_REST_Response
     */
    public function getSiteDetails(){
        return new WP_REST_Response(DataGenerator::siteDetails());
    }

    /**
     * getSiteDatabase
     *
     * Generates a database file and returns the path to download it
     *
     * @return WP_REST_Response
     */
    public function getSiteDatabase(){

        //creates the database file
        $database = DataGenerator::exportDatabase();

        //the path to the root of the site (/usr/username/site/public)
        $path = substr(
            $_SERVER['SCRIPT_FILENAME'],
            0,
            strrpos(
                $_SERVER['SCRIPT_FILENAME'],
                DIRECTORY_SEPARATOR
            )
        );

        return new WP_REST_Response([
            'data' => [
                'name' => $database,

                //send the path so we can just use file_get_contents to get the sql
                'path' => str_replace($path, '',SITE_VERSION_CONTROL_ENTRY) . DIRECTORY_SEPARATOR . $database
            ],
            'success'  => $database !== false
        ]);
    }

    public function deleteSavedSiteDatabaseFile(){
        if(isset($_GET['database']) && strpos($_GET['database'], '.sql') !== false){
            //the path to the root of the site (/usr/username/site/public)
            $path = substr(
                $_SERVER['SCRIPT_FILENAME'],
                0,
                strrpos(
                    $_SERVER['SCRIPT_FILENAME'],
                    DIRECTORY_SEPARATOR
                )
            );

            unlink($path . DIRECTORY_SEPARATOR . $_GET['database']);
        }

        return new WP_REST_Response([
            'data' => [
                'message' => 'Database File Removed'
            ],
            'success'     => true
        ]);
    }
}
