<?php
/**
 * Template: Add/Edit Ad
 * @package Ads_Board
 */

if (!defined("ABSPATH")) {
    exit();
}

$item = $data["item"] ?? null;
$categories = $data["categories"] ?? [];
$errors = $data["errors"] ?? [];
$old = $data["old_input"] ?? [];
$form_action = $data["form_action"] ?? "create";
$gallery = $item ? $item->gallery ?? [] : [];

// Функция получения значения: приоритет $old > $item > default
function ads_get_field_value($key, $default = "")
{
    global $old, $item;
    if (isset($old[$key]) && $old[$key] !== "") {
        return $old[$key];
    }
    if (
        $item &&
        property_exists($item, $key) &&
        $item->$key !== null &&
        $item->$key !== ""
    ) {
        return $item->$key;
    }
    return $default;
}
?>

<div class="wrap ads-board-add-new">
    <h1 class="wp-heading-inline" style="margin-bottom: 20px;">
        <?php echo $item
            ? "Редактировать объявление"
            : "Добавить объявление"; ?>
    </h1>
    <a href="<?php echo admin_url(
        "admin.php?page=ads-board",
    ); ?>" class="page-title-action">
        К списку
    </a>

    <!-- 🔔 Блок ошибок валидации — ВВЕРХУ, чтобы сразу видно -->
    <?php if (!empty($errors)): ?>
        <div class="notice notice-error" style="margin: 20px 0; padding: 15px;">
            <p><strong>Исправьте ошибки в форме:</strong></p>
            <ul style="margin: 10px 0 0 20px; padding: 0;">
                <?php foreach ($errors as $field => $message):

                    $field_labels = [
                        "title" => "Заголовок",
                        "description" => "Описание",
                        "price" => "Цена",
                        "author_name" => "ФИО автора",
                        "author_phone" => "Телефон",
                        "author_email" => "Email",
                        "contacts" => "Контакты",
                        "category_id" => "Категория",
                        "published_at" => "Дата публикации",
                        "expires_at" => "Дата окончания",
                    ];
                    $label = $field_labels[$field] ?? $field;
                    ?>
                    <li><?php echo esc_html($label); ?>: <?php echo esc_html(
    $message,
); ?></li>
                <?php
                endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <!-- 🔔 Уведомления об успехе -->
    <?php settings_errors("ads_items"); ?>

    <form method="post" enctype="multipart/form-data" class="ads-form">
        <?php wp_nonce_field("ads_items_nonce", "ads_items_nonce_field"); ?>
        <input type="hidden" name="ads_action" value="<?php echo esc_attr(
            $form_action,
        ); ?>">
        <?php if ($item): ?>
            <input type="hidden" name="id" value="<?php echo esc_attr(
                $item->id,
            ); ?>">
        <?php endif; ?>

        <div class="postbox-container" style="max-width: 700px; float: left; width: 70%;">

            <!-- Основная информация -->
            <div class="postbox" style="margin-bottom: 20px;">
                <h2 class="hndle" style="padding: 10px 15px;"><span>Основная информация</span></h2>
                <div class="inside" style="padding: 15px;">
                    <table class="form-table" style="margin: 0;">
                        <tr>
                            <th scope="row" style="padding: 15px 10px 15px 0; width: 150px;">
                                <label for="ad_title">Заголовок <span style="color:#dc3232">*</span></label>
                            </th>
                            <td style="padding: 15px 0;">
                                <input type="text" name="title" id="ad_title" class="large-text"
                                       value="<?php echo esc_attr(
                                           ads_get_field_value("title"),
                                       ); ?>"
                                       style="padding: 8px 12px;" required>
                                <?php if (isset($errors["title"])): ?>
                                    <p class="description" style="color:#dc3232; margin: 5px 0 0;"><?php echo esc_html(
                                        $errors["title"],
                                    ); ?></p>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row" style="padding: 15px 10px 15px 0;">
                                <label for="ad_slug">Ярлык (slug)</label>
                            </th>
                            <td style="padding: 15px 0;">
                                <input type="text" name="slug" id="ad_slug" class="regular-text code"
                                       value="<?php echo esc_attr(
                                           ads_get_field_value("slug"),
                                       ); ?>"
                                       style="padding: 8px 12px;" placeholder="Автогенерация">
                                <p class="description">Латиницей, для ЧПУ. Оставьте пустым для автозаполнения.</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row" style="padding: 15px 10px 15px 0; vertical-align: top;">
                                <label for="ad_description">Описание <span style="color:#dc3232">*</span></label>
                            </th>
                            <td style="padding: 15px 0;">
                                <?php wp_editor(
                                    ads_get_field_value("description", ""),
                                    "ad_description",
                                    [
                                        "textarea_name" => "description",
                                        "textarea_rows" => 10,
                                        "media_buttons" => true,
                                        "teeny" => false,
                                        "quicktags" => true,
                                        "editor_css" =>
                                            "<style>#wp-ad_description-editor-container { padding: 10px; }</style>",
                                    ],
                                ); ?>
                                <?php if (isset($errors["description"])): ?>
                                    <p class="description" style="color:#dc3232; margin: 5px 0 0;"><?php echo esc_html(
                                        $errors["description"],
                                    ); ?></p>
                                <?php endif; ?>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Цена и контакты -->
            <div class="postbox" style="margin-bottom: 20px;">
                <h2 class="hndle" style="padding: 10px 15px;"><span>Цена и контакты</span></h2>
                <div class="inside" style="padding: 15px;">
                    <table class="form-table" style="margin: 0;">
                        <tr>
                            <th scope="row" style="padding: 15px 10px 15px 0; width: 150px;">
                                <label for="ad_price">Цена ($)</label>
                            </th>
                            <td style="padding: 15px 0;">
                                <input type="number" name="price" id="ad_price" class="small-text" step="0.01" min="0"
                                       value="<?php echo esc_attr(
                                           ads_get_field_value("price", ""),
                                       ); ?>"
                                       style="padding: 8px 12px; width: 150px;">
                                <?php if (isset($errors["price"])): ?>
                                    <p class="description" style="color:#dc3232; margin: 5px 0 0;"><?php echo esc_html(
                                        $errors["price"],
                                    ); ?></p>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row" style="padding: 15px 10px 15px 0;">
                                <label for="ad_author">ФИО автора <span style="color:#dc3232">*</span></label>
                            </th>
                            <td style="padding: 15px 0;">
                                <input type="text" name="author_name" id="ad_author" class="regular-text"
                                       value="<?php echo esc_attr(
                                           ads_get_field_value("author_name"),
                                       ); ?>"
                                       style="padding: 8px 12px;" required>
                                <?php if (isset($errors["author_name"])): ?>
                                    <p class="description" style="color:#dc3232; margin: 5px 0 0;"><?php echo esc_html(
                                        $errors["author_name"],
                                    ); ?></p>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row" style="padding: 15px 10px 15px 0;">
                                <label for="ad_phone">Телефон</label>
                            </th>
                            <td style="padding: 15px 0;">
                                <input type="tel" name="author_phone" id="ad_phone" class="regular-text"
                                       value="<?php echo esc_attr(
                                           ads_get_field_value("author_phone"),
                                       ); ?>"
                                       style="padding: 8px 12px;"
                                       placeholder="+375 (29) 123-45-67">
                                <?php if (isset($errors["author_phone"])): ?>
                                    <p class="description" style="color:#dc3232; margin: 5px 0 0;"><?php echo esc_html(
                                        $errors["author_phone"],
                                    ); ?></p>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row" style="padding: 15px 10px 15px 0;">
                                <label for="ad_email">Email</label>
                            </th>
                            <td style="padding: 15px 0;">
                                <input type="email" name="author_email" id="ad_email" class="regular-text"
                                       value="<?php echo esc_attr(
                                           ads_get_field_value("author_email"),
                                       ); ?>"
                                       style="padding: 8px 12px;">
                                <?php if (isset($errors["author_email"])): ?>
                                    <p class="description" style="color:#dc3232; margin: 5px 0 0;"><?php echo esc_html(
                                        $errors["author_email"],
                                    ); ?></p>
                                <?php endif; ?>
                                <?php if (isset($errors["contacts"])): ?>
                                    <p class="description" style="color:#dc3232; margin: 5px 0 0; font-weight: 500;">
                                        <?php echo esc_html(
                                            $errors["contacts"],
                                        ); ?>
                                    </p>
                                <?php endif; ?>
                                <p class="description" style="margin: 5px 0 0;">Укажите хотя бы телефон или email</p>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Публикация -->
            <div class="postbox" style="margin-bottom: 20px;">
                <h2 class="hndle" style="padding: 10px 15px;"><span>Публикация</span></h2>
                <div class="inside" style="padding: 15px;">
                    <table class="form-table" style="margin: 0;">
                        <tr>
                            <th scope="row" style="padding: 15px 10px 15px 0; width: 150px;">
                                <label for="ad_category">Категория <span style="color:#dc3232">*</span></label>
                            </th>
                            <td style="padding: 15px 0;">
                                <select name="category_id" id="ad_category" class="regular-text" required style="padding: 8px 12px; min-width: 250px;">
                                    <option value="">— Выберите категорию —</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo esc_attr(
                                            $cat->id,
                                        ); ?>"
                                                <?php selected(
                                                    ads_get_field_value(
                                                        "category_id",
                                                    ),
                                                    $cat->id,
                                                ); ?>>
                                            <?php echo esc_html($cat->name); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <?php if (isset($errors["category_id"])): ?>
                                    <p class="description" style="color:#dc3232; margin: 5px 0 0;"><?php echo esc_html(
                                        $errors["category_id"],
                                    ); ?></p>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row" style="padding: 15px 10px 15px 0;">
                                <label for="ad_published">Дата публикации</label>
                            </th>
                            <td style="padding: 15px 0;">
                                <input type="datetime-local" name="published_at" id="ad_published"
                                       value="<?php
                                       $val = ads_get_field_value(
                                           "published_at",
                                       );
                                       echo $val
                                           ? esc_attr(
                                               date(
                                                   "Y-m-d\TH:i",
                                                   strtotime($val),
                                               ),
                                           )
                                           : "";
                                       ?>"
                                       style="padding: 8px 12px;">
                                <p class="description">Оставьте пустым для публикации сразу.</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row" style="padding: 15px 10px 15px 0;">
                                <label for="ad_expires">Дата окончания</label>
                            </th>
                            <td style="padding: 15px 0;">
                                <input type="datetime-local" name="expires_at" id="ad_expires"
                                       value="<?php
                                       $val = ads_get_field_value("expires_at");
                                       echo $val
                                           ? esc_attr(
                                               date(
                                                   "Y-m-d\TH:i",
                                                   strtotime($val),
                                               ),
                                           )
                                           : "";
                                       ?>"
                                       style="padding: 8px 12px;">
                                <?php if (isset($errors["expires_at"])): ?>
                                    <p class="description" style="color:#dc3232; margin: 5px 0 0;"><?php echo esc_html(
                                        $errors["expires_at"],
                                    ); ?></p>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row" style="padding: 15px 10px 15px 0;">
                                <label for="ad_status">Статус</label>
                            </th>
                            <td style="padding: 15px 0;">
                                <select name="status" id="ad_status" style="padding: 8px 12px;">
                                    <option value="draft" <?php selected(
                                        ads_get_field_value("status", "draft"),
                                        "draft",
                                    ); ?>>
                                        Черновик
                                    </option>
                                    <option value="active" <?php selected(
                                        ads_get_field_value("status", "draft"),
                                        "active",
                                    ); ?>>
                                        Опубликовано
                                    </option>
                                    <option value="sold" <?php selected(
                                        ads_get_field_value("status", "draft"),
                                        "sold",
                                    ); ?>>
                                        Продано
                                    </option>
                                </select>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

        </div>

        <!-- Галерея и настройки (сайдбар) -->
        <div class="postbox-container" style="float: right; width: 28%;">

            <!-- Галерея -->
            <div class="postbox" style="margin-bottom: 20px;">
                <h2 class="hndle" style="padding: 10px 15px;"><span>Изображения</span></h2>
                <div class="inside" style="padding: 15px;">
                    <?php if (!empty($gallery)): ?>
                        <div class="ads-gallery" style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px; margin-bottom: 15px;">
                            <?php foreach ($gallery as $img): ?>
                                <div class="ads-gallery-item" style="position: relative; border: 2px solid <?php echo $img[
                                    "is_primary"
                                ]
                                    ? "#0073aa"
                                    : "#ddd"; ?>; border-radius: 4px; overflow: hidden;">
                                    <img src="<?php echo esc_url(
                                        home_url($img["file_path"]),
                                    ); ?>"
                                         alt="<?php echo esc_attr(
                                             $img["file_name"],
                                         ); ?>"
                                         style="width: 100%; height: 80px; object-fit: cover;">
                                    <?php if ($img["is_primary"]): ?>
                                        <span style="position: absolute; top: 5px; right: 5px; color: #f0ad4e; background: #fff; border-radius: 50%; padding: 2px; font-size: 14px;">★</span>
                                    <?php endif; ?>
                                    <div style="position: absolute; bottom: 0; left: 0; right: 0; background: rgba(0,0,0,0.6); color: #fff; font-size: 11px; padding: 3px;">
                                        <button type="button" class="button button-small set-primary"
                                                data-id="<?php echo esc_attr(
                                                    $img["id"],
                                                ); ?>"
                                                style="<?php echo $img[
                                                    "is_primary"
                                                ]
                                                    ? "display:none"
                                                    : ""; ?>; width: 100%; border: none; background: transparent; color: #fff; cursor: pointer;">
                                            Главное
                                        </button>
                                        <a href="<?php echo wp_nonce_url(
                                            admin_url(
                                                "admin.php?page=ads-add-new&action=delete_image&img_id=" .
                                                    $img["id"] .
                                                    "&ad_id=" .
                                                    ($item->id ?? 0),
                                            ),
                                            "ads_items_nonce",
                                        ); ?>"
                                           class="button button-small button-link-delete"
                                           style="width: 100%; border: none; background: transparent; color: #fff; text-align: center;"
                                           onclick="return confirm('Удалить изображение?');">
                                            Удалить
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <div id="ads-upload-area" style="border: 2px dashed #ddd; padding: 20px; text-align: center; border-radius: 4px; cursor: pointer;">
                        <input type="file" name="ad_images[]" id="ad_images" multiple accept="image/*" style="display: none;">
                        <label for="ad_images" style="cursor: pointer; display: block;">
                            <span style="font-size: 32px; color: #777; display: block; margin-bottom: 5px;">+</span>
                            Добавить изображения
                        </label>
                        <p class="description" style="margin-top: 10px; font-size: 12px;">
                            JPG, PNG, GIF, WebP. Макс. 5 МБ каждое.
                        </p>
                    </div>
                    <div id="ads-upload-preview" style="margin-top: 10px;"></div>
                </div>
            </div>

            <!-- Дополнительные настройки -->
            <div class="postbox" style="margin-bottom: 20px;">
                <h2 class="hndle" style="padding: 10px 15px;"><span>Настройки</span></h2>
                <div class="inside" style="padding: 15px;">
                    <label style="display: block; margin-bottom: 12px; padding: 8px 0;">
                        <input type="checkbox" name="is_pinned" value="1" <?php checked(
                            ads_get_field_value("is_pinned"),
                            1,
                        ); ?> style="margin-right: 8px;">
                        <strong>Закрепить вверху списка</strong>
                    </label>
                    <label style="display: block; margin-bottom: 12px; padding: 8px 0;">
                        <input type="checkbox" name="is_important" value="1" <?php checked(
                            ads_get_field_value("is_important"),
                            1,
                        ); ?> style="margin-right: 8px;">
                        <strong>Отметить как важное</strong>
                    </label>
                </div>
            </div>

            <!-- Кнопки -->
            <div id="major-publishing-actions" style="background: #fff; border-top: 1px solid #ddd; padding: 15px 20px; margin-top: 20px;">
                <div id="publishing-action">
                    <button type="submit" class="button button-primary button-large" style="padding: 10px 20px;">
                        <?php echo $item ? "Обновить" : "Создать"; ?>
                    </button>
                </div>
                <div class="clear"></div>
            </div>

        </div>
        <div class="clear"></div>
    </form>
</div>

<!-- Скрипты -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Предпросмотр изображений
    const fileInput = document.getElementById('ad_images');
    const preview = document.getElementById('ads-upload-preview');

    if (fileInput && preview) {
        fileInput.addEventListener('change', function(e) {
            preview.innerHTML = '';
            Array.from(e.target.files).forEach(file => {
                if (file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = function(ev) {
                        const div = document.createElement('div');
                        div.style.cssText = 'display: inline-block; margin: 5px; border: 1px solid #ddd; padding: 5px; border-radius: 4px;';
                        div.innerHTML = '<img src="' + ev.target.result + '" style="max-width: 100px; max-height: 80px; border-radius: 3px;"><br><small>' + file.name + '</small>';
                        preview.appendChild(div);
                    };
                    reader.readAsDataURL(file);
                }
            });
        });
    }

    // Установка главного изображения
    document.querySelectorAll('.set-primary').forEach(btn => {
        btn.addEventListener('click', function() {
            const imgId = this.dataset.id;
            const adId = <?php echo $item ? (int) $item->id : 0; ?>;

            if (!adId) {
                alert('Сначала сохраните объявление.');
                return;
            }

            window.location.href = '<?php echo admin_url(
                "admin.php?page=ads-add-new",
            ); ?>' +
                '&action=set_primary&img_id=' + imgId + '&ad_id=' + adId +
                '&ads_items_nonce_field=<?php echo wp_create_nonce(
                    "ads_items_nonce",
                ); ?>';
        });
    });

    // Автогенерация slug
    const titleInput = document.getElementById('ad_title');
    const slugInput = document.getElementById('ad_slug');

    if (titleInput && slugInput && !slugInput.value) {
        let slugTouched = false;
        slugInput.addEventListener('focus', function() { slugTouched = true; });

        titleInput.addEventListener('input', function() {
            if (!slugTouched && !slugInput.value) {
                slugInput.value = this.value.toLowerCase()
                    .replace(/[^\w\s-]/g, '')
                    .replace(/[\s_]+/g, '-')
                    .replace(/^-+|-+$/g, '');
            }
        });
    }
});
</script>

<style>
.ads-board-add-new .required { color: #dc3232; }
.ads-board-add-new .ads-gallery-item:hover { border-color: #0073aa !important; }
#ads-upload-area:hover { border-color: #0073aa; background: #f9f9f9; }
.ads-board-add-new input[type="text"],
.ads-board-add-new input[type="email"],
.ads-board-add-new input[type="tel"],
.ads-board-add-new input[type="number"],
.ads-board-add-new select,
.ads-board-add-new input[type="datetime-local"] {
    padding: 8px 12px;
    min-height: 36px;
}
.ads-board-add-new .form-table th {
    padding: 15px 10px 15px 0 !important;
    width: 150px;
    font-weight: 500;
}
.ads-board-add-new .form-table td {
    padding: 15px 0 !important;
}
.ads-board-add-new .inside {
    padding: 15px !important;
}
.ads-board-add-new .hndle {
    padding: 10px 15px !important;
}
@media screen and (max-width: 1200px) {
    .postbox-container { float: none !important; width: 100% !important; }
}
</style>
