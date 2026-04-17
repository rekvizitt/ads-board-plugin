/**
 * Admin scripts for Ads Board
 * @package Ads_Board
 */
(function ($) {
  "use strict";

  $(document).ready(function () {
    // ✅ Чекбокс "Выбрать все"
    $("#ads-select-all").on("change", function () {
      const isChecked = $(this).prop("checked");
      $('input[name="ads_ids[]"]').prop("checked", isChecked);
    });

    // ✅ Деселект "Выбрать все" при снятии галочки с элемента
    $('input[name="ads_ids[]"]').on("change", function () {
      if (!$(this).prop("checked")) {
        $("#ads-select-all").prop("checked", false);
      }
      // Если все выбраны — ставим галочку в "Выбрать все"
      const total = $('input[name="ads_ids[]"]').length;
      const checked = $('input[name="ads_ids[]"]:checked').length;
      if (total > 0 && total === checked) {
        $("#ads-select-all").prop("checked", true);
      }
    });

    // ✅ Подтверждение массового удаления
    $("#ads-list-form").on("submit", function (e) {
      const bulkAction = $('select[name="ads_bulk_action"]').val();
      if (bulkAction === "delete") {
        const checkedCount = $('input[name="ads_ids[]"]:checked').length;
        if (
          checkedCount > 0 &&
          !confirm(
            adsBoardAdmin.i18n.bulkDeleteConfirm.replace("%d", checkedCount),
          )
        ) {
          e.preventDefault();
        }
      }
    });

    // ✅ Live-поиск с задержкой (опционально)
    let searchTimeout;
    $('input[name="s"]').on("input", function () {
      clearTimeout(searchTimeout);
      const $this = $(this);
      searchTimeout = setTimeout(function () {
        // Можно добавить AJAX-поиск здесь
        // console.log('Searching for:', $this.val());
      }, 300);
    });
  });
})(jQuery);
