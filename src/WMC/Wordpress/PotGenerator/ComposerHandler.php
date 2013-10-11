<?php

namespace WMC\Wordpress\PotGenerator;

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

        foreach (Translatable::findAll() as $translation) {
            if (!($translation instanceof Core) && file_exists($translation->getPotFile())) {
                $translation->export();
                $io->write(" • <info>{$translation->type}</info>: {$translation->id} {$stats['mo']}");
            }
        }

        set_error_handler($last_handler);
    }

    public static function downloadLanguages(Event $event)
    {
        global $wp_version;

        // Wordpress is so buggy we need to disable error checking
        $last_handler = set_error_handler(null);

        $io       = $event->getIO();
        $composer = $event->getComposer();
        $extras   = $composer->getPackage()->getExtra();

        $web_dir  = getcwd() . '/' . (empty($extras['web-dir']) ? 'htdocs' : $extras['web-dir']);

        if (!defined('ABSPATH')) {
            require_once $web_dir . '/wp-load.php';
        }

        $io->write('<info>Downloading translations</info>');

        preg_match('/^\d+\.\d+/', $wp_version, $matches);
        $branch = $matches[0];

        $svn = "http://i18n.svn.wordpress.org";
        $index_file = "$web_dir/wp-content/languages/cache.json";
        $index = array();

        if (file_exists($index_file)) {
            $index = json_decode(file_get_contents($index_file), true);
        }

        $files = array(
            "$svn/pot/branches/$branch/wordpress-admin-network.pot",
            "$svn/pot/branches/$branch/wordpress-admin.pot",
            "$svn/pot/branches/$branch/wordpress-continents-cities.pot",
            "$svn/pot/branches/$branch/wordpress.pot",
        );

        $languages = Translatable::getLanguages();

        foreach ($languages as $locale) {
            foreach (array('admin-', 'admin-network-', 'continents-cities-', '') as $key) {
                $files[] = "$svn/$locale/branches/$branch/messages/$key$locale.po";
                $files[] = "$svn/$locale/branches/$branch/messages/$key$locale.mo";
            }
        }

        foreach ($files as $source) {
            // @TODO use the SVN headers to only download when needed
            $filename = basename($source);
            $target = "$web_dir/wp-content/languages/$filename";
            $commit = self::getSVNCommit($source);

            if (file_exists($target) && isset($index[$filename])) {
                if ($index[$filename] == $commit) {
                    break;
                } else {
                    $io->write(" • Updating <info>$filename</info>");
                }
            } else {
                $io->write(" • Downloading <info>$filename</info>");
            }

            $data = file_get_contents($source);
            $index[$filename] = $commit;
            file_put_contents($target, $data);
        }

        file_put_contents($index_file, json_encode($index));

        set_error_handler($last_handler);
    }

    private static function getSVNCommit($url)
    {
        $headers = get_headers($url, true);
        if (isset($headers['ETag']) && preg_match('/^(\d+)/', trim($headers['ETag'], '\'"'), $matches)) {
            return (int) $matches[1];
        }

        return null;
    }
}
