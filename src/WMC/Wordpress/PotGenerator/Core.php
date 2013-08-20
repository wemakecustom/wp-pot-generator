<?php

namespace WMC\Wordpress\PotGenerator;

require_once __DIR__ . '/Translatable.php';

class Core extends Translatable
{
    public $type = 'Core';

    public function __construct()
    {
        parent::__construct('core');
        $this->name = $this->getName();
    }

    protected function getName()
    {
        return 'Core';
    }

    public function getPoFile($locale)
    {
        return "{$this->path}/$locale.po";
    }

    protected function getPath()
    {
        return WP_CONTENT_DIR . '/languages';
    }

    public function makePot()
    {
        // Skip
    }

    public static function findAll()
    {
        return array(new static()); // Only one
    }
}