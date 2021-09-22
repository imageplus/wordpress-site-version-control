# Imageplus Site Version Control
This plugin helps maintain consistent versions between local, staging and production environments by providing functionality to match different versions of plugins,the Wordpress core and cloning the database.

Due to local sites typically not being publicly accessible there is no easy way of adding support for matching the staging or production environments to local. The other way around is possible however.

For cloning the database a working copy of mysql dump must be installed on the site you're downloading from

## Installation

### Defining a password
For security reasons we should add different passwords per site and version of the site due to this returning versions of plugins and the core so to avoid people knowing about vulnerabilities we may have missed we don't want any access to be provided to this.

To define a custom password for the rest endpoint make use of the `SITE_VERSION_CONTROL_PASSWORD` global to set the password 

e.g. 

```php
    define('SITE_VERSION_CONTROL_PASSWORD', 'password')
```

### Defining Sites
All versions of the site must be defined.

If using the config file all sites must be defined (local, staging, production) using the global `SITE_VERSION_CONTROL_SITES`

You can also use the `site_version_control_sites` filter to define only staging and production environments as local will have already been added automatically without the global `SITE_VERSION_CONTROL_SITES` being defined

The sites listed in here must be in the below format

```php
    [
        'domain' => 'https://www.image-plus.co.uk', //the full domain of the site
        'key'    => SITE_VERSION_CONTROL_PASSWORD,  //the rest password defined in the plugin for that site
        'live'   => false                           //if this is the live site or not
    ]
```

## Usage

A new section is added to the admin when the plugin is activated. This can be found under `Settings > Site Version Control`

This page will list all plugins and versions across all 3 sites.

Use the sidebar on the right to preform updates to plugins, the Wordpress core version and the database.