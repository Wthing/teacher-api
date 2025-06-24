<?php

use app\models\Form;
use app\models\FormConfirmPerson;
use app\models\FormFieldAutocomplete;
use app\models\User;
use yii\helpers\Html;
use yii\helpers\Json;

/** @var Form[] $forms */
/** @var int $profileId */
/** @var array $userData */
/** @var int $unreadRequestsCount */
/** @var FormConfirmPerson $accessGranted */
/** @var string $bucketUrl */

function isValidUrl($url) {
    return filter_var($url, FILTER_VALIDATE_URL) !== false;
}

$user = User::findOne($profileId);
$autocompleteMap = [];
$allAutocompleteRows = FormFieldAutocomplete::find()->all();
foreach ($allAutocompleteRows as $entry) {
    $autocompleteMap[$entry->field_id][] = $entry->content;
}
?>

    <head>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/core@latest/dist/css/tabler.min.css">
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
        <title>Профиль</title>
    </head>

    <script src="https://cdn.jsdelivr.net/npm/@tabler/core@latest/dist/js/tabler.min.js"></script>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h2 class="fw-bold mb-0">Профиль: <?= Html::encode($user->username) ?></h2>

            <?php if (Yii::$app->user->can('/super-user/*')): ?>
                <?= Html::a(
                    '<i class="ti ti-settings me-1"></i> Перейти в раздел администратора',
                    ['super-user/'],
                    ['class' => 'btn btn-primary']
                ) ?>
            <?php endif; ?>

            <?php if (in_array($profileId, $accessGranted)): ?>
                <a href="/confirm-application/" class="btn btn-outline-<?= $unreadRequestsCount > 0 ? 'danger' : 'secondary' ?> position-relative">
                    <i class="ti ti-bell"></i>
                    <?php if ($unreadRequestsCount > 0): ?>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                        <?= $unreadRequestsCount ?>
                        <span class="visually-hidden">непрочитанные заявки</span>
                    </span>
                    <?php endif; ?>
                </a>
            <?php endif; ?>
        </div>

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

            <div class="card border-0 shadow-sm mb-5">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><?= Html::encode($form->form_name) ?></h5>
                    <button class="btn btn-light btn-sm create-field-btn"
                            data-form="<?= $form->id ?>"
                            data-fields='<?= Json::encode($formFields) ?>'>
                        <i class="ti ti-plus me-1"></i> Добавить запись
                    </button>
                </div>

                <?php if ($maxCount > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle mb-0">
                            <thead class="table-light">
                            <tr>
                                <?php foreach ($formFields as $field): ?>
                                    <th><?= Html::encode($field->field_name) ?></th>
                                <?php endforeach; ?>
                                <th class="text-center">Действия</th>
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
                                            $ext = strtolower(pathinfo(parse_url($value, PHP_URL_PATH), PATHINFO_EXTENSION));
                                            $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'];

                                            if (in_array($ext, $imageExtensions)) {
                                                $displayValue = Html::img($value, [
                                                    'style' => 'max-width: 120px; height: auto; border-radius: 4px;',
                                                    'class' => 'img-thumbnail',
                                                ]);
                                            } elseif (in_array($ext, ['pdf', 'doc', 'docx', 'xls', 'xlsx'])) {
                                                $parsedPath = parse_url($value, PHP_URL_PATH);
                                                $filename = pathinfo($parsedPath, PATHINFO_FILENAME);
                                                $previewKey = 'uploads/previews/' . $filename . '.jpg';

                                                try {
                                                    $previewUrl = Yii::$app->s3->getPresignedUrl($previewKey, '+30 minutes');
                                                } catch (\Throwable $e) {
                                                    Yii::error("Ошибка генерации preview S3 URL: " . $e->getMessage(), 'form');
                                                    $previewUrl = null;
                                                }

                                                Yii::info($previewUrl);
                                                $displayValue = Html::a(
                                                    Html::img($previewUrl, [
                                                        'style' => 'max-width: 120px; height: auto; border-radius: 4px;',
                                                        'class' => 'img-thumbnail',
                                                        'alt' => 'Превью'
                                                    ]),
                                                    $value,
                                                    ['target' => '_blank']
                                                );
                                            } else {
                                                $displayValue = Html::a(basename(parse_url($value, PHP_URL_PATH)), $value, ['target' => '_blank']);
                                            }
                                        } else {
                                            if (is_string($value) && isValidUrl($value)) {
                                                $displayValue = Html::a(Html::encode($value), $value, ['target' => '_blank']);
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

                                    <td class="text-center" style="width: 90px;">
                                        <div class="d-flex justify-content-center align-items-center gap-1" role="group" aria-label="Управление записью">
                                            <button type="button"
                                                    class="btn btn-outline-warning edit-field-btn"
                                                    data-bs-toggle="tooltip"
                                                    style="height:32px; width:32px; padding:0;"
                                                    data-form="<?= $form->id ?>"
                                                    data-ids='<?= Json::encode($recordIds) ?>'
                                                    data-values='<?= Json::encode($rowData) ?>'
                                                    data-fields='<?= Json::encode($formFields) ?>'>
                                                <i class="ti ti-edit"></i>
                                            </button>

                                            <button type="button"
                                                    class="btn btn-outline-danger delete-field-btn"
                                                    data-bs-toggle="tooltip"
                                                    style="height:32px; width:32px; padding:0;"
                                                    data-ids='<?= Json::encode($recordIds) ?>'
                                                    name="<?= Yii::$app->request->csrfParam ?>"
                                                    value="<?= Yii::$app->request->getCsrfToken() ?>">
                                                <i class="ti ti-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endfor; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="p-4 text-muted">Нет данных для отображения</div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>

        <!-- Модальное окно редактирования -->
        <div class="modal fade" id="editFieldModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" role="dialog" aria-labelledby="editFieldModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" style="max-width: 500px;" role="document">
                <div class="modal-content rounded-4 shadow-sm">
                    <form id="editForm" method="post" enctype="multipart/form-data" action="/site/update-form-data">
                        <div class="modal-header border-0 pb-0">
                            <h5 class="modal-title"><i class="ti ti-edit me-1"></i>Редактировать данные</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
                        </div>

                        <div class="modal-body pt-3" id="modalFieldsContainer" style="max-height: 65vh; overflow-y: auto;"></div>

                        <input type="hidden" name="form_id" id="modalFormId">
                        <div id="modalRecordIdsContainer"></div>
                        <input type="hidden" name="<?= Yii::$app->request->csrfParam ?>" value="<?= Yii::$app->request->getCsrfToken() ?>">

                        <div class="modal-footer border-0 pt-0 d-flex justify-content-between">
                            <button type="submit" class="btn btn-primary px-4"><i class="ti ti-device-floppy me-1"></i>Сохранить</button>
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><i class="ti ti-x me-1"></i>Отмена</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Модальное окно создания -->
        <div class="modal fade" id="createFieldModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" role="dialog" aria-labelledby="createFieldModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" style="max-width: 500px;" role="document">
                <div class="modal-content rounded-4 shadow-sm">
                    <form id="createForm" method="post" enctype="multipart/form-data" action="/site/create-form-data">
                        <div class="modal-header border-0 pb-0">
                            <h5 class="modal-title"><i class="ti ti-plus me-1"></i>Создать новую запись</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Закрыть"></button>
                        </div>

                        <div class="modal-body pt-3" id="createFieldsContainer" style="max-height: 65vh; overflow-y: auto;"></div>

                        <input type="hidden" name="form_id" id="createFormId">
                        <input type="hidden" name="<?= Yii::$app->request->csrfParam ?>" value="<?= Yii::$app->request->getCsrfToken() ?>">

                        <div class="modal-footer border-0 pt-0 d-flex justify-content-between">
                            <button type="submit" class="btn btn-success px-4"><i class="ti ti-check me-1"></i>Создать</button>
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal"><i class="ti ti-x me-1"></i>Отмена</button>
                        </div>
                    </form>
                </div>
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

$csrfParam = Yii::$app->request->csrfParam;
$csrfToken = Yii::$app->request->getCsrfToken();

$this->registerJs(<<<JS
$('.delete-field-btn').on('click', function () {
    if (!confirm('Вы уверены, что хотите удалить эту запись?')) return;

    const recordIds = $(this).data('ids');
    $.ajax({
        url: '/site/delete-form-data',
        type: 'POST',
        data: {
            data_ids: JSON.stringify(recordIds),
            '$csrfParam': '$csrfToken'
        },
        success: function (res) {
            if (res.success) {
                location.reload();
            } else {
                alert(res.message || 'Ошибка при удалении');
            }
        },
        error: function () {
            alert('Ошибка при удалении запроса');
        }
    });
});
JS);
?>