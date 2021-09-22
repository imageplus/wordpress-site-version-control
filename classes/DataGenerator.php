<?php

require_once ABSPATH . 'wp-admin/includes/plugin.php';

class DataGenerator
{
    /**
     * siteDetails
     *
     * Returns the array of site details sent with the rest api
     *
     * @return array
     */
    public static function siteDetails(){
        global $wp_version, $wp_db_version;

        return [
            //gets the wordpress core versions required
            'core'   => [
                'version'  => $wp_version,
                'database' => $wp_db_version
            ],

            'url'     => get_site_url(),

            //gets all information on the plugins
            'plugins' => get_plugins()
        ];
    }

    /**
     * exportDatabase
     *
     * generates a sql file of the database in the plugin directory
     *
     * @return false|string
     */
    public static function exportDatabase(){
        //options passed to the command
        $options = apply_filters(
            'svc_mysqldump_options',
            [
                '--host'      => DB_HOST,
                '--user'      => DB_USER,
                '--password'  => DB_PASSWORD,
            ]
        );

        //if options isn't an array we fail
        if(!is_array($options)){
            return false;
        }

        //builds query string in the format --option1= --option2=
        $commandOptions = http_build_query($options, '', ' ');

        //the name of the export
        $name = DB_NAME . '-export-' . date('Ymd') . '.sql';

        //the command to run
        $command = "mysqldump {$commandOptions} " . DB_NAME . " > " . SITE_VERSION_CONTROL_ENTRY . "/{$name}";

        $result = exec($command);

        if($result !== false){
            return $name;
        }

        return false;
    }
}
