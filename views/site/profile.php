<?php
use yii\helpers\Html;
use yii\helpers\Json;

/** @var \app\models\Form[] $forms */
?>

<div class="container mt-3">
    <?php foreach ($forms as $form): ?>
        <?php
        $formFields = $form->formFields;
        $fieldDataMap = [];

        foreach ($formFields as $field) {
            $fieldDataMap[$field->id] = $userData[$field->id] ?? [];
        }

        $maxCount = 0;
        foreach ($fieldDataMap as $dataRows) {
            $maxCount = max($maxCount, count($dataRows));
        }
        ?>

        <h3 class="mt-4">Данные формы: <?= Html::encode($form->form_name) ?></h3>

        <!-- Кнопка "Добавить новую запись" -->
        <div class="col-md-12 text-right mb-3">
            <button class="btn btn-success btn-sm create-field-btn"
                    data-form="<?= $form->id ?>"
                    data-fields='<?= Json::encode($formFields) ?>'>
                ➕ Добавить новую запись
            </button>
        </div>

        <?php for ($i = 0; $i < $maxCount; $i++): ?>
            <?php
            $rowDisplay = [];
            $rowData = [];
            $recordIds = [];

            foreach ($formFields as $field) {
                $fieldId = $field->id;
                $value = $fieldDataMap[$fieldId][$i]['data'] ?? $fieldDataMap[$fieldId][$i] ?? '';
                $rowDisplay[] = Html::encode(is_array($value) ? implode(', ', $value) : $value);
                $rowData[$fieldId] = $value;

                if (isset($fieldDataMap[$fieldId][$i]['id'])) {
                    $recordIds[] = $fieldDataMap[$fieldId][$i]['id'];
                }
            }
            ?>

            <div class="row mb-2">
                <div class="col-md-12">
                    <?= implode(' - ', $rowDisplay) ?>
                </div>
                <div class="col-md-12 text-right">
                    <button class="btn btn-warning btn-sm edit-field-btn"
                            data-form="<?= $form->id ?>"
                            data-ids='<?= Json::encode($recordIds) ?>'
                            data-values='<?= Json::encode($rowData) ?>'
                            data-fields='<?= Json::encode($formFields) ?>'>
                        ✎
                    </button>
                    <button class="btn btn-danger btn-sm delete-field-btn"
                            data-ids='<?= Json::encode($recordIds) ?>'>
                        🗑
                    </button>

                </div>
            </div>
        <?php endfor; ?>
    <?php endforeach; ?>
</div>

<!-- Модальное окно для редактирования -->
<div class="modal fade" id="editFieldModal" tabindex="-1" role="dialog" aria-labelledby="editFieldModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form id="editForm" method="post" action="/site/update-form-data">
                <div class="modal-header">
                    <h5 class="modal-title" id="editFieldModalLabel">Редактировать данные</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Закрыть">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="modalFieldsContainer">
                    <!-- Поля вставляются через JS -->
                </div>
                <input type="hidden" name="form_id" id="modalFormId">
                <input type="hidden" name="data_ids" id="modalDataIds">
                <input type="hidden" name="<?= Yii::$app->request->csrfParam ?>" value="<?= Yii::$app->request->getCsrfToken() ?>">
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Сохранить</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Отмена</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Модальное окно для создания новой записи -->
<div class="modal fade" id="createFieldModal" tabindex="-1" role="dialog" aria-labelledby="createFieldModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <form id="createForm" method="post" action="/site/create-form-data">
                <div class="modal-header">
                    <h5 class="modal-title" id="createFieldModalLabel">Создать новую запись</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Закрыть">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="createFieldsContainer">
                    <!-- Динамически вставляемые поля -->
                </div>
                <input type="hidden" name="form_id" id="createFormId">
                <input type="hidden" name="<?= Yii::$app->request->csrfParam ?>" value="<?= Yii::$app->request->getCsrfToken() ?>">
                <div class="modal-footer">
                    <button type="submit" class="btn btn-success">Создать</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Отмена</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
$csrfToken = Yii::$app->request->getCsrfToken();
$csrfParam = Yii::$app->request->csrfParam;

$js = <<<JS
$('.edit-field-btn').on('click', function () {
    var formId = $(this).data('form');
    var recordIds = $(this).data('ids');
    var fieldValues = $(this).data('values');
    var formFields = $(this).data('fields');

    $('#modalFormId').val(formId);
    $('#modalDataIds').val(JSON.stringify(recordIds));

    var container = $('#modalFieldsContainer');
    container.empty();

    formFields.forEach(function(field) {
        var value = fieldValues[field.id] || '';
        var input = generateInputByType(field.type_id, field.id, value);

        var formGroup = $('<div>').addClass('form-group');
        formGroup.append($('<label>').text(field.label));
        formGroup.append(input);
        container.append(formGroup);
    });

    $('#editFieldModal').modal('show');
});

$('.create-field-btn').on('click', function () {
    var formId = $(this).data('form');
    var formFields = $(this).data('fields');

    $('#createFormId').val(formId);

    var container = $('#createFieldsContainer');
    container.empty();

    formFields.forEach(function(field) {
        var input = generateInputByType(field.type_id, field.id, '');

        var formGroup = $('<div>').addClass('form-group');
        formGroup.append($('<label>').text(field.field_name));
        formGroup.append(input);
        container.append(formGroup);
    });

    $('#createFieldModal').modal('show');
});

$('.delete-field-btn').on('click', function () {
    if (!confirm('Вы уверены, что хотите удалить эту запись?')) return;

    var recordIds = $(this).data('ids');

    $.ajax({
        url: '/site/delete-form-data',
        type: 'POST',
        data: {
            data_ids: JSON.stringify(recordIds),
            '<?= $csrfParam ?>': '<?= $csrfToken ?>'
        },
        success: function () {
            location.reload(); // перезагружаем страницу
        },
        error: function () {
            alert('Ошибка при удалении данных');
        }
    });
});


function generateInputByType(typeId, fieldId, value) {
    let input;

    switch (typeId) {
        case 1: // integer
            input = $('<input>')
                .attr('type', 'number')
                .addClass('form-control')
                .attr('name', 'field_values[' + fieldId + ']')
                .val(value);
            break;
        case 2: // string
            input = $('<input>')
                .attr('type', 'text')
                .addClass('form-control')
                .attr('name', 'field_values[' + fieldId + ']')
                .val(value);
            break;
        case 3: // date
            input = $('<input>')
                .attr('type', 'date')
                .addClass('form-control')
                .attr('name', 'field_values[' + fieldId + ']')
                .val(value);
            break;
        case 4: // url
            input = $('<input>')
                .attr('type', 'url')
                .addClass('form-control')
                .attr('name', 'field_values[' + fieldId + ']')
                .val(value);
            break;
        case 5: // file
            input = $('<input>')
                .attr('type', 'file')
                .addClass('form-control')
                .attr('name', 'field_values[' + fieldId + ']');
            break;
        default:
            input = $('<input>')
                .attr('type', 'text')
                .addClass('form-control')
                .attr('name', 'field_values[' + fieldId + ']')
                .val(value);
    }

    return input;
}
JS;

$this->registerJs($js);
?>
