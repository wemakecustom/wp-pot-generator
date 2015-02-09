<?php

namespace WMC\Wordpress\PotGenerator;

class Plugin extends Translatable
{
    public $plugin;
    public $type = 'Plugin';

    public function __construct($id)
    {
        parent::__construct($id);

        $this->plugin = get_plugin_data($this->getPluginFile());
        $this->name = $this->getName();
    }

    public function getDomain()
    {
        if (isset($this->plugin['TextDomain'])) {
            return $this->plugin['TextDomain'];
        }

        return parent::getDomain();
    }

    public function getDomainPath()
    {
        $paths = parent::getDomainPath();

        if (isset($this->plugin['DomainPath'])) {
            $path = trim($this->plugin['DomainPath'], '/') . '/';
            if (array_search($path, $paths) === false) {
                $paths[] = $path;
            }
        }

        return $paths;
    }

    protected function getName()
    {
        return $this->plugin['Name'];
    }

    public function getPotFile()
    {
        $slug = $this->getSlug();

        return WP_LANG_DIR . "/plugins/${slug}.pot";
    }

    protected function getPluginFile()
    {
        $plugins = get_plugins("/{$this->id}");
        if ($plugins) {
            $file = current(array_keys($plugins));

            return "{$this->path}/$file";
        } else {
            return false;
        }
    }

    protected function getPath()
    {
        return WP_PLUGIN_DIR . "/{$this->id}";
    }

    protected function makePot($path, $pot_file)
    {
        $makepot = new \MakePOT;
        $slug = preg_replace("!^{$this->path}/(.*)\.php!", '$1', $this->getPluginFile());
        $makepot->wp_plugin($path, $pot_file, $slug);
    }

    public function isActive()
    {
        $file = preg_replace('/^.+\/([^\/]+\/[^\/]+\.php)$/', '$1', $this->getPluginFile());

        return is_plugin_active($file);
    }

    public static function findAll()
    {
        $plugins = array();

        foreach (get_plugins() as $id => $plugin) {
            list($id, $plugin_file) = explode('/', $id);
            $plugins[] = new static($id);
        }

        return $plugins;
    }
}
