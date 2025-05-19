<?php
use yii\helpers\Html;
use yii\bootstrap5\Modal;
use yii\helpers\Url;

$fieldTypes = \app\models\FormFieldType::find()->all();
$forms = \app\models\Form::find()->all();

$optionsHtml = '';
foreach ($fieldTypes as $fieldType) {
    $optionsHtml .= "<option value=\"{$fieldType->id}\">{$fieldType->type_name}</option>";
}
?>

<div class="container mt-4">
    <h1 class="mb-4">Конструктор форм</h1>

    <div class="mb-4">
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#formModalStep1">
            + Создать новую форму
        </button>
    </div>

    <div class="row">
        <?php foreach ($forms as $form): ?>
            <div class="col-md-4">
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title"><?= Html::encode($form->form_name) ?></h5>
                        <button type="button" class="btn btn-primary toggle-form-btn" data-bs-toggle="modal" data-bs-target="#viewFormModal" data-form="<?= $form->id ?>">
                            Открыть
                        </button>
                        <button type="button" class="btn btn-warning edit-form-btn" data-bs-toggle="modal" data-bs-target="#editFormModal" data-form="<?= $form->id ?>">
                            Редактировать
                        </button>
                        <button type="button"
                                class="btn btn-danger btn-sm delete-form-btn"
                                data-form-id="<?= $form->id ?>">
                            Удалить
                        </button>

                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Модал для просмотра формы -->
<?php Modal::begin([
    'id' => 'viewFormModal',
    'title' => 'Детали формы',
]); ?>
<div class="modal-body">Загрузка...</div>
<?php Modal::end(); ?>

<!-- Модал для редактирования формы -->
<?php Modal::begin([
    'id' => 'editFormModal',
    'title' => 'Редактирование формы',
]); ?>
<form id="editForm" method="post" action="<?= Url::to(['admin/update-form']) ?>">
    <input type="hidden" name="_csrf" value="<?= Yii::$app->request->csrfToken ?>">
    <input type="hidden" name="Form[id]" id="editFormId">

    <div class="mb-3">
        <label for="editFormName" class="form-label">Название формы</label>
        <input type="text" name="Form[form_name]" class="form-control" id="editFormName" required>
    </div>

    <div id="editFieldInputs" class="mb-3">
    </div>

    <button type="button" class="btn btn-outline-secondary btn-sm mb-3" id="editAddField">+ Добавить поле</button>

    <div class="text-end">
        <button type="submit" class="btn btn-success">Сохранить изменения</button>
    </div>
</form>
<?php Modal::end(); ?>

<?php
$js = <<<JS
const optionsHtml = `$optionsHtml`;

let editFieldIndex = 0;

document.getElementById('editAddField').addEventListener('click', () => {
    const container = document.getElementById('editFieldInputs');
    const wrapper = document.createElement('div');
    wrapper.classList.add('mb-2');
    wrapper.innerHTML = `
        <input type="text" name="fields[\${editFieldIndex}][field_name]" class="form-control mb-1" placeholder="Название поля" required>
        <select name="fields[\${editFieldIndex}][type_id]" class="form-control mb-1" required>
            \${optionsHtml}
        </select>
        <button type="button" class="btn btn-sm btn-danger remove-field-btn">Удалить поле</button>
        <hr>
    `;
    container.appendChild(wrapper);
    editFieldIndex++;
});

document.getElementById('editFieldInputs').addEventListener('click', (e) => {
    if(e.target.classList.contains('remove-field-btn')){
        e.target.parentElement.remove();
    }
});

$('.edit-form-btn').on('click', function () {
    const formId = $(this).data('form');
    editFieldIndex = 0;
    const modal = $('#editFormModal');
    const form = modal.find('#editForm')[0];

    form.reset();
    document.getElementById('editFieldInputs').innerHTML = '';

    $('#editFormId').val(formId);

    $.ajax({
        url: '/admin/fetch-form-with-fields',
        method: 'GET',
        data: { id: formId },
        success: function (response) {
            if (!response.success) {
                alert('Ошибка загрузки данных: ' + response.error);
                modal.modal('hide');
                return;
            }

            $('#editFormName').val(response.form.form_name);

            if (response.fields.length === 0) {
                return;
            }

            response.fields.forEach(field => {
                const wrapper = document.createElement('div');
                wrapper.classList.add('mb-2');
                wrapper.innerHTML = `
                    <input type="hidden" name="fields[\${editFieldIndex}][id]" value="\${field.id}">
                    <input type="text" name="fields[\${editFieldIndex}][field_name]" class="form-control mb-1" placeholder="Название поля" required value="\${field.field_name}">
                    <select name="fields[\${editFieldIndex}][type_id]" class="form-control mb-1" required>
                        \${optionsHtml}
                    </select>
                    <button type="button" class="btn btn-sm btn-danger remove-field-btn">Удалить поле</button>
                    <hr>
                `;
                container.appendChild(wrapper);

                // Устанавливаем выбранный тип поля
                $(wrapper).find('select').val(field.type_id);

                editFieldIndex++;
            });
        },
        error: function () {
            alert('Произошла ошибка при загрузке данных формы.');
            modal.modal('hide');
        }
    });
});

$('.toggle-form-btn').on('click', function () {
    const formId = $(this).data('form');
    const modal = $('#viewFormModal');
    const modalBody = modal.find('.modal-body');
    const modalTitle = modal.find('.modal-title');

    modalBody.html('<p>Загрузка...</p>');

    $.ajax({
        url: '/admin/fetch-fields-by-form-id',
        method: 'GET',
        data: { id: formId },
        success: function (response) {
            if (!response.success) {
                modalBody.html('<p class="text-danger">Ошибка: ' + response.error + '</p>');
                return;
            }

            modalTitle.text('Детали формы: ' + response.form_name);

            if (response.fields.length === 0) {
                modalBody.html('<p>Нет полей в этой форме.</p>');
                return;
            }

           let html = '<ul class="list-group">';
            response.fields.forEach(function (field) {
                html += `<li class="list-group-item d-flex justify-content-between align-items-center">
                    <span>\${field.field_name}</span>
                    <span class="text-muted">\${field.type_name}</span>
                </li>`;
            });
            html += '</ul>';

            modalBody.html(html);
        },
        error: function () {
            modalBody.html('<p class="text-danger">Произошла ошибка при загрузке данных.</p>');
        }
    });
});

// Удаление формы (как у тебя было)
$('.delete-form-btn').on('click', function () {
    const formId = $(this).data('form-id');
    const card = $(this).closest('.col-md-4');

    if (!confirm('Вы уверены, что хотите удалить эту форму?')) {
        return;
    }

    $.ajax({
        url: '/admin/delete-form',
        type: 'POST',
        data: {
            id: formId,
            _csrf: yii.getCsrfToken()
        },
        success: function (response) {
            if (response.success) {
                card.fadeOut(300, function () {
                    $(this).remove();
                });
            } else {
                alert('Ошибка при удалении: ' + response.error);
            }
        },
        error: function () {
            alert('Серверная ошибка при удалении формы.');
        }
    });
});
JS;

$this->registerJs($js);
?>
