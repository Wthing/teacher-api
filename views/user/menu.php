<div id="modal-edit" style="display:none;">
    <form id="edit-form">
        <input type="hidden" name="form_id" id="form_id">
        <input type="hidden" name="field_id" id="field_id">
        <div id="autocomplete-container"></div>
        <input type="text" name="value" id="field_value" class="form-control">
        <button type="submit">Сохранить</button>
    </form>
</div>

<script>
$('.edit-btn').on('click', function () {
    const fieldId = $(this).data('field');
    const formId = $(this).data('form');
    const value = $(this).data('value');

    $('#form_id').val(formId);
    $('#field_id').val(fieldId);
    $('#field_value').val(value);

    $.get('/form/autocomplete-options', { field_id: fieldId }, function (data) {
        if (data.length > 0) {
            let select = '<select name="value">';
            data.forEach(opt => {
                select += `<option value="${opt.content}">${opt.content}</option>`;
            });
            select += '</select>';
            $('#autocomplete-container').html(select);
            $('#field_value').hide();
        } else {
            $('#autocomplete-container').html('');
            $('#field_value').show();
        }

        $('#modal-edit').show();
    });
});
</script>
