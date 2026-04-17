<?php
/**
 * Template: Add/Edit Ad Form
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
function ads_get_field($key, $default = "")
{
    global $old, $item;
    if (isset($old[$key]) && $old[$key] !== "") {
        return $old[$key];
    }
    if ($item && property_exists($item, $key) && $item->$key !== null) {
        return $item->$key;
    }
    return $default;
}

$is_edit = !empty($item);
$page_title = $is_edit ? "Редактировать объявление" : "Добавить объявление";
$submit_text = $is_edit ? "Сохранить изменения" : "Опубликовать объявление";
?>

<div class="wrap ads-form-wrap">
    <h1 class="wp-heading-inline"><?php echo esc_html($page_title); ?></h1>
    <a href="<?php echo admin_url(
        "admin.php?page=ads-board",
    ); ?>" class="page-title-action">Назад к списку</a>

    <!-- Блок ошибок валидации -->
    <?php if (!empty($errors)): ?>
        <div class="notice notice-error is-dismissible">
            <p><strong>Исправьте ошибки в форме:</strong></p>
            <ul class="ads-error-list">
                <?php foreach ($errors as $field => $message):
                    $labels = [
                        "title" => "Заголовок",
                        "description" => "Описание",
                        "price" => "Цена",
                        "author_name" => "ФИО автора",
                        "author_phone" => "Телефон",
                        "author_email" => "Email",
                        "contacts" => "Контакты",
                        "category_id" => "Категория",
                    ]; ?>
                    <li><strong><?php echo esc_html(
                        $labels[$field] ?? $field,
                    ); ?>:</strong> <?php echo esc_html($message); ?></li>
                <?php
                endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php settings_errors("ads_items"); ?>

    <form method="post" enctype="multipart/form-data" id="ads-form" class="ads-form">
        <?php wp_nonce_field("ads_items_nonce", "ads_items_nonce_field"); ?>
        <input type="hidden" name="ads_action" value="<?php echo esc_attr(
            $form_action,
        ); ?>">
        <?php if ($item): ?>
            <input type="hidden" name="id" value="<?php echo esc_attr(
                $item->id,
            ); ?>">
        <?php endif; ?>

        <div class="ads-form-layout">

            <!-- Левая колонка: основные поля -->
            <div class="ads-form-main">

                <!-- Заголовок -->
                <div class="ads-form-section">
                    <h3>Основная информация</h3>
                    <div class="ads-form-field">
                        <label for="ad_title">Заголовок <span class="ads-required">*</span></label>
                        <input type="text" name="title" id="ad_title" class="large-text"
                               value="<?php echo esc_attr(
                                   ads_get_field("title"),
                               ); ?>"
                               placeholder="Например: Продам велосипед" required>
                        <?php if (isset($errors["title"])): ?>
                            <span class="ads-field-error"><?php echo esc_html(
                                $errors["title"],
                            ); ?></span>
                        <?php endif; ?>
                        <p class="ads-field-help">Краткое и понятное название объявления</p>
                    </div>

                    <div class="ads-form-field">
                        <label for="ad_slug">Ярлык (slug)</label>
                        <input type="text" name="slug" id="ad_slug" class="regular-text code"
                               value="<?php echo esc_attr(
                                   ads_get_field("slug"),
                               ); ?>"
                               placeholder="auto-generated">
                        <p class="ads-field-help">URL объявления. Оставьте пустым для автогенерации</p>
                    </div>
                </div>

                <!-- Описание -->
                <div class="ads-form-section">
                    <h3>Описание <span class="ads-required">*</span></h3>
                    <div class="ads-form-field">
                        <?php wp_editor(
                            ads_get_field("description", ""),
                            "ad_description",
                            [
                                "textarea_name" => "description",
                                "textarea_rows" => 8,
                                "media_buttons" => false,
                                "teeny" => false,
                                "quicktags" => [
                                    "buttons" => "strong,em,ul,ol,li",
                                ],
                            ],
                        ); ?>
                        <?php if (isset($errors["description"])): ?>
                            <span class="ads-field-error"><?php echo esc_html(
                                $errors["description"],
                            ); ?></span>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Цена и контакты -->
                <div class="ads-form-section">
                    <h3>Цена и контакты</h3>

                    <div class="ads-form-row">
                        <div class="ads-form-field">
                            <label for="ad_price">Цена ($)</label>
                            <input type="number" name="price" id="ad_price" class="small-text" step="0.01" min="0"
                                   value="<?php echo esc_attr(
                                       ads_get_field("price", ""),
                                   ); ?>"
                                   placeholder="0.00">
                            <?php if (isset($errors["price"])): ?>
                                <span class="ads-field-error"><?php echo esc_html(
                                    $errors["price"],
                                ); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="ads-form-field">
                        <label for="ad_author">ФИО автора <span class="ads-required">*</span></label>
                        <input type="text" name="author_name" id="ad_author" class="regular-text"
                               value="<?php echo esc_attr(
                                   ads_get_field("author_name"),
                               ); ?>" required>
                        <?php if (isset($errors["author_name"])): ?>
                            <span class="ads-field-error"><?php echo esc_html(
                                $errors["author_name"],
                            ); ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="ads-form-row">
                        <div class="ads-form-field">
                            <label for="ad_phone">Телефон</label>
                            <input type="tel" name="author_phone" id="ad_phone" class="regular-text"
                                   value="<?php echo esc_attr(
                                       ads_get_field("author_phone"),
                                   ); ?>"
                                   placeholder="+375 (29) 123-45-67">
                            <?php if (isset($errors["author_phone"])): ?>
                                <span class="ads-field-error"><?php echo esc_html(
                                    $errors["author_phone"],
                                ); ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="ads-form-field">
                            <label for="ad_email">Email</label>
                            <input type="email" name="author_email" id="ad_email" class="regular-text"
                                   value="<?php echo esc_attr(
                                       ads_get_field("author_email"),
                                   ); ?>"
                                   placeholder="email@example.com">
                            <?php if (isset($errors["author_email"])): ?>
                                <span class="ads-field-error"><?php echo esc_html(
                                    $errors["author_email"],
                                ); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php if (isset($errors["contacts"])): ?>
                        <span class="ads-field-error"><?php echo esc_html(
                            $errors["contacts"],
                        ); ?></span>
                    <?php endif; ?>
                    <p class="ads-field-help">Укажите хотя бы телефон или email</p>
                </div>

                <!-- Публикация -->
                <div class="ads-form-section">
                    <h3>Публикация</h3>

                    <div class="ads-form-field">
                        <label for="ad_category">Категория <span class="ads-required">*</span></label>
                        <select name="category_id" id="ad_category" class="regular-text" required>
                            <option value="">— Выберите категорию —</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo esc_attr(
                                    $cat->id,
                                ); ?>"
                                        <?php selected(
                                            ads_get_field("category_id"),
                                            $cat->id,
                                        ); ?>>
                                    <?php echo esc_html($cat->name); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if (isset($errors["category_id"])): ?>
                            <span class="ads-field-error"><?php echo esc_html(
                                $errors["category_id"],
                            ); ?></span>
                        <?php endif; ?>
                    </div>

                    <div class="ads-form-row">
                        <div class="ads-form-field">
                            <label for="ad_published">Дата публикации</label>
                            <input type="datetime-local" name="published_at" id="ad_published"
                                   value="<?php
                                   $val = ads_get_field("published_at");
                                   echo $val
                                       ? esc_attr(
                                           date("Y-m-d\TH:i", strtotime($val)),
                                       )
                                       : "";
                                   ?>">
                            <p class="ads-field-help">Оставьте пустым для публикации сразу</p>
                        </div>
                        <div class="ads-form-field">
                            <label for="ad_expires">Дата окончания</label>
                            <input type="datetime-local" name="expires_at" id="ad_expires"
                                   value="<?php
                                   $val = ads_get_field("expires_at");
                                   echo $val
                                       ? esc_attr(
                                           date("Y-m-d\TH:i", strtotime($val)),
                                       )
                                       : "";
                                   ?>">
                            <?php if (isset($errors["expires_at"])): ?>
                                <span class="ads-field-error"><?php echo esc_html(
                                    $errors["expires_at"],
                                ); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="ads-form-field">
                        <label for="ad_status">Статус</label>
                        <select name="status" id="ad_status" class="regular-text">
                            <option value="draft" <?php selected(
                                ads_get_field("status", "draft"),
                                "draft",
                            ); ?>>
                                Черновик
                            </option>
                            <option value="active" <?php selected(
                                ads_get_field("status", "draft"),
                                "active",
                            ); ?>>
                                Опубликовано
                            </option>
                            <option value="sold" <?php selected(
                                ads_get_field("status", "draft"),
                                "sold",
                            ); ?>>
                                Продано
                            </option>
                        </select>
                    </div>
                </div>

            </div>

            <!-- Правая колонка: галерея и настройки -->
            <div class="ads-form-sidebar">

                <!-- Галерея -->
                <div class="ads-form-section ads-form-box">
                    <h3>Изображения</h3>

                    <?php if (!empty($gallery)): ?>
                        <div class="ads-gallery-preview">
                            <?php foreach ($gallery as $img): ?>
                                <div class="ads-gallery-item" data-id="<?php echo esc_attr(
                                    $img["id"],
                                ); ?>">
                                    <img src="<?php echo esc_url(
                                        home_url($img["file_path"]),
                                    ); ?>"
                                         alt="<?php echo esc_attr(
                                             $img["file_name"],
                                         ); ?>">
                                    <div class="ads-gallery-actions">
                                        <?php if (!$img["is_primary"]): ?>
                                            <button type="button" class="ads-set-primary" title="Сделать главным">★</button>
                                        <?php else: ?>
                                            <span class="ads-is-primary" title="Главное изображение">★</span>
                                        <?php endif; ?>
                                        <a href="<?php echo wp_nonce_url(
                                            admin_url(
                                                "admin.php?page=ads-add-new&action=delete_image&img_id=" .
                                                    $img["id"] .
                                                    "&ad_id=" .
                                                    ($item->id ?? 0),
                                            ),
                                            "ads_items_nonce",
                                        ); ?>"
                                           class="ads-delete-image"
                                           onclick="return confirm('Удалить изображение?');"
                                           title="Удалить">×</a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <div class="ads-upload-area">
                        <input type="file" name="ad_images[]" id="ad_images" multiple accept="image/*" hidden>
                        <label for="ad_images" class="ads-upload-label">
                            <span class="dashicons dashicons-plus-alt"></span>
                            <span>Добавить фото</span>
                        </label>
                        <p class="ads-upload-help">JPG, PNG, GIF. Макс. 5 МБ каждое.</p>
                    </div>
                    <div id="ads-upload-preview"></div>
                </div>

                <!-- Дополнительные настройки -->
                <div class="ads-form-section ads-form-box">
                    <h3>Настройки</h3>

                    <label class="ads-checkbox">
                        <input type="checkbox" name="is_pinned" value="1" <?php checked(
                            ads_get_field("is_pinned"),
                            1,
                        ); ?>>
                        <span>Закрепить вверху списка</span>
                    </label>

                    <label class="ads-checkbox">
                        <input type="checkbox" name="is_important" value="1" <?php checked(
                            ads_get_field("is_important"),
                            1,
                        ); ?>>
                        <span>Отметить как важное</span>
                    </label>
                </div>

                <!-- Кнопки -->
                <div class="ads-form-actions">
                    <button type="submit" class="button button-primary button-large" id="ads-submit-btn">
                        <?php echo esc_html($submit_text); ?>
                    </button>
                    <?php if ($is_edit): ?>
                        <a href="<?php echo wp_nonce_url(
                            admin_url(
                                "admin.php?page=ads-board&action=delete&ad_id=" .
                                    $item->id,
                            ),
                            "delete_ad_" . $item->id,
                        ); ?>"
                           class="button button-link-delete"
                           onclick="return confirm('Удалить объявление?');"
                           style="margin-top: 10px; display: block; text-align: center;">
                            Удалить объявление
                        </a>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </form>
</div>

<!-- Стили и скрипты -->
<?php
// Выводим инлайн-скрипты для простоты (можно вынести в ads-admin.js)
?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Автогенерация slug из заголовка
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


    // Установка главного изображения (через редирект)
    document.querySelectorAll('.ads-set-primary').forEach(btn => {
        btn.addEventListener('click', function() {
            const imgId = this.closest('.ads-gallery-item').dataset.id;
            const adId = <?php echo $item ? (int) $item->id : 0; ?>;
            if (!adId) { alert('Сначала сохраните объявление'); return; }
            window.location.href = '<?php echo admin_url(
                "admin.php?page=ads-add-new",
            ); ?>' +
                '&action=set_primary&img_id=' + imgId + '&ad_id=' + adId +
                '&ads_items_nonce_field=<?php echo wp_create_nonce(
                    "ads_items_nonce",
                ); ?>';
        });
    });

    // Валидация: хотя бы один контакт
    const phone = document.getElementById('ad_phone');
    const email = document.getElementById('ad_email');
    const form = document.getElementById('ads-form');

    if (phone && email && form) {
        form.addEventListener('submit', function(e) {
            if (!phone.value.trim() && !email.value.trim()) {
                e.preventDefault();
                alert('Укажите телефон или email');
                phone.focus();
            }
        });
    }
});
</script>
