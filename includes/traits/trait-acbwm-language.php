<?php
defined('ABSPATH') || exit;

trait Acbwm_Trait_Language
{
    /**
     * get all the available languages
     * @return mixed
     */
    public static function get_all_available_languages()
    {
        $available_languages = array();
        $languages = get_available_languages();
        if (is_array($languages)) {
            $default_language = acbwm_get_default_language();
            $languages = array_merge(array($default_language), $languages);
            $languages = array_unique($languages);
            if (!empty($languages)) {
                foreach ($languages as $language_code) {
                    $available_languages[$language_code] = self::get_language_name($language_code);
                }
            }
        }
        return $available_languages;
    }

    /**
     * get language label by lang code
     * @param $language_code
     * @return mixed|string|null
     */
    public static function get_language_name($language_code)
    {
        if ($language_code == 'en_US') {
            return "English (United States)";
        } else {
            $translations = self::get_available_translations();
            if (isset($translations[$language_code]['native_name'])) {
                return $translations[$language_code]['native_name'];
            }
        }
        return NULL;
    }

    /**
     * Get site's default language
     * @return array
     */
    public static function get_available_translations()
    {
        require_once(ABSPATH . 'wp-admin/includes/translation-install.php');
        if (function_exists('wp_get_available_translations')) {
            return wp_get_available_translations();
        }
        return array();
    }

    /**
     * Get the default language of the site
     * @return String|null
     */
    public static function get_current_language()
    {
        if (defined('ICL_LANGUAGE_CODE')) {
            return ICL_LANGUAGE_CODE;
        }
        return acbwm_get_default_language();
    }
}