<?php

namespace WMC\Wordpress\PotGenerator\Command;

use WMC\Wordpress\PotGenerator\Translatable;
use WMC\Wordpress\PotGenerator\Plugin;
use WMC\Wordpress\PotGenerator\Theme;
use WMC\Wordpress\PotGenerator\Core;

/**
 * POT-Generator
 */
class PotCommand extends \WP_CLI_Command {

    /**
     * Compile pot/po/mo for all active plugins and themes.
     */
    function compile( $args, $assoc_args ) {
        \WP_CLI::line('Compiling translations');

        foreach (Translatable::findAll() as $translation) {
            if ($translation->isActive() !== false) {
                @$translation->export();
                \WP_CLI::line(" • {$translation->type}: {$translation->id}");
            }
        }
    }

    /**
     * Download translation files from http://i18n.svn.wordpress.org
     */
    function download( $args, $assoc_args ) {
        global $wp_version;

        $languages_path = WP_CONTENT_DIR . '/languages';

        \WP_CLI::line('Downloading translations');

        preg_match('/^\d+\.\d+/', $wp_version, $matches);
        $branch = $matches[0];

        $svn = "http://i18n.svn.wordpress.org";
        $index_file = "$languages_path/cache.json";
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
            $target = "$languages_path/$filename";
            $commit = self::getSVNCommit($source);

            if (file_exists($target) && isset($index[$filename])) {
                if ($index[$filename] == $commit) {
                    break;
                } else {
                    \WP_CLI::line(" • Updating $filename");
                }
            } else {
                \WP_CLI::line(" • Downloading $filename");
            }

            $data = file_get_contents($source);
            $index[$filename] = $commit;
            file_put_contents($target, $data);
        }

        file_put_contents($index_file, json_encode($index));
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
