<?php
namespace app\helpers;

use Yii;

class DateHelper
{
    public static function now()
    {
        return date('Y-m-d H:i:s');
    }

    public static function toIDDate($date)
    {
        setlocale(LC_TIME, 'id_ID.UTF-8');
        return strftime('%d %B %Y', strtotime($date));
    }

    public static function toIDDateTime($date)
    {
        setlocale(LC_TIME, 'id_ID.UTF-8');
        return strftime('%d %B %Y %H:%M:%S', strtotime($date));
    }

	/**
     * Formats date to local timezone format.
     * Converts date string to application's local timezone format.
     * 
     * Usage:
     * ```php
     * $localDate = CoreModel::localDateFormatter('2023-01-01T00:00:00Z');
     * // Returns date in local timezone format from params
     * ```
     * 
     * @param string|null $date Date string to format
     * @return string|null Formatted date or null if input is null
     */
    public static function localDateFormatter($date)
    {
        if ($date === null) {
            return null;
        }

        return (new \DateTime($date))->format(Yii::$app->params['timestamp']['local']);
    }

    /**
     * Formats date to UTC timezone format.
     * Converts date string to UTC timezone format.
     * 
     * Usage:
     * ```php
     * $utcDate = CoreModel::utcDateFormatter('2023-01-01 07:00:00');
     * // Returns date in UTC format from params
     * ```
     * 
     * @param string|null $date Date string to format
     * @return string|null Formatted date or null if input is null
     */
    public static function utcDateFormatter($date)
    {
        if ($date === null) {
            return null;
        }

        return (new \DateTime($date))->format(Yii::$app->params['timestamp']['UTC']);
    }

    /**
     * Converts UTC date string to local timezone format.
     * 
     * @param string|null $date Date string to convert
     * @return string|null Formatted date or null if input is null
     */
    public static function utcToLocalDate($date)
    {
        if ($date === null) {
            return null;
        }

        $dt = new \DateTime($date, new \DateTimeZone('UTC'));
        $dt->setTimezone(new \DateTimeZone(Yii::$app->timeZone));

        return $dt->format(Yii::$app->params['timestamp']['local']);
    }

    /**
     * Converts local date string to UTC timezone format.
     * 
     * @param string|null $date Date string to convert
     * @return string|null Formatted date or null if input is null
     */
    public static function localToUtcDate($date)
    {
        if ($date === null || $date === '') {
            return null;
        }

        // Asumsikan input adalah waktu LOCAL (Asia/Jakarta / app timezone)
        $dt = new \DateTime($date, new \DateTimeZone(Yii::$app->timeZone));

        // Convert ke UTC
        $dt->setTimezone(new \DateTimeZone('UTC'));

        return $dt->format(Yii::$app->params['timestamp']['UTC']);
    }
}
