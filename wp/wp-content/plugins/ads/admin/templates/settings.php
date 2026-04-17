<?php
/**
 * Template: Plugin Settings Page
 * @package Ads_Board
 */

if (!defined("ABSPATH")) {
    exit();
} ?>

<div class="wrap ads-board-settings">
    <h1>Настройки доски объявлений</h1>

    <?php settings_errors("ads_board_settings"); ?>

    <form method="post" action="options.php" class="ads-settings-form">
        <?php settings_fields("ads_board_settings"); ?>

        <!-- Вкладки -->
        <div class="ads-settings-tabs">
            <button type="button" class="ads-tab-button active" data-tab="general">Общие</button>
            <button type="button" class="ads-tab-button" data-tab="display">Отображение</button>
            <button type="button" class="ads-tab-button" data-tab="moderation">Модерация</button>
            <button type="button" class="ads-tab-button" data-tab="advanced">Дополнительно</button>
        </div>

        <!-- Контент вкладок: оборачиваем секции -->
        <div class="ads-settings-content">
            <?php
            // Получаем секции вручную, чтобы обернуть их
            global $wp_settings_sections;
            $page = "ads_board_settings_page";

            if (isset($wp_settings_sections[$page])) {
                foreach ($wp_settings_sections[$page] as $section) {
                    $section_id = $section["id"]; ?>
                       <div id="settings-section-<?php echo esc_attr(
                           $section_id,
                       ); ?>" class="settings-section">
                           <?php if ($section["title"]): ?>
                               <h2 class="title"><?php echo esc_html(
                                   $section["title"],
                               ); ?></h2>
                           <?php endif; ?>

                           <?php if ($section["callback"]): ?>
                               <?php call_user_func(
                                   $section["callback"],
                                   $section,
                               ); ?>
                           <?php endif; ?>

                           <!-- ✅ ОБЯЗАТЕЛЬНО оборачиваем в table + tbody -->
                           <table class="form-table">
                               <tbody>
                                   <?php do_settings_fields(
                                       $page,
                                       $section_id,
                                   ); ?>
                               </tbody>
                           </table>
                       </div>
                       <?php
                }
            }
            ?>
        </div>

        <p class="submit ads-submit-actions">
            <?php submit_button(
                "Сохранить изменения",
                "primary",
                "submit",
                false,
            ); ?>
            <a href="<?php echo admin_url(
                "admin.php?page=ads-board",
            ); ?>" class="button ads-cancel-button">Отмена</a>
        </p>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const tabs = document.querySelectorAll('.ads-tab-button');
    const sections = document.querySelectorAll('.settings-section');

    tabs.forEach(function(tab) {
        tab.addEventListener('click', function() {
            const target = this.dataset.tab;

            tabs.forEach(function(t) { t.classList.remove('active'); });
            sections.forEach(function(s) { s.classList.remove('active'); });

            this.classList.add('active');
            const targetSection = document.getElementById('settings-section-' + target);
            if (targetSection) {
                targetSection.classList.add('active');
            }
        });
    });

    // Активация первой вкладки
    if (tabs[0] && sections[0]) {
        tabs[0].click();
    }
});
</script>
