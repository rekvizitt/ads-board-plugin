<?php
/**
 * Template: Categories Management (Admin)
 * @package Ads_Board
 */

if (!defined("ABSPATH")) {
    exit();
}

$items = $data["items"] ?? [];
$edit = $data["edit_item"] ?? null;
$errors = $data["errors"] ?? [];
$old = $data["old_input"] ?? [];
$search = $data["search"] ?? "";
$pagination = [
    "total" => $data["total_pages"] ?? 1,
    "current" => $data["current_page"] ?? 1,
    "base_url" =>
        $data["base_url"] ??
        admin_url("admin.php?page=ads-categories&paged=%#%"),
];

// Функция получения значения
function ads_cat_get_field($key, $default = "")
{
    global $old, $edit;
    if (isset($old[$key]) && $old[$key] !== "") {
        return $old[$key];
    }
    if ($edit && property_exists($edit, $key) && $edit->$key !== null) {
        return $edit->$key;
    }
    return $default;
}
?>

<div class="wrap ads-categories-wrap">
    <h1 class="wp-heading-inline">Категории объявлений</h1>

    <?php if (!$edit): ?>
        <button type="button" class="page-title-action" id="ads-show-add-form">Добавить категорию</button>
    <?php else: ?>
        <a href="<?php echo admin_url(
            "admin.php?page=ads-categories",
        ); ?>" class="page-title-action">Назад к списку</a>
    <?php endif; ?>

    <?php settings_errors("ads_categories"); ?>

    <!-- Форма добавления -->
    <?php if (!$edit): ?>
    <div id="ads-add-category-form" class="ads-form-panel ads-hidden">
        <h2>Новая категория</h2>
        <form method="post">
            <?php wp_nonce_field(
                "ads_categories_nonce",
                "ads_categories_nonce_field",
            ); ?>
            <input type="hidden" name="ads_action" value="create">

            <div class="ads-form-grid">
                <div class="ads-form-field">
                    <label for="cat_name">Название <span class="ads-required">*</span></label>
                    <input type="text" name="name" id="cat_name" class="regular-text"
                           value="<?php echo esc_attr(
                               ads_cat_get_field("name"),
                           ); ?>" required>
                    <?php if (isset($errors["name"])): ?>
                        <span class="ads-field-error"><?php echo esc_html(
                            $errors["name"],
                        ); ?></span>
                    <?php endif; ?>
                    <p class="ads-field-help">Например: Недвижимость, Транспорт</p>
                </div>

                <div class="ads-form-field">
                    <label for="cat_slug">Ярлык (slug)</label>
                    <input type="text" name="slug" id="cat_slug" class="regular-text code"
                           value="<?php echo esc_attr(
                               ads_cat_get_field("slug"),
                           ); ?>"
                           placeholder="Автогенерация">
                    <?php if (isset($errors["slug"])): ?>
                        <span class="ads-field-error"><?php echo esc_html(
                            $errors["slug"],
                        ); ?></span>
                    <?php endif; ?>
                    <p class="ads-field-help">Латиницей, без пробелов. Например: real-estate</p>
                </div>

                <div class="ads-form-field">
                    <label for="cat_desc">Описание</label>
                    <textarea name="description" id="cat_desc" class="large-text" rows="3"><?php echo esc_textarea(
                        ads_cat_get_field("description"),
                    ); ?></textarea>
                    <?php if (isset($errors["description"])): ?>
                        <span class="ads-field-error"><?php echo esc_html(
                            $errors["description"],
                        ); ?></span>
                    <?php endif; ?>
                </div>

                <div class="ads-form-field">
                    <label for="cat_sort">Порядок сортировки</label>
                    <input type="number" name="sort_order" id="cat_sort" class="small-text"
                           value="<?php echo esc_attr(
                               ads_cat_get_field("sort_order", "0"),
                           ); ?>" min="0">
                    <p class="ads-field-help">Меньшее число = выше в списке</p>
                </div>
            </div>

            <p class="ads-form-actions">
                <button type="submit" class="button button-primary">Создать категорию</button>
                <button type="button" class="button" id="ads-cancel-add">Отмена</button>
            </p>
        </form>
    </div>
    <?php endif; ?>

    <!-- Форма редактирования -->
    <?php if ($edit): ?>
    <div class="ads-form-panel">
        <h2>Редактирование категории</h2>
        <form method="post">
            <?php wp_nonce_field(
                "ads_categories_nonce",
                "ads_categories_nonce_field",
            ); ?>
            <input type="hidden" name="ads_action" value="update">
            <input type="hidden" name="id" value="<?php echo esc_attr(
                $edit->id,
            ); ?>">

            <div class="ads-form-grid">
                <div class="ads-form-field">
                    <label for="cat_name">Название <span class="ads-required">*</span></label>
                    <input type="text" name="name" id="cat_name" class="regular-text"
                           value="<?php echo esc_attr(
                               ads_cat_get_field("name"),
                           ); ?>" required>
                    <?php if (isset($errors["name"])): ?>
                        <span class="ads-field-error"><?php echo esc_html(
                            $errors["name"],
                        ); ?></span>
                    <?php endif; ?>
                </div>

                <div class="ads-form-field">
                    <label for="cat_slug">Ярлык (slug)</label>
                    <input type="text" name="slug" id="cat_slug" class="regular-text code"
                           value="<?php echo esc_attr(
                               ads_cat_get_field("slug"),
                           ); ?>">
                    <?php if (isset($errors["slug"])): ?>
                        <span class="ads-field-error"><?php echo esc_html(
                            $errors["slug"],
                        ); ?></span>
                    <?php endif; ?>
                </div>

                <div class="ads-form-field">
                    <label for="cat_desc">Описание</label>
                    <textarea name="description" id="cat_desc" class="large-text" rows="3"><?php echo esc_textarea(
                        ads_cat_get_field("description"),
                    ); ?></textarea>
                </div>

                <div class="ads-form-field">
                    <label for="cat_sort">Порядок сортировки</label>
                    <input type="number" name="sort_order" id="cat_sort" class="small-text"
                           value="<?php echo esc_attr(
                               ads_cat_get_field("sort_order", "0"),
                           ); ?>" min="0">
                </div>

                <div class="ads-form-field">
                    <label>Статистика</label>
                    <p class="ads-field-help">
                        <?php
                        global $wpdb;
                        $ads_count = $wpdb->get_var(
                            $wpdb->prepare(
                                "SELECT COUNT(*) FROM {$wpdb->prefix}ads WHERE category_id = %d",
                                $edit->id,
                            ),
                        );
                        printf("В категории %d объявлений", $ads_count);
                        ?>
                    </p>
                </div>
            </div>

            <p class="ads-form-actions">
                <button type="submit" class="button button-primary">Сохранить изменения</button>
                <a href="<?php echo admin_url(
                    "admin.php?page=ads-categories",
                ); ?>" class="button">Отмена</a>
            </p>
        </form>
    </div>
    <?php endif; ?>

    <!-- Список категорий -->
    <?php if (!$edit): ?>
    <form method="get" class="ads-categories-search">
        <input type="hidden" name="page" value="ads-categories">
        <div class="ads-search-row">
            <input type="search" name="s" value="<?php echo esc_attr(
                $search,
            ); ?>"
                   placeholder="Поиск категорий..." class="regular-text">
            <button type="submit" class="button">Найти</button>
            <?php if ($search): ?>
                <a href="<?php echo admin_url(
                    "admin.php?page=ads-categories",
                ); ?>" class="button">Сбросить</a>
            <?php endif; ?>
        </div>
    </form>

    <?php if (!empty($items)): ?>
    <table class="wp-list-table widefat fixed striped ads-categories-table">
        <thead>
            <tr>
                <th scope="col" class="ads-col-id">ID</th>
                <th scope="col">Название</th>
                <th scope="col">Ярлык</th>
                <th scope="col">Описание</th>
                <th scope="col ads-col-sort">Порядок</th>
                <th scope="col ads-col-count">Объявлений</th>
                <th scope="col ads-col-actions">Действия</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $cat):

                $edit_link = admin_url(
                    "admin.php?page=ads-categories&edit=" . $cat->id,
                );
                $delete_link = wp_nonce_url(
                    admin_url(
                        "admin.php?page=ads-categories&action=delete&id=" .
                            $cat->id,
                    ),
                    "ads_categories_nonce",
                    "ads_categories_nonce_field",
                );

                global $wpdb;
                $ads_count = $wpdb->get_var(
                    $wpdb->prepare(
                        "SELECT COUNT(*) FROM {$wpdb->prefix}ads WHERE category_id = %d",
                        $cat->id,
                    ),
                );
                ?>
            <tr>
                <td class="ads-col-id"><?php echo (int) $cat->id; ?></td>
                <td><strong><?php echo esc_html($cat->name); ?></strong></td>
                <td><code><?php echo esc_html($cat->slug); ?></code></td>
                <td><?php echo esc_html(
                    wp_trim_words($cat->description, 25, "…"),
                ); ?></td>
                <td class="ads-col-sort ads-text-center"><?php echo (int) $cat->sort_order; ?></td>
                <td class="ads-col-count ads-text-center">
                    <a href="<?php echo admin_url(
                        "admin.php?page=ads-board&category=" . $cat->id,
                    ); ?>">
                        <?php echo (int) $ads_count; ?>
                    </a>
                </td>
                <td class="ads-col-actions">
                    <div class="row-actions">
                        <a href="<?php echo esc_url(
                            $edit_link,
                        ); ?>">Редактировать</a> |
                        <a href="<?php echo esc_url($delete_link); ?>"
                           onclick="return confirm('Удалить категорию «<?php echo esc_js(
                               $cat->name,
                           ); ?>»?');"
                           class="delete">
                            Удалить
                        </a>
                    </div>
                </td>
            </tr>
            <?php
            endforeach; ?>
        </tbody>
    </table>

    <!-- Пагинация -->
    <?php if ($pagination["total"] > 1): ?>
        <div class="tablenav">
            <div class="tablenav-pages">
                <?php echo paginate_links([
                    "base" => $pagination["base_url"],
                    "format" => "",
                    "prev_text" => "«",
                    "next_text" => "»",
                    "total" => $pagination["total"],
                    "current" => $pagination["current"],
                    "type" => "list",
                    "add_args" => array_filter(["s" => $search ?: null]),
                ]); ?>
            </div>
        </div>
    <?php endif; ?>

    <?php else: ?>
        <div class="card ads-empty-state">
            <p>Категории не найдены.</p>
            <button type="button" class="button button-primary" id="ads-show-add-form">
                Создать первую категорию
            </button>
        </div>
    <?php endif; ?>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const showBtn = document.getElementById('ads-show-add-form');
    const addForm = document.getElementById('ads-add-category-form');
    const cancelBtn = document.getElementById('ads-cancel-add');

    if (showBtn && addForm) {
        showBtn.addEventListener('click', function() {
            addForm.classList.remove('ads-hidden');
            showBtn.classList.add('ads-hidden');
            document.getElementById('cat_name')?.focus();
        });
    }

    if (cancelBtn && addForm && showBtn) {
        cancelBtn.addEventListener('click', function() {
            addForm.classList.add('ads-hidden');
            showBtn.classList.remove('ads-hidden');
        });
    }

    // Автогенерация slug
    const nameInput = document.getElementById('cat_name');
    const slugInput = document.getElementById('cat_slug');

    if (nameInput && slugInput) {
        let slugTouched = false;
        slugInput.addEventListener('focus', function() { slugTouched = this.value.length > 0; });

        nameInput.addEventListener('input', function() {
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
