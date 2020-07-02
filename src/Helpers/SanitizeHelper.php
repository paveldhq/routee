<?php


namespace Para\Helpers;

/**
 * Class SanitizeHelper
 * @package Para\Helpers
 */
class SanitizeHelper
{

    public static function sanitizeString($inputString)
    {
        if (!is_string($inputString)) {
            $inputString = (string)$inputString;
        }

        return trim($inputString);
    }
}
