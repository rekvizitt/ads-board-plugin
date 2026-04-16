<?php
/**
 * Публичная часть плагина
 *
 * @package Ads_Board
 */

if (!defined("ABSPATH")) {
    exit();
}

class Ads_Board_Public
{
    private $plugin_name;
    private $version;
    public function __construct($plugin_name, $version)
    {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }
    public function enqueue_styles()
    {
        wp_enqueue_style(
            $this->plugin_name,
            ADS_BOARD_PLUGIN_URL . "assets/css/public.css",
            [],
            $this->version,
            "all",
        );
    }
    public function enqueue_scripts()
    {
        wp_enqueue_script(
            $this->plugin_name,
            ADS_BOARD_PLUGIN_URL . "assets/js/public.js",
            ["jquery"],
            $this->version,
            false,
        );
    }
    public function register_shortcodes()
    {
        add_shortcode("latest_ads", [$this, "latest_ads_shortcode"]);
    }
    public function latest_ads_shortcode($atts)
    {
        // Пока заглушка
        return '<div class="ads-board-shortcode">Список объявлений будет здесь</div>';
    }
    public function register_rewrite_rules()
    {
        // Пока заглушка, реализуем позже
    }
}
