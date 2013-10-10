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

    protected function getName()
    {
        return $this->plugin['Name'];
    }

    public function getPoFile($locale)
    {
        return "{$this->path}/languages/{$this->id}-$locale.po";
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

    public function makePot()
    {
        $this->prepareExport();

        $makepot = new \MakePOT;
        $slug = preg_replace("!^{$this->path}/(.*)\.php!", '$1', $this->getPluginFile());
        $makepot->wp_plugin($this->getPath(), $this->getPotFile(), $slug);
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
