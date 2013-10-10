<?php

namespace WMC\Wordpress\PotGenerator;

class Theme extends Translatable
{
    public $theme;
    public $type = 'Theme';

    public function __construct($id)
    {
        parent::__construct($id);

        $this->theme = wp_get_theme($id);
        $this->name = $this->getName();
    }

    protected function getName()
    {
        return $this->theme->name;
    }

    protected function getPath()
    {
        $theme_root = get_theme_root($this->id);

        return "$theme_root/{$this->id}";
    }

    protected function loadExisting($locale)
    {
        $loader = parent::loadExisting($locale);
        if ($loader) {
            // Merge with parent translations if empty
            if ($parent = $this->theme->parent()) {
                $parent_generator = new Theme($parent->get_stylesheet());
                $this->merge($loader, $parent_generator, $locale);
            }
            // Merge with core
            $this->merge($loader, new Core(), $locale);
        }

        return $loader;
    }

    public function getPossibleFiles($locale)
    {
        list($lang) = explode('_', $locale);
        $id = explode('/', $this->id);
        $id = $id[count($id) - 1];

        $files = glob(WP_CONTENT_DIR . "/languages/themes/{$id}-{"."$locale,$lang"."}.{po,mo}", GLOB_BRACE);

        $files = array_merge(parent::getPossibleFiles($locale), $files);

        return $files;
    }

    private function merge(\Gettext_Translations $current, Translatable $parent, $locale)
    {
        $parent_loader = $parent->loadExisting($locale);
        $comment       = "Copied from {$parent->id}/$locale";

        foreach ($current->entries as $entry_id => &$entry) {
            if (isset($parent_loader->entries[$entry_id]) && (empty($entry->translations) || $entry->translator_comments == $comment)) {
                if ($translations = $parent_loader->entries[$entry_id]->translations) {
                    $entry->translator_comments = $comment;
                    $entry->translations        = $translations;
                }
            }
        }
    }

    public function makePot()
    {
        $this->prepareExport();

        $makepot = new \MakePOT;
        $makepot->wp_theme($this->getPath(), $this->getPotFile());
    }

    public static function findAll()
    {
        $themes = array();

        foreach (wp_get_themes() as $id => $theme) {
            $themes[] = new static($id);
        }

        return $themes;
    }
}
