<?php namespace Netcore\Translator;

use Illuminate\Support\Arr;
use Netcore\Translator\Helpers\TransHelper;
use Netcore\Translator\Models\Translation;

class Translator extends \Illuminate\Translation\Translator
{

    /**
     * Accessing cache (redis or file) is much faster than accessing MySQL
     * But accessing values that are stored as static properties is even more faster
     * Especially if Blade asks for ~300 translations.
     *
     * @param array
     */
    private static $staticCacheAllTranslations = [];

    /**
     * A common scenario is @each('profile', $users, 'user');
     * In this case, if we have 50 users, Blade will ask for all translations in profile.blade.php 50 times!
     * If there are 8 translations in each file, that creates 50x8=400 requests to cache!
     * And accessing cache 400 times can get noticably slow (4 seconds)
     *
     * @param array
     */
    private static $staticCacheFrequentTranslations = [];

    /**
     * Get the translation for the given key.
     *
     * @param string $id
     * @param array|string $parameters
     * @param null $locale
     * @param bool $fallback
     * @return String
     */
    public function get($id, $parameters = [], $locale = null, $fallback = true): String
    {
        /*
         * Overwrite way how trans('group.key') helper and @lang directive work
         * Here we rely completely on database. resources/lang files are never touched.
         * Since we cache all translations, this will not consume a lot of server resources.
         *
         * The benefit of this is:
         * 1. Admin can perform CRUD operations easily
         * 2. Don't have to worry about git merge conflicts if lang files are changed
         * 3. Don't have to worry about file permissions if dynamically rewriting lang files
         * 4. In case we ever want to go back to lang files, just uncomment our
         *    custom service provider in config/app.php
         *
         */

        $staticCacheKey = $id;
        if ($parameters) {
            $staticCacheKey .= '-' . md5(json_encode($parameters));
        }

        $cached = Arr::get(self::$staticCacheFrequentTranslations, $staticCacheKey);
        if ($cached) {
            return $cached;
        }

        if (!self::$staticCacheAllTranslations) {

            $translationsKeyInCache = config('translations.translations_key_in_cache');
            if ($translationsKeyInCache AND function_exists($translationsKeyInCache)) {
                $translationsKeyInCache = $translationsKeyInCache();
            } else {
                $translationsKeyInCache = 'translations';
            }

            self::$staticCacheAllTranslations = cache()->rememberForever($translationsKeyInCache, function () {
                $translations = [];

                foreach (Translation::all() as $translation) {
                    
                    // We want only Strings to be returned by this method. No arrays.
                    
                    //array_set($translations, "{$translation->locale}.{$translation->group}.{$translation->key}",
                        //$translation->value);

                    $key = "{$translation->locale}.{$translation->group}.{$translation->key}";
                    $translations[$key] = $translation->value;
                }

                return $translations;
            });
        }

        $currentLanguage = TransHelper::getLanguage();

        $fallbackLanguage = TransHelper::getFallbackLanguage();

        $translation = Arr::get(self::$staticCacheAllTranslations, "{$currentLanguage->iso_code}.{$id}", $id);

        if (!$translation) {
            $translation = Arr::get(self::$staticCacheAllTranslations, "{$fallbackLanguage->iso_code}.{$id}", $id);
        }

        if ($id != 'validation.attributes') {
            foreach ($parameters as $key => $value) {
                $translation = str_replace(':' . $key, $value, $translation);
            }
        }

        self::$staticCacheFrequentTranslations[$staticCacheKey] = $translation;

        return (string) $translation;
    }

    /**
     * Get the translation for a given key from the JSON translation files.
     *
     * @param  string  $id
     * @param  array  $parameters
     * @param  string  $locale
     * @return string
     */
    public function getFromJson($id, array $parameters = [], $locale = null)
    {
        $locale = $locale ?: $this->locale;

        return $this->get($id, $parameters, $locale);
    }
}