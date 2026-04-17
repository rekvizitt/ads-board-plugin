<?php
/**
 * File Uploader for Ads Board
 * Handles image uploads, validation, and gallery management.
 *
 * @package Ads_Board
 * @subpackage Ads_Board/admin/includes
 */

if (!defined("ABSPATH")) {
    exit();
}

class Ads_File_Uploader
{
    private $upload_dir;
    private $allowed_types = [
        "image/jpeg",
        "image/png",
        "image/gif",
        "image/webp",
    ];
    private $max_size = 5 * MB_IN_BYTES; // 5 MB
    private $max_files = 10;

    public function __construct()
    {
        $upload = wp_upload_dir();
        $this->upload_dir = $upload["basedir"] . "/ads-board/";

        // Создаём папку, если нет
        if (!file_exists($this->upload_dir)) {
            wp_mkdir_p($this->upload_dir);
        }
    }

    /**
     * Обработка загрузки файлов из $_FILES
     */
    public function handle_upload($input_name = "ad_images")
    {
        if (
            !isset($_FILES[$input_name]) ||
            empty($_FILES[$input_name]["name"][0])
        ) {
            return ["success" => true, "files" => []];
        }

        $files = $_FILES[$input_name];
        $uploaded = [];
        $errors = [];

        // Поддерживаем множественную загрузку
        $count = is_array($files["name"]) ? count($files["name"]) : 1;

        for ($i = 0; $i < $count; $i++) {
            $file = [
                "name" => $files["name"][$i] ?? $files["name"],
                "type" => $files["type"][$i] ?? $files["type"],
                "tmp_name" => $files["tmp_name"][$i] ?? $files["tmp_name"],
                "error" => $files["error"][$i] ?? $files["error"],
                "size" => $files["size"][$i] ?? $files["size"],
            ];

            $result = $this->upload_single_file($file);

            if ($result["success"]) {
                $uploaded[] = $result["data"];
            } else {
                $errors[] = $result["error"];
            }
        }

        return [
            "success" => empty($errors),
            "files" => $uploaded,
            "errors" => $errors,
        ];
    }

    /**
     * Загрузка одного файла
     */
    private function upload_single_file($file)
    {
        // Проверка ошибки PHP
        if ($file["error"] !== UPLOAD_ERR_OK) {
            return [
                "success" => false,
                "error" => $this->get_upload_error_msg($file["error"]),
            ];
        }

        // Проверка типа
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file["tmp_name"]);
        finfo_close($finfo);

        if (!in_array($mime, $this->allowed_types, true)) {
            return [
                "success" => false,
                "error" => __(
                    "Недопустимый тип файла. Разрешены: JPG, PNG, GIF, WebP.",
                    "ads-board",
                ),
            ];
        }

        // Проверка размера
        if ($file["size"] > $this->max_size) {
            return [
                "success" => false,
                "error" => sprintf(
                    __("Файл слишком большой. Максимум %d МБ.", "ads-board"),
                    $this->max_size / MB_IN_BYTES,
                ),
            ];
        }

        // Генерация уникального имени
        $filename = wp_unique_filename(
            $this->upload_dir,
            sanitize_file_name($file["name"]),
        );
        $target_path = $this->upload_dir . $filename;
        $relative_path = "/wp-content/uploads/ads-board/" . $filename;

        // Перемещение файла
        if (!move_uploaded_file($file["tmp_name"], $target_path)) {
            return [
                "success" => false,
                "error" => __("Ошибка при сохранении файла.", "ads-board"),
            ];
        }

        // Получение размеров изображения
        $image_data = @getimagesize($target_path);

        return [
            "success" => true,
            "data" => [
                "file_name" => $filename,
                "file_path" => $relative_path,
                "mime_type" => $mime,
                "file_size" => $file["size"],
                "width" => $image_data[0] ?? 0,
                "height" => $image_data[1] ?? 0,
            ],
        ];
    }

    /**
     * Текст ошибки загрузки
     */
    private function get_upload_error_msg($code)
    {
        $errors = [
            UPLOAD_ERR_INI_SIZE => __(
                "Файл превышает upload_max_filesize в php.ini.",
                "ads-board",
            ),
            UPLOAD_ERR_FORM_SIZE => __(
                "Файл превышает MAX_FILE_SIZE в форме.",
                "ads-board",
            ),
            UPLOAD_ERR_PARTIAL => __("Файл загружен частично.", "ads-board"),
            UPLOAD_ERR_NO_FILE => __("Файл не был загружен.", "ads-board"),
            UPLOAD_ERR_NO_TMP_DIR => __(
                "Отсутствует временная папка.",
                "ads-board",
            ),
            UPLOAD_ERR_CANT_WRITE => __(
                "Не удалось записать файл на диск.",
                "ads-board",
            ),
            UPLOAD_ERR_EXTENSION => __(
                "Загрузка прервана расширением PHP.",
                "ads-board",
            ),
        ];
        return $errors[$code] ??
            __("Неизвестная ошибка загрузки.", "ads-board");
    }

    /**
     * Удаление файла
     */
    public function delete_file($file_path)
    {
        $full_path = ABSPATH . ltrim($file_path, "/");
        if (file_exists($full_path)) {
            return @unlink($full_path);
        }
        return false;
    }

    /**
     * Получение галереи объявления
     */
    public function get_ad_gallery($ad_id)
    {
        global $wpdb;
        $table = $wpdb->prefix . "ads_images";

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table WHERE ad_id = %d ORDER BY is_primary DESC, sort_order ASC, id ASC",
                $ad_id,
            ),
            ARRAY_A,
        );
    }

    /**
     * Установка главного изображения
     */
    public function set_primary_image($ad_id, $image_id)
    {
        global $wpdb;
        $table = $wpdb->prefix . "ads_images";

        // Снимаем primary со всех
        $wpdb->update($table, ["is_primary" => 0], ["ad_id" => $ad_id]);
        // Ставим на выбранное
        return $wpdb->update($table, ["is_primary" => 1], ["id" => $image_id]);
    }

    /**
     * Удаление изображения из галереи
     */
    public function delete_image($image_id, $ad_id)
    {
        global $wpdb;
        $table = $wpdb->prefix . "ads_images";

        $image = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT file_path FROM $table WHERE id = %d AND ad_id = %d",
                $image_id,
                $ad_id,
            ),
        );

        if (!$image) {
            return false;
        }

        // Удаляем файл
        $this->delete_file($image->file_path);

        // Удаляем запись
        return $wpdb->delete($table, ["id" => $image_id]);
    }
}
