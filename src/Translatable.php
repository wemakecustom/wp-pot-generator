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

    /**
     * Wether the plugin/theme is active.
     * @return boolean|null null if unknown
     */
    public function isActive()
    {
        return null;
    }

    abstract protected function getPath();

    public function getSlug()
    {
        $id = explode('/', $this->id);

        return $id[0];
    }

    public function getDomain()
    {
        return $this->getSlug();
    }

    public function getDomainPath()
    {
        return array(
            'languages/',
            'locale/',
            'lang/',
            '',
        );
    }

    public function getLangPath()
    {
        return WP_LANG_DIR . '/' . $this->getName();
    }


    public function getPotFile()
    {
        $slug = $this->getSlug();

        return WP_LANG_DIR . "/${slug}.pot";
    }

    public function getPoFile($locale)
    {
        return preg_replace('/\.pot$/', "-${locale}.po", $this->getPotFile());
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
                $this->compilePot();
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

        $return = array();

        if (isset($sitepress)) {
            $languages = $sitepress->get_languages();
            foreach ($languages as $language) {
                if ($language['code'] != 'en' && $language['active'] == 1) {
                    $return[$language['code']] = $language['default_locale'];
                }
            }
        }

        if (preg_match('/^([a-z]+)_([A-Z]+)$/', WPLANG, $matches)) {
            if ($matches[1] != 'en') {
                $return[$matches[1]] = $matches[0];
            }
        }

        return $return;
    }

    public function getExistingFile($locale)
    {
        $files = $this->getPossibleFiles($locale);

        if ($files) {
            return $files[0];
        }

        return false;
    }

    public function getPossibleFiles($locale)
    {
        $slug = $this->getSlug();
        list($lang) = explode('_', $locale);

        $prefix     = preg_replace('/\.pot$/', '', $this->getPotFile());
        $suffix     = '{' . "$locale,$lang" . '}';
        $extension  = '{po,mo}';
        $domainPath = '{' . implode(',', $this->getDomainPath()) . '}';

         return glob('{'
            . "${prefix}-${suffix}.${extension},"
            . "{$this->path}/${domainPath}*${suffix}.${extension}"
            . '}', GLOB_BRACE);
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
        $this->compilePot(); // Force
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

            $po->export_to_file($po_file, false);

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

    public function compilePot()
    {
        $this->prepareExport();
        $path = $this->getPath();
        $pot_file = $this->getPotFile();
        $this->makePot($path, $pot_file);

        // remove headers
        $po = new \PO;
        $po->import_from_file($pot_file);
        $po->export_to_file($pot_file, false);
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
    abstract protected function makePot($path, $pot_file);

    public static function findAll()
    {
        return array_merge(
            Theme::findAll(),
            Plugin::findAll()
        );
    }
}
