<?php

class PluginVersionInstallerSkin extends Plugin_Installer_Skin
{
    /**
     * feedback
     *
     * to prevent the default class from outputting anything overwrite the method which handles most of the output
     *
     * @param string $string
     * @param mixed ...$args
     */
    public function feedback($string, ...$args)
    {
        return;
    }
}
