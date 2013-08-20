<?php

namespace WMC\Wordpress\PotGenerator;

require_once ABSPATH . 'wp-admin/includes/plugin.php';
require_once dirname(__DIR__) . '/makepot.php';


abstract class Translatable
{
    public $id;
    public $path;
    public $url;
    public $name;
    public $type = null;

    public function __construct($id)
    {
        $this->id   = $id;
        $this->path = $this->getPath();
        $this->url  = str_replace(ABSPATH, site_url() . '/', $this->getPath());
    }

    abstract protected function getPath();

    public function getPotFile()
    {
        return "{$this->path}/languages/{$this->id}.pot";
    }

    public function getPoFile($locale)
    {
        return "{$this->path}/languages/$locale.po";
    }

    public function getMoFile($locale)
    {
        return preg_replace('/\.po$/', '.mo', $this->getPoFile($locale));
    }

    public function getPo($locale)
    {
        if (file_exists($file = $this->getPoFile($locale))) {
            $po = new \PO;
            $po->import_from_file($file);
            return $po;
        }

        return false;
    }

    public function getMo($locale)
    {
        if (file_exists($file = $this->getMoFile($locale))) {
            $mo = new \MO;
            $mo->import_from_file($file);
            return $mo;
        }

        return false;
    }

    public function getPot($create = false)
    {
        if (!file_exists($this->getPotFile())) {
            if ($create) {
                $this->makePot();
            } else {
                return false;
            }
        }

        $pot = new \PO;
        $pot->import_from_file($this->getPotFile());

        return $pot;
    }

    public function getStats($locale)
    {
        $pot = $this->getPot();
        $stats = array(
            'pot' => $pot ? count($pot->entries) : '?',
            'po' => null,
            'mo' => null,
        );

        $po = $this->getPo($locale);
        if ($po) {
            foreach ($po->entries as $entry) {
                if (!empty($entry->translations)) {
                    $stats['po'] += 1;
                }
            }
        }

        $mo = $this->getMo($locale);
        if ($mo) {
            $stats['mo'] = count($mo->entries);
        }

        return $stats;
    }

    public static function getLanguages()
    {
        global $sitepress;
        $languages = $sitepress->get_languages();
        $return = array();

        foreach ($languages as $language) {
            if ($language['code'] != 'en' && $language['active'] == 1) {
                $return[$language['code']] = $language['default_locale'];
            }
        }

        return $return;
    }

    public function getExistingFile($locale)
    {
        list($lang) = explode('_', $locale);
        $files = glob("{$this->path}/{languages/,locale/,}*{"."$locale,$lang"."}.{po,mo}", GLOB_BRACE);

        if ($files) {
            return $files[0];
        }

        return false;
    }

    protected function loadExisting($locale)
    {
        $file = $this->getExistingFile($locale);

        if ($file) {
            preg_match('/\.(mo|po)$/', $file, $matches);
            $class = strtoupper($matches[1]); // PO or MO
            $loader = new $class;
            $loader->import_from_file($file);
            return $loader;
        }

        return false;
    }

    public function export()
    {
        $this->prepareExport();
        $this->makePot(); // Force
        $pot = $this->getPot(true);

        foreach (static::getLanguages() as $code => $locale) {
            $po_file = $this->getPoFile($locale);
            $mo_file = $this->getMoFile($locale);
            $has_content = false;
            $po = clone $pot;

            if ($po_orig = $this->loadExisting($locale)) {
                foreach ($po_orig->entries as $msgid => $entry) {
                    if (isset($po->entries[$msgid])) {
                        $po->entries[$msgid]->translations        = $entry->translations;
                        $po->entries[$msgid]->translator_comments = $entry->translator_comments;
                        if (count($entry->translations)) {
                            $has_content = true;
                        }
                    }
                }
            }

            $po->export_to_file($po_file);

            if ($has_content) {
                static::compilePo($po_file, $mo_file);
            } elseif (file_exists($mo_file)) {
                unlink($mo_file);
            }
        }
    }

    public static function compilePo($po_file, $mo_file)
    {
        $po = new \PO;
        $po->import_from_file($po_file);

        $mo = new \MO;
        $mo->headers = $po->headers;
        $mo->entries = $po->entries;
        $mo->export_to_file($mo_file);
    }

    protected function prepareExport()
    {
        $pot_dir = dirname($this->getPotFile());

        if (!is_dir($pot_dir)) {
            mkdir($pot_dir, 0777, true);
            if (!is_dir($pot_dir)) return false; // Error
        }

        return true;
    }

    abstract protected function getName();
    abstract public function makePot();
    abstract public static function findAll();
}