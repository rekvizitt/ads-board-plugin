<?php
/**
 * Settings accessor for Ads Board
 * Provides static methods to get plugin settings from anywhere in the code.
 *
 * @package Ads_Board
 * @subpackage Ads_Board/includes
 */

if (!defined("ABSPATH")) {
    exit();
}

class Ads_Settings
{
    private static $instance = null;
    private $options = null;
    private $option_name = "ads_board_options";

    private function __construct()
    {
        $this->load_options();
    }

    private function __clone() {}

    public function __wakeup()
    {
        throw new Exception("Cannot unserialize singleton");
    }

    public static function get_instance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function load_options()
    {
        $defaults = $this->get_defaults();
        $saved = get_option($this->option_name, []);
        $this->options = wp_parse_args($saved, $defaults);
    }

    public function get_defaults()
    {
        return [
            "ads_per_page" => 12,
            "date_format" => "relative",
            "show_views_count" => 1,
            "show_author" => 1,
            "grid_columns" => "3",
            "image_size" => "medium",
            "require_moderation" => 0,
            "auto_expire_days" => 30,
            "max_images_per_ad" => 10,
            "enable_schema" => 1,
            "enable_ajax_filters" => 1,
        ];
    }

    public function get($key, $default = null)
    {
        if ($this->options === null) {
            $this->load_options();
        }
        return $this->options[$key] ?? $default;
    }

    public function get_all()
    {
        if ($this->options === null) {
            $this->load_options();
        }
        return $this->options;
    }

    public function reset_to_defaults()
    {
        delete_option($this->option_name);
        $this->load_options();
        return true;
    }
}

// Функция-обёртка для удобного использования
if (!function_exists("ads_get_setting")) {
    function ads_get_setting($key, $default = null)
    {
        return Ads_Settings::get_instance()->get($key, $default);
    }
}

if (!function_exists("ads_get_all_settings")) {
    function ads_get_all_settings()
    {
        return Ads_Settings::get_instance()->get_all();
    }
}
