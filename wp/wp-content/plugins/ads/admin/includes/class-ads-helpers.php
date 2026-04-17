<?php
/**
 * Helper functions for Ads Board
 *
 * @package Ads_Board
 * @subpackage Ads_Board/admin/includes
 */

if (!defined("ABSPATH")) {
    exit();
}

class Ads_Helpers
{
    /**
     * Конфигурация статусов объявлений
     */
    public static function get_status_config($status)
    {
        $configs = [
            "active" => [
                "label" => "✅ " . __("Активно", "ads-board"),
                "class" => "status-active",
                "color" => "#155724",
                "bg" => "#d4edda",
            ],
            "draft" => [
                "label" => "📝 " . __("Черновик", "ads-board"),
                "class" => "status-draft",
                "color" => "#856404",
                "bg" => "#fff3cd",
            ],
            "expired" => [
                "label" => "⏰ " . __("Истекло", "ads-board"),
                "class" => "status-expired",
                "color" => "#721c24",
                "bg" => "#f8d7da",
            ],
            "sold" => [
                "label" => "💰 " . __("Продано", "ads-board"),
                "class" => "status-sold",
                "color" => "#004085",
                "bg" => "#cce5ff",
            ],
        ];
        return $configs[$status] ?? [
            "label" => $status,
            "class" => "",
            "color" => "#333",
            "bg" => "#e0e0e0",
        ];
    }

    /**
     * Форматирование даты с учётом настроек
     */
    public static function format_date($datetime, $format = null)
    {
        if (!$datetime) {
            return "—";
        }
        $timestamp = is_numeric($datetime) ? $datetime : strtotime($datetime);
        return date_i18n($format ?: get_option("date_format"), $timestamp);
    }
}
