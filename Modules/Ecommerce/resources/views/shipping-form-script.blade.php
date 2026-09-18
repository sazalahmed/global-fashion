<script>
'use strict';
$(function () {
  // Filter districts by name as user types
  $('#districtFilter').on('input', function () {
    var q = $(this).val().toLowerCase().trim();
    $('#districtsList .district-item').each(function () {
      var name = $(this).data('name') || '';
      $(this).toggle(name.indexOf(q) !== -1);
    });
  });

  // Select-all-visible respects current filter and skips disabled rows
  $('#selectAllVisible').on('click', function () {
    $('#districtsList .district-item:visible .district-checkbox:not(:disabled)').prop('checked', true);
  });

  $('#clearAll').on('click', function () {
    $('#districtsList .district-checkbox:not(:disabled)').prop('checked', false);
  });
});
</script>
