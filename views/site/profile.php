<?php
/** @var array $forms */
/** @var array $fields */
/** @var array $userData */
$this->registerJsFile('https://code.jquery.com/jquery-3.6.0.min.js');
?>

<!-- Модальное окно -->
<div id="formModal" style="display:none; position:fixed; top:10%; left:20%; background:#fff; padding:20px; border:1px solid #ccc; z-index:1000;">
    <form id="dynamicForm">
        <div id="modalFields"></div>
        <input type="hidden" name="form_id" id="modalFormId">
        <button type="submit">Сохранить</button>
        <button type="button" id="closeModal">Отмена</button>
    </form>
</div>

<!-- Основной вывод -->
<?php foreach ($forms as $form): ?>
    <div class="form-block" style="margin-bottom: 30px;">
        <h3>
            <?= htmlspecialchars($form->form_name) ?>
            <button class="add-field-btn" data-form="<?= $form->id ?>">Добавить</button>
        </h3>

        <?php
        $formFields = array_filter($fields, fn($f) => $f->form_id === $form->id);

        $fieldDataMap = [];
        $maxCount = 0;

        foreach ($formFields as $field) {
            $fieldId = $field->id;
            $fieldName = $field->field_name;
            $values = $userData[$fieldId] ?? [];

            $fieldDataMap[$fieldName] = $values;
            $maxCount = max($maxCount, count($values));
        }

        for ($i = 0; $i < $maxCount; $i++):
            $row = [];
            $rowData = [];

            foreach ($formFields as $field) {
                $fieldId = $field->id;
                $value = $userData[$fieldId][$i] ?? 'Нет данных';
                $row[] = $value;
                $rowData[$fieldId] = $value;
            }

            echo '<div style="margin-bottom:10px;">' .
                implode(' - ', $row) .
                ' <button class="edit-field-btn" data-form="' . $form->id . '" data-index="' . $i . '" data-values=\'' . json_encode($rowData) . '\'>✎</button>' .
                '</div>';
        endfor;

        ?>
    </div>
<?php endforeach; ?>


<!-- Скрипт -->
<?php
$this->registerJs("
    $('.add-field-btn').on('click', function() {
        var formId = $(this).data('form');
        $('#modalFormId').val(formId);

        $.get('/site/get-form-fields', {form_id: formId}, function(data) {
            $('#modalFields').html('');
            for (var i = 0; i < data.length; i++) {
                var field = data[i];
                var html = '<div>' +
                               '<label>' + field.field_name + '</label><br/>' +
                               '<input type=\"text\" name=\"fields[' + field.id + ']\" />' +
                           '</div>';
                $('#modalFields').append(html);
            }
            $('#formModal').show();
        });
    });

    $('#closeModal').on('click', function() {
        $('#formModal').hide();
    });

    $('#dynamicForm').on('submit', function(e) {
        e.preventDefault();
        $.post('/site/save-form-data', $(this).serialize(), function(response) {
            if (response.status === 'success') {
                alert(response.message); // Успех
                location.reload(); // Перезагружаем страницу после сохранения
            } else {
                alert('Ошибка: ' + response.message); // Ошибка
            }
        }).fail(function() {
            alert('Ошибка при отправке данных');
        });
    });
");
?>
