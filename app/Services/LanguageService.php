<?php

namespace App\Services;

use Illuminate\Support\Facades\File;

class LanguageService
{
    /**
     * Get the flag emoji for a language code
     *
     * @param string $locale
     * @return string
     */
    public static function getLanguageFlag(string $locale): string
    {
        $flags = [
            'de' => '🇩🇪',
            'en' => '🇬🇧',
            'es' => '🇪🇸',
            'fr' => '🇫🇷',
            'it' => '🇮🇹',
            'pt' => '🇵🇹',
            'nl' => '🇳🇱',
            'pl' => '🇵🇱',
            'ru' => '🇷🇺',
            'zh' => '🇨🇳',
            'ja' => '🇯🇵',
            'ko' => '🇰🇷',
            'ar' => '🇸🇦',
        ];

        return $flags[$locale] ?? '🌐';
    }

    /**
     * Check if a locale is valid
     *
     * @param string $locale
     * @return bool
     */
    public static function isValidLocale(string $locale): bool
    {
        return array_key_exists($locale, self::getAvailableLanguages());
    }

    /**
     * Get all available languages from the lang directory
     *
     * @return array
     */
    public static function getAvailableLanguages(): array
    {
        $langPath = lang_path();
        $languages = [];

        // Scan for JSON language files
        $files = File::files($langPath);

        foreach ($files as $file) {
            if ($file->getExtension() === 'json') {
                $locale = $file->getFilenameWithoutExtension();
                $languages[$locale] = self::getLanguageName($locale);
            }
        }

        // Sort alphabetically by language name
        asort($languages);

        return $languages;
    }

    /**
     * Get the native name for a language code
     *
     * @param string $locale
     * @return string
     */
    public static function getLanguageName(string $locale): string
    {
        $names = [
            'de' => 'Deutsch',
            'en' => 'English',
            'es' => 'Español',
            'fr' => 'Français',
            'it' => 'Italiano',
            'pt' => 'Português',
            'nl' => 'Nederlands',
            'pl' => 'Polski',
            'ru' => 'Русский',
            'zh' => '中文',
            'ja' => '日本語',
            'ko' => '한국어',
            'ar' => 'العربية',
        ];

        return $names[$locale] ?? strtoupper($locale);
    }

    /**
     * Get the current locale with fallback
     *
     * @return string
     */
    public static function getCurrentLocale(): string
    {
        return app()->getLocale();
    }
}
