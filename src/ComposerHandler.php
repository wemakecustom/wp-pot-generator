<?php

namespace WMC\Wordpress\PotGenerator;

use Composer\Script\Event;
use Composer\Util\ErrorHandler;
use WMC\Composer\Utils\Composer\PackageLocator;

class ComposerHandler
{
    public static function compile(Event $event)
    {
        $composer = $event->getComposer();
        $extras   = $composer->getPackage()->getExtra();
        $web_dir  = getcwd() . '/' . (empty($extras['web-dir']) ? 'htdocs' : $extras['web-dir']);

        self::wpCli($composer, $web_dir, 'pot compile');
    }

    public static function downloadLanguages(Event $event)
    {
        $composer = $event->getComposer();
        $extras   = $composer->getPackage()->getExtra();
        $web_dir  = getcwd() . '/' . (empty($extras['web-dir']) ? 'htdocs' : $extras['web-dir']);

        self::wpCli($composer, $web_dir, 'pot download');
    }

    private static function wpCli($composer, $web_dir, $command)
    {
        $cliPath = PackageLocator::getPackagePath($composer, 'wp-cli/wp-cli');

        passthru("bin/wp --path='${web_dir}' ${command}");
    }
}
