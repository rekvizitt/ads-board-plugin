<?php
/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @since      0.0.1
 * @package    Ads_Board
 * @subpackage Ads_Board/admin
 * @author     Vladislav Chekaviy
 */

class Ads_Public
{
    private $plugin_name;
    private $version;
    private $router;
    public function __construct($plugin_name, $version)
    {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
        if (file_exists(ADS_PLUGIN_DIR . "includes/class-ads-router.php")) {
            require_once ADS_PLUGIN_DIR . "includes/class-ads-router.php";
            $this->router = new Ads_Router();
        }
    }
    public function enqueue_styles()
    {
        wp_enqueue_style(
            $this->plugin_name,
            ADS_PLUGIN_URL . "public/css/ads-public.css",
            [],
            $this->version,
            "all",
        );
    }

    public function enqueue_scripts()
    {
        wp_enqueue_script(
            $this->plugin_name,
            ADS_PLUGIN_URL . "public/js/ads-public.js",
            ["jquery"],
            $this->version,
            true,
        );
    }

    public function template_redirect()
    {
        if ($this->router) {
            $this->router->handle_template_redirect();
        }
    }
}
