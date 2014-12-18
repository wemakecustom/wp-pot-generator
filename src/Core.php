<?php

namespace WMC\Wordpress\PotGenerator;

class Core extends Translatable
{
    public $type = 'Core';

    public function __construct($id = '')
    {
        parent::__construct($id);
        $this->name = $this->getName();
    }

    protected function getName()
    {
        return ucwords(str_replace('-', ' ', $this->id));
    }

    public function getPoFile($locale)
    {
        $id = trim(str_replace('wordpress', '', $this->id), '-');
        if ($id) $id .= '-';
        return "{$this->path}/$id$locale.po";
    }

    protected function getPath()
    {
        return WP_CONTENT_DIR . '/languages';
    }

    public function makePot()
    {
        // skip
    }

    public function getPossibleFiles($locale)
    {
        $id = trim(str_replace('wordpress', '', $this->id), '-');
        if ($id) $id .= '-';

        list($lang) = explode('_', $locale);

        return glob("{$this->path}/{$id}{"."$locale,$lang"."}.{po,mo}", GLOB_BRACE);
    }

    public static function findAll()
    {
        $files = glob(WP_CONTENT_DIR . '/languages/wordpress*.pot');
        $array = array();

        foreach ($files as $file) {
            $id = basename($file, '.pot');
            $array[] = new static($id);
        }

        return $array;
    }
}
