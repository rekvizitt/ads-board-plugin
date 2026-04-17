<?php
/**
 * Database.
 *
 * @since      0.0.1
 * @package    Ads_Board
 * @subpackage Ads_Board/includes
 * @author     Vladislav Chekaviy
 */

class Ads_Database
{
    public static function create_tables()
    {
        global $wpdb;

        $table_categories = $wpdb->prefix . "ads_categories";
        $table_ads = $wpdb->prefix . "ads";
        $table_images = $wpdb->prefix . "ads_images";
        $table_settings = $wpdb->prefix . "ads_settings";

        $charset_collate = $wpdb->get_charset_collate();

        $sql_categories = "CREATE TABLE IF NOT EXISTS $table_categories (
                    id bigint(20) NOT NULL AUTO_INCREMENT,
                    name varchar(255) NOT NULL,
                    slug varchar(255) NOT NULL,
                    description text,
                    sort_order int(11) DEFAULT 0,
                    created_at datetime DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (id),
                    UNIQUE KEY slug (slug)
                ) $charset_collate;";

        $sql_ads = "CREATE TABLE IF NOT EXISTS $table_ads (
                           id bigint(20) NOT NULL AUTO_INCREMENT,
                           title varchar(255) NOT NULL,
                           slug varchar(255) NOT NULL,
                           description longtext NOT NULL,
                           price decimal(10,2) DEFAULT NULL,
                           author_name varchar(255) NOT NULL,
                           author_phone varchar(50),
                           author_email varchar(100),
                           category_id bigint(20) NOT NULL,
                           status varchar(20) DEFAULT 'active',
                           is_pinned tinyint(1) DEFAULT 0,
                           is_important tinyint(1) DEFAULT 0,
                           views_count bigint(20) DEFAULT 0,
                           published_at datetime,
                           expires_at datetime,
                           created_at datetime DEFAULT CURRENT_TIMESTAMP,
                           PRIMARY KEY (id),
                           UNIQUE KEY slug (slug),
                           KEY category_id (category_id),
                           KEY status (status)
                       ) $charset_collate;";

        $sql_images = "CREATE TABLE IF NOT EXISTS $table_images (
                           id bigint(20) NOT NULL AUTO_INCREMENT,
                           ad_id bigint(20) NOT NULL,
                           file_path varchar(500) NOT NULL,
                           file_name varchar(255) NOT NULL,
                           is_primary tinyint(1) DEFAULT 0,
                           sort_order int(11) DEFAULT 0,
                           created_at datetime DEFAULT CURRENT_TIMESTAMP,
                           PRIMARY KEY (id),
                           KEY ad_id (ad_id)
                       ) $charset_collate;";

        $sql_settings = "CREATE TABLE IF NOT EXISTS $table_settings (
                           id bigint(20) NOT NULL AUTO_INCREMENT,
                           option_name varchar(191) NOT NULL,
                           option_value longtext,
                           PRIMARY KEY (id),
                           UNIQUE KEY option_name (option_name)
                       ) $charset_collate;";

        require_once ABSPATH . "wp-admin/includes/upgrade.php";
        dbDelta($sql_categories);
        dbDelta($sql_ads);
        dbDelta($sql_images);
        dbDelta($sql_settings);

        // Индексы для таблицы ads (после создания таблицы)
        $wpdb->query("
            ALTER TABLE {$table_ads}
            ADD INDEX idx_status_published (status, published_at, expires_at),
            ADD INDEX idx_category_status (category_id, status),
            ADD INDEX idx_pinned_important (is_pinned, is_important, created_at),
            ADD INDEX idx_price (price)
        ");

        // Индексы для таблицы images
        $wpdb->query("
            ALTER TABLE {$table_images}
            ADD INDEX idx_ad_primary (ad_id, is_primary)
        ");
    }
    public static function insert_default_settings()
    {
        global $wpdb;
        $table = $wpdb->prefix . "ads_settings";

        $settings = [
            ["option_name" => "ads_per_page", "option_value" => "12"],
            ["option_name" => "date_format", "option_value" => "relative"],
            ["option_name" => "show_views_count", "option_value" => "1"],
            ["option_name" => "grid_columns", "option_value" => "3"],
            ["option_name" => "max_images", "option_value" => "10"],
        ];

        foreach ($settings as $setting) {
            $exists = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM $table WHERE option_name = %s",
                    $setting["option_name"],
                ),
            );

            if (!$exists) {
                $wpdb->insert($table, $setting);
            }
        }
    }
    public static function insert_sample_categories()
    {
        global $wpdb;
        $table = $wpdb->prefix . "ads_categories";

        $categories = [
            [
                "name" => "Недвижимость",
                "slug" => "real-estate",
                "sort_order" => 1,
            ],
            ["name" => "Транспорт", "slug" => "transport", "sort_order" => 2],
            [
                "name" => "Электроника",
                "slug" => "electronics",
                "sort_order" => 3,
            ],
            ["name" => "Работа", "slug" => "jobs", "sort_order" => 4],
            ["name" => "Услуги", "slug" => "services", "sort_order" => 5],
        ];

        foreach ($categories as $category) {
            $exists = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM $table WHERE slug = %s",
                    $category["slug"],
                ),
            );

            if (!$exists) {
                $wpdb->insert($table, $category);
            }
        }
    }
}
