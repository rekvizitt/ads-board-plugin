<?php
/**
 * Класс для работы с базой данных
 *
 * @package Ads_Board
 */

if (!defined("ABSPATH")) {
    exit();
}

class Ads_Board_Database
{
    private $prefix;
    private $table_ads;
    private $table_categories;
    private $table_settings;
    private $table_images;
    private $table_views;
    public function __construct()
    {
        global $wpdb;
        $this->prefix = $wpdb->prefix;

        // Определяем названия таблиц
        $this->table_ads = $this->prefix . "ads_board_ads";
        $this->table_categories = $this->prefix . "ads_board_categories";
        $this->table_settings = $this->prefix . "ads_board_settings";
        $this->table_images = $this->prefix . "ads_board_images";
        $this->table_views = $this->prefix . "ads_board_views";
    }
    public function create_tables()
    {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        require_once ABSPATH . "wp-admin/includes/upgrade.php";

        // Создаем таблицу категорий
        $sql_categories = "CREATE TABLE IF NOT EXISTS {$this->table_categories} (
               id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
               name varchar(255) NOT NULL,
               slug varchar(255) NOT NULL,
               description text DEFAULT NULL,
               parent_id bigint(20) UNSIGNED DEFAULT 0,
               sort_order int(11) DEFAULT 0,
               created_at datetime DEFAULT CURRENT_TIMESTAMP,
               updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
               PRIMARY KEY (id),
               UNIQUE KEY slug (slug),
               KEY parent_id (parent_id),
               KEY sort_order (sort_order)
           ) $charset_collate;";

        dbDelta($sql_categories);

        // Создаем таблицу объявлений
        $sql_ads = "CREATE TABLE IF NOT EXISTS {$this->table_ads} (
               id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
               title varchar(255) NOT NULL,
               slug varchar(255) NOT NULL,
               description longtext NOT NULL,
               price decimal(10,2) DEFAULT NULL,
               author_name varchar(255) NOT NULL,
               author_phone varchar(50) DEFAULT NULL,
               author_email varchar(100) DEFAULT NULL,
               category_id bigint(20) UNSIGNED NOT NULL,
               status varchar(20) DEFAULT 'active',
               is_pinned tinyint(1) DEFAULT 0,
               is_important tinyint(1) DEFAULT 0,
               views_count bigint(20) UNSIGNED DEFAULT 0,
               published_at datetime DEFAULT NULL,
               expires_at datetime DEFAULT NULL,
               created_at datetime DEFAULT CURRENT_TIMESTAMP,
               updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
               PRIMARY KEY (id),
               UNIQUE KEY slug (slug),
               KEY category_id (category_id),
               KEY status (status),
               KEY is_pinned (is_pinned),
               KEY is_important (is_important),
               KEY published_at (published_at),
               KEY expires_at (expires_at),
               KEY views_count (views_count)
           ) $charset_collate;";

        dbDelta($sql_ads);

        // Создаем таблицу изображений
        $sql_images = "CREATE TABLE IF NOT EXISTS {$this->table_images} (
               id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
               ad_id bigint(20) UNSIGNED NOT NULL,
               file_path varchar(500) NOT NULL,
               file_name varchar(255) NOT NULL,
               file_type varchar(50) DEFAULT NULL,
               file_size bigint(20) DEFAULT NULL,
               is_primary tinyint(1) DEFAULT 0,
               sort_order int(11) DEFAULT 0,
               created_at datetime DEFAULT CURRENT_TIMESTAMP,
               PRIMARY KEY (id),
               KEY ad_id (ad_id),
               KEY is_primary (is_primary),
               KEY sort_order (sort_order),
               FOREIGN KEY (ad_id) REFERENCES {$this->table_ads}(id) ON DELETE CASCADE
           ) $charset_collate;";

        dbDelta($sql_images);

        // Создаем таблицу настроек
        $sql_settings = "CREATE TABLE IF NOT EXISTS {$this->table_settings} (
               id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
               option_name varchar(191) NOT NULL,
               option_value longtext DEFAULT NULL,
               autoload varchar(20) DEFAULT 'yes',
               created_at datetime DEFAULT CURRENT_TIMESTAMP,
               updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
               PRIMARY KEY (id),
               UNIQUE KEY option_name (option_name),
               KEY autoload (autoload)
           ) $charset_collate;";

        dbDelta($sql_settings);

        // Создаем таблицу статистики просмотров
        $sql_views = "CREATE TABLE IF NOT EXISTS {$this->table_views} (
               id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
               ad_id bigint(20) UNSIGNED NOT NULL,
               user_ip varchar(45) DEFAULT NULL,
               user_agent text DEFAULT NULL,
               viewed_at datetime DEFAULT CURRENT_TIMESTAMP,
               PRIMARY KEY (id),
               KEY ad_id (ad_id),
               KEY user_ip (user_ip),
               KEY viewed_at (viewed_at),
               FOREIGN KEY (ad_id) REFERENCES {$this->table_ads}(id) ON DELETE CASCADE
           ) $charset_collate;";

        dbDelta($sql_views);

        // Сохраняем версию БД
        update_option("ads_board_db_version", ADS_BOARD_VERSION);
    }
    public function insert_default_settings()
    {
        global $wpdb;

        $default_settings = [
            [
                "option_name" => "ads_per_page",
                "option_value" => "12",
            ],
            [
                "option_name" => "default_sort",
                "option_value" => "date_desc",
            ],
            [
                "option_name" => "date_format",
                "option_value" => "relative",
            ],
            [
                "option_name" => "show_views_count",
                "option_value" => "1",
            ],
            [
                "option_name" => "show_publish_date",
                "option_value" => "1",
            ],
            [
                "option_name" => "grid_columns",
                "option_value" => "3",
            ],
            [
                "option_name" => "max_images",
                "option_value" => "10",
            ],
            [
                "option_name" => "max_image_size",
                "option_value" => "5",
            ],
            [
                "option_name" => "allowed_image_types",
                "option_value" => "jpg,jpeg,png,webp",
            ],
            [
                "option_name" => "default_expiry_days",
                "option_value" => "30",
            ],
            [
                "option_name" => "enable_moderation",
                "option_value" => "0",
            ],
            [
                "option_name" => "require_image",
                "option_value" => "0",
            ],
            [
                "option_name" => "excerpt_length",
                "option_value" => "150",
            ],
            [
                "option_name" => "gallery_type",
                "option_value" => "slider",
            ],
            [
                "option_name" => "enable_breadcrumbs",
                "option_value" => "1",
            ],
        ];

        foreach ($default_settings as $setting) {
            // Проверяем, не существует ли уже эта настройка
            $exists = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM {$this->table_settings} WHERE option_name = %s",
                    $setting["option_name"],
                ),
            );

            if (!$exists) {
                $wpdb->insert($this->table_settings, $setting, ["%s", "%s"]);
            }
        }
    }
    public function insert_sample_categories()
    {
        global $wpdb;

        $sample_categories = [
            [
                "name" => "Недвижимость",
                "slug" => "real-estate",
                "description" => "Продажа и аренда недвижимости",
                "sort_order" => 1,
            ],
            [
                "name" => "Транспорт",
                "slug" => "transport",
                "description" => "Автомобили, мотоциклы и другой транспорт",
                "sort_order" => 2,
            ],
            [
                "name" => "Электроника",
                "slug" => "electronics",
                "description" => "Телефоны, компьютеры, бытовая техника",
                "sort_order" => 3,
            ],
            [
                "name" => "Работа",
                "slug" => "jobs",
                "description" => "Вакансии и резюме",
                "sort_order" => 4,
            ],
            [
                "name" => "Услуги",
                "slug" => "services",
                "description" => "Различные услуги",
                "sort_order" => 5,
            ],
        ];

        foreach ($sample_categories as $category) {
            // Проверяем, не существует ли уже эта категория
            $exists = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT COUNT(*) FROM {$this->table_categories} WHERE slug = %s",
                    $category["slug"],
                ),
            );

            if (!$exists) {
                $wpdb->insert($this->table_categories, $category, [
                    "%s",
                    "%s",
                    "%s",
                    "%d",
                ]);
            }
        }
    }
    public function drop_tables()
    {
        global $wpdb;

        // Удаляем таблицы в правильном порядке (из-за foreign keys)
        $wpdb->query("DROP TABLE IF EXISTS {$this->table_views}");
        $wpdb->query("DROP TABLE IF EXISTS {$this->table_images}");
        $wpdb->query("DROP TABLE IF EXISTS {$this->table_ads}");
        $wpdb->query("DROP TABLE IF EXISTS {$this->table_categories}");
        $wpdb->query("DROP TABLE IF EXISTS {$this->table_settings}");

        // Удаляем опцию версии БД
        delete_option("ads_board_db_version");
    }
    public function tables_exist()
    {
        global $wpdb;

        $table = $wpdb->get_var("SHOW TABLES LIKE '{$this->table_ads}'");

        return $table === $this->table_ads;
    }
    public function get_ads_table()
    {
        return $this->table_ads;
    }
    public function get_categories_table()
    {
        return $this->table_categories;
    }
    public function get_settings_table()
    {
        return $this->table_settings;
    }
    public function get_images_table()
    {
        return $this->table_images;
    }
    public function get_views_table()
    {
        return $this->table_views;
    }
}
