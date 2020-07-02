<?php


namespace Para\Helpers;

/**
 * Class LoggerHelper
 * @package Para\Helpers
 */
class LoggerHelper
{
    const DATE_TIME_FORMAT = 'Y-m-d H:i:s';
    const TIMEZONE         = 'UTC';

    private static $tzObj;

    private static function getDateTimeZone()
    {
        if (!(static::$tzObj instanceof \DateTimeZone)) {
            static::$tzObj = new \DateTimeZone(static::TIMEZONE);
        }
        return static::$tzObj;
    }

    public static function log($message)
    {
        echo vsprintf(
            "[%s] %s\n",
            [
                (new \DateTime('now', static::getDateTimeZone()))->format(static::DATE_TIME_FORMAT),
                $message
            ]
        );
    }
}
