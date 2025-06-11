<?php

use app\models\Form;
use app\models\FormConfirmPerson;
use app\models\FormFieldAutocomplete;
use yii\helpers\Html;
use yii\helpers\Json;

/** @var Form[] $forms */
/** @var int $profileId */
/** @var array $userData */
/** @var int $unreadRequestsCount */
/** @var FormConfirmPerson $accessGranted */

function isValidUrl($url) {
    return filter_var($url, FILTER_VALIDATE_URL) !== false;
}

$autocompleteMap = [];
$allAutocompleteRows = FormFieldAutocomplete::find()->all();
foreach ($allAutocompleteRows as $entry) {
    $autocompleteMap[$entry->field_id][] = $entry->content;
}
?>

<div class="container mt-3">
    <?php if (in_array($profileId, $accessGranted)): ?>
        <?php if ($unreadRequestsCount > 0): ?>
            <div class="mb-3 text-end">
                <a href="/confirm-application/" class="btn btn-outline-danger position-relative">
                    🔔
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                    <?= $unreadRequestsCount ?>
                    <span class="visually-hidden">непрочитанные заявки</span>
                </span>
                </a>
            </div>
        <?php else: ?>
            <div class="mb-3 text-end">
                <a href="/confirm-application/" class="btn btn-outline-secondary">
                    🔔
                </a>
            </div>
        <?php endif; ?>
    <?php endif; ?>



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

        <h3 class="mt-4"><?= Html::encode($form->form_name) ?></h3>

        <div class="col-md-12 text-right mb-3">
            <button class="btn btn-success btn-sm create-field-btn"
                    data-form="<?= $form->id ?>"
                    data-fields='<?= Json::encode($formFields) ?>'>
                ➕ Добавить новую запись
            </button>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle">
                <thead class="table-light">
                <tr>
                    <?php foreach ($formFields as $field): ?>
                        <th><?= Html::encode($field->field_name) ?></th>
                    <?php endforeach; ?>
                    <th>Действия</th>
                </tr>
                </thead>
                <tbody>
                <?php for ($i = 0; $i < $maxCount; $i++): ?>
                    <?php
                    $rowData = [];
                    $recordIds = [];
                    ?>
                    <tr>
                        <?php foreach ($formFields as $field): ?>
                            <?php
                            $fieldId = $field->id;
                            $value = $fieldDataMap[$fieldId][$i]['data'] ?? $fieldDataMap[$fieldId][$i] ?? '';
                            $displayValue = '';

                            if ($field->type_id == 5 && is_string($value) && $value !== '') {
                                $ext = strtolower(pathinfo($value, PATHINFO_EXTENSION));
                                $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'];

                                if (in_array($ext, $imageExtensions)) {
                                    $url = Yii::getAlias('@web') . '/' . ltrim($value, '/');
                                    Yii::info('image extension: ' . $url);
                                    $displayValue = Html::img($url, [
                                        'style' => 'max-width: 120px; height: auto; border-radius: 4px;',
                                        'alt' => basename($value),
                                        'class' => 'img-thumbnail'
                                    ]);
                                } elseif (in_array($ext, ['pdf', 'doc', 'docx', 'xls', 'xlsx'])) {
                                    $url = Yii::getAlias('@web') . '/uploads/previews/' . ltrim($value, '/uploads');
                                    Yii::info('image pdf: ' . $url);

                                    $previewUrl = rtrim($url, '.' . $ext) . '.jpg';
                                    Yii::info('image extension pdf: ' . $previewUrl);
                                    $documentUrl = '/' . ltrim($value, '/');




                                    $displayValue = Html::a(
                                        Html::img($previewUrl, [
                                            'style' => 'max-width: 120px; height: auto; border-radius: 4px;',
                                            'alt' => basename($value),
                                            'class' => 'img-thumbnail',
                                        ]),
                                        $documentUrl,
                                        [
                                            'target' => '_blank',
                                            'title' => 'Открыть документ: ' . basename($value),
                                        ]
                                    );
                                }
                                else {
                                    $displayValue = Html::a(
                                        basename($value),
                                        '/' . ltrim($value, '/'), // <-- абсолютный путь от корня
                                        ['target' => '_blank']
                                    );

                                }
                            } else {
                                if (is_string($value) && isValidUrl($value)) {
                                    $displayValue = Html::a(Html::encode($value), $value, [
                                        'target' => '_blank',
                                        'rel' => 'noopener noreferrer'
                                    ]);
                                } elseif (is_array($value)) {
                                    $displayValue = Html::encode(implode(', ', $value));
                                } else {
                                    $displayValue = Html::encode($value);
                                }
                            }

                            $rowData[$fieldId] = $value;

                            if (isset($fieldDataMap[$fieldId][$i]['id'])) {
                                $recordIds[] = $fieldDataMap[$fieldId][$i]['id'];
                            }
                            ?>
                            <td><?= $displayValue ?></td>
                        <?php endforeach; ?>

                        <td class="text-end">
                            <button class="btn btn-warning btn-sm edit-field-btn"
                                    data-form="<?= $form->id ?>"
                                    data-ids='<?= Json::encode($recordIds) ?>'
                                    data-values='<?= Json::encode($rowData) ?>'
                                    data-fields='<?= Json::encode($formFields) ?>'>
                                ✎
                            </button>
                            <button class="btn btn-danger btn-sm delete-field-btn"
                                    data-ids='<?= Json::encode($recordIds) ?>'
                                    name="<?= Yii::$app->request->csrfParam ?>"
                                    value="<?= Yii::$app->request->getCsrfToken() ?>">
                                🗑
                            </button>
                        </td>
                    </tr>
                <?php endfor; ?>
                </tbody>
            </table>
        </div>
    <?php endforeach; ?>


    <!-- Edit Modal -->
<div class="modal fade" id="editFieldModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" role="dialog" aria-labelledby="editFieldModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <form id="editForm" method="post" enctype="multipart/form-data" action="/site/update-form-data">
                <div class="modal-header">
                    <h5 class="modal-title">Редактировать данные</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="modalFieldsContainer"></div>

                <input type="hidden" name="form_id" id="modalFormId">

                <!-- Here we will dynamically add hidden inputs for record_ids -->
                <div id="modalRecordIdsContainer"></div>

                <input type="hidden" name="<?= Yii::$app->request->csrfParam ?>" value="<?= Yii::$app->request->getCsrfToken() ?>">

                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Сохранить</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Create Modal -->
<div class="modal fade" id="createFieldModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" role="dialog" aria-labelledby="createFieldModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <form id="createForm" method="post" enctype="multipart/form-data" action="/site/create-form-data">
                <div class="modal-header">
                    <h5 class="modal-title">Создать новую запись</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="createFieldsContainer"></div>
                <input type="hidden" name="form_id" id="createFormId">
                <input type="hidden" name="<?= Yii::$app->request->csrfParam ?>" value="<?= Yii::$app->request->getCsrfToken() ?>">
                <div class="modal-footer">
                    <button type="submit" class="btn btn-success">Создать</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                </div>
            </form>
        </div>
    </div>
</div>


<?php
$csrfToken = Yii::$app->request->getCsrfToken();
$csrfParam = Yii::$app->request->csrfParam;

$autocompleteJson = Json::encode($autocompleteMap);

$js = <<<JS
var autocompleteOptionsMap = $autocompleteJson;

$('.edit-field-btn').on('click', function () {
    var formId = $(this).data('form');
    var recordIds = $(this).data('ids'); 
    var fieldValues = $(this).data('values'); 
    var formFields = $(this).data('fields'); 

    $('#modalFormId').val(formId);

    var container = $('#modalFieldsContainer');
    var recordIdsContainer = $('#modalRecordIdsContainer');

    container.empty();
    recordIdsContainer.empty();

    
    for (let i = 0; i < formFields.length; i++) {
        let fieldId = formFields[i].id;
        let recordId = recordIds[i] || '';

        var hiddenInput = $('<input>')
            .attr('type', 'hidden')
            .attr('name', 'record_ids[' + fieldId + ']')
            .val(recordId);

        recordIdsContainer.append(hiddenInput);
    }

    formFields.forEach(function(field) {
        var value = fieldValues[field.id] || '';

        if (autocompleteOptionsMap[field.id] !== undefined && autocompleteOptionsMap[field.id].length > 0) {
            var select = $('<select>').addClass('form-control').attr('name', 'field_values[' + field.id + ']');
            select.append($('<option>').val('').text('->'));
            autocompleteOptionsMap[field.id].forEach(function(opt) {
                var option = $('<option>').val(opt).text(opt);
                if (opt === value) option.prop('selected', true);
                select.append(option);
            });
            var formGroup = $('<div>').addClass('form-group mb-3');
            formGroup.append($('<label>').text(field.label));
            formGroup.append(select);
            container.append(formGroup);
        } else {
            var input = generateInputByType(field.type_id, field.id, value);
            var formGroup = $('<div>').addClass('form-group mb-3');
            formGroup.append($('<label>').text(field.label));
            formGroup.append(input);
            container.append(formGroup);
        }
    });

    var modal = new bootstrap.Modal(document.getElementById('editFieldModal'));
    modal.show();
});

$('.create-field-btn').on('click', function () {
    var formId = $(this).data('form');
    var formFields = $(this).data('fields');

    $('#createFormId').val(formId);

    var container = $('#createFieldsContainer');
    container.empty();

    formFields.forEach(function(field) {
        if (autocompleteOptionsMap[field.id] !== undefined && autocompleteOptionsMap[field.id].length > 0) {
            var select = $('<select>').addClass('form-control').attr('name', 'field_values[' + field.id + ']');
            select.append($('<option>').val('').text('->'));
            autocompleteOptionsMap[field.id].forEach(function(opt) {
                select.append($('<option>').val(opt).text(opt));
            });

            var formGroup = $('<div>').addClass('form-group mb-3');
            formGroup.append($('<label>').text(field.field_name));
            formGroup.append(select);
            container.append(formGroup);

        } else {
            var input = generateInputByType(field.type_id, field.id, '');
            var formGroup = $('<div>').addClass('form-group mb-3');
            formGroup.append($('<label>').text(field.field_name));
            formGroup.append(input);
            container.append(formGroup);
        }
    });

    var modal = new bootstrap.Modal(document.getElementById('createFieldModal'));
    modal.show();
});

$('.delete-field-btn').on('click', function () {
    if (!confirm('Вы уверены, что хотите удалить эту запись?')) return;

    var recordIds = $(this).data('ids');

    $.ajax({
        url: '/site/delete-form-data',
        type: 'POST',
        data: {
            data_ids: JSON.stringify(recordIds),
            '$csrfParam': '$csrfToken'
        },
        success: function () {
            location.reload();
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
            input = $('<input>').attr('type', 'number').addClass('form-control').attr('name', 'field_values[' + fieldId + ']').val(value);
            break;
        case 2: // text
            input = $('<input>').attr('type', 'text').addClass('form-control').attr('name', 'field_values[' + fieldId + ']').val(value);
            break;
        case 3: // date
            input = $('<input>').attr('type', 'date').addClass('form-control').attr('name', 'field_values[' + fieldId + ']').val(value);
            break;
        case 4: // url
            input = $('<input>').attr('type', 'url').addClass('form-control').attr('name', 'field_values[' + fieldId + ']').val(value);
            break;
        case 5: // file upload
            input = $('<input>').attr('type', 'file').addClass('form-control').attr('name', 'field_files[' + fieldId + ']');

            if (typeof value === 'string' && value.length > 0) {
                const fileUrl = '/' + value.replace(/^\/+/, '');
                const fileName = fileUrl.split('/').pop();

                // Detect image extensions
                const ext = fileName.split('.').pop().toLowerCase();
                const imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'];

                let preview;

                if (imageExtensions.includes(ext)) {
                    preview = $('<img>').attr('src', fileUrl).css({'max-height': '100px', 'margin-top': '5px'});
                } else {
                    preview = $('<a>').attr('href', fileUrl).attr('target', '_blank').text(fileName).css({'display': 'block', 'margin-top': '5px'});
                }

                input.after(preview);
            }
            break;
        default:
            input = $('<input>').attr('type', 'text').addClass('form-control').attr('name', 'field_values[' + fieldId + ']').val(value);
    }

    return input;
}
JS;

$this->registerJs($js);
?>
