<?php

namespace Leat\Utils;

class TranslatedText
{
    /**
     * Get translated text from a translation object.
     *
     * @param array|string|null $tx Translation object or string
     * @return string The translated text
     */
    public static function get_text($tx)
    {
        if (empty($tx)) {
            return '';
        }

        // If it's already a string, return it
        if (is_string($tx)) {
            return $tx;
        }

        // If it's JSON string, decode it
        if (is_string($tx) && self::is_json($tx)) {
            $tx = json_decode($tx, true);
        }

        // Get current language
        $current_language = Common::get_current_language();

        // Try to get the translation for the current language
        if (isset($tx[$current_language])) {
            return $tx[$current_language];
        }

        // If not found, try to get the 'default' translation
        if (isset($tx['default'])) {
            return $tx['default'];
        }

        // If 'default' is not available, fall back to the first available translation
        if (is_array($tx) && !empty($tx)) {
            return reset($tx);
        }

        return '';
    }

    /**
     * Check if a string is valid JSON.
     *
     * @param string $string The string to check
     * @return boolean
     */
    private static function is_json($string)
    {
        if (!is_string($string)) {
            return false;
        }
        json_decode($string);
        return (json_last_error() === JSON_ERROR_NONE);
    }
}
