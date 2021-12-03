<?php

namespace Secomapp\Events;


class AppThemesFetched
{
    private $themes;

    /**
     * AppThemesFetched constructor.
     * @param array $themes
     */
    public function __construct($themes)
    {
        $this->themes = $themes;
    }

    /**
     * @return array
     */
    public function getThemes()
    {
        return $this->themes;
    }
}