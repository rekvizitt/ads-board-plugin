<?php
/**
 * The core plugin class.
 *
 * This is used to define admin-specific hooks, and public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      0.0.1
 * @package    Ads_Board
 * @subpackage Ads_Board/includes
 * @author     Vladislav Chekaviy
 */

class Ads
{
    protected $loader;
    protected $plugin_name;
    protected $version;
    public function __construct()
    {
        if (defined("PLUGIN_NAME_VERSION")) {
            $this->version = PLUGIN_NAME_VERSION;
        } else {
            $this->version = "0.0.1";
        }
        $this->plugin_name = "ads-board";
        $this->load_dependencies();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }
    private function load_dependencies()
    {
        require_once plugin_dir_path(dirname(__FILE__)) .
            "includes/class-ads-loader.php";
        require_once plugin_dir_path(dirname(__FILE__)) .
            "admin/class-ads-admin.php";
        require_once plugin_dir_path(dirname(__FILE__)) .
            "public/class-ads-public.php";
        $this->loader = new Ads_Loader();
    }
    private function define_admin_hooks()
    {
        $plugin_admin = new Ads_Admin(
            $this->get_plugin_name(),
            $this->get_version(),
        );

        $this->loader->add_action(
            "admin_enqueue_scripts",
            $plugin_admin,
            "enqueue_styles",
        );
        $this->loader->add_action(
            "admin_enqueue_scripts",
            $plugin_admin,
            "enqueue_scripts",
        );
    }
    private function define_public_hooks()
    {
        $plugin_public = new Ads_Public(
            $this->get_plugin_name(),
            $this->get_version(),
        );

        $this->loader->add_action(
            "wp_enqueue_scripts",
            $plugin_public,
            "enqueue_styles",
        );
        $this->loader->add_action(
            "wp_enqueue_scripts",
            $plugin_public,
            "enqueue_scripts",
        );
    }
    public function run()
    {
        $this->loader->run();
    }
    public function get_plugin_name()
    {
        return $this->plugin_name;
    }
    public function get_loader()
    {
        return $this->loader;
    }
    public function get_version()
    {
        return $this->version;
    }
}
