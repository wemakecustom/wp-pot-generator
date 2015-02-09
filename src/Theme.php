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

    public function getDomain()
    {
        if ($this->theme->get('TextDomain')) {
            return $this->theme->get('TextDomain');
        }

        return parent::getDomain();
    }

    public function getDomainPath()
    {
        $paths = parent::getDomainPath();

        if ($this->theme->get('DomainPath')) {
            $path = trim($this->theme->get('DomainPath'), '/') . '/';
            if (array_search($path, $paths) === false) {
                $paths[] = $path;
            }
        }

        return $paths;
    }

    protected function getName()
    {
        return $this->theme->name;
    }

    public function getPotFile()
    {
        $slug = $this->getSlug();

        return WP_LANG_DIR . "/themes/${slug}.pot";
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

    protected function makePot($path, $pot_file)
    {
        $makepot = new \MakePOT;
        $makepot->wp_theme($path, $pot_file);
    }

    public function isActive()
    {
        $theme = wp_get_theme();

        return $theme->name == $this->name || $theme->parent_theme == $this->name;
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
