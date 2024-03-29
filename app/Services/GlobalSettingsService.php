<?php

namespace App\Services;

use App\Models\Core\Settings\Website;
use App\Models\Core\Design\ThemeSetting;

use App\Services\WebsiteService;
use App\Services\Themes\ThemeService;

class GlobalSettingsService
{
    protected $websiteService;
    protected $themeService;
    protected $settings;

    CONST DEFAULT_THEME = "ikigai";
    CONST WEBSITE_SETTINGS = "website";
    CONST THEME_SETTINGS = "theme";
    CONST THEME_FOLDER = "themeFolder";

    public function __construct()
	{
        $this->websiteService = new WebsiteService();
        $this->themeService = new ThemeService();

        // Initialise settings with null value first
        $this->settings[self::WEBSITE_SETTINGS] = null;
        $this->settings[self::THEME_SETTINGS] = null;
        $this->settings[self::THEME_FOLDER] = self::DEFAULT_THEME;

        $websiteSettings = $this->websiteService->getSettings();
        $activeThemeId = data_get($websiteSettings, 'website.activeTheme');
        $websiteInstalled = data_get($websiteSettings, 'website.installed');

        // If there is an active theme fill the global settings with correct values
        if($websiteInstalled) {
            $theme = $this->themeService->getTheme($activeThemeId);
            $themeSettings = $this->themeService->getSettings($activeThemeId);

            $this->settings = [];
            $this->settings[self::WEBSITE_SETTINGS] = $websiteSettings;
            $this->settings[self::THEME_SETTINGS] = $themeSettings;
            $this->settings[self::THEME_FOLDER] = optional($theme)->name;
        }
    }

    public function all() {
        return $this->json($this->settings);
    }

    public function get($settings) {
        return $this->json($this->settings[$settings]);
    }

    private function json($settings) {
        return json_decode(json_encode($settings));
    }
}