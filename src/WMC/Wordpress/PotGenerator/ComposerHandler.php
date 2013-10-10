<?php

namespace WMC\Wordpress\PotGenerator;

use Composer\IO\IOInterface;
use Composer\Script\Event;

class ComposerHandler
{
    public static function compile(Event $event)
    {
        // Wordpress is so buggy we need to disable error checking
        $last_handler = set_error_handler(null);

        $io       = $event->getIO();
        $composer = $event->getComposer();
        $extras   = $composer->getPackage()->getExtra();

        $web_dir  = getcwd() . '/' . (empty($extras['web-dir']) ? 'htdocs' : $extras['web-dir']);

        if (!defined('ABSPATH')) {
            require_once $web_dir . '/wp-load.php';
        }

        $io->write('<info>Compiling translations</info>');

        $languages = Translatable::getLanguages();

        foreach (Translatable::findAll() as $translation) {
            if (file_exists($translation->getPotFile())) {
                $translation->export();
                $io->write(" • <info>{$translation->type}</info>: {$translation->id} {$stats['mo']}");
            }
        }

        set_error_handler($last_handler);
    }
}
