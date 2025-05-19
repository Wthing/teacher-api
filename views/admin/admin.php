
<?php

use yii\helpers\Html;
use yii\bootstrap5\Modal;
use yii\helpers\Url;

/** @var yii\web\View $this */

$this->title = 'Конструктор форм';
$this->registerCsrfMetaTags();

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
                        <button type="button" class="btn btn-primary toggle-form-btn" data-bs-toggle="modal" data-bs-target="#exampleModal" data-form="<?= $form->id ?>">
                            Открыть
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

<?php
echo Html::beginForm(Url::to(['admin/create']), 'post', ['id' => 'mainForm']);
?>

<?php Modal::begin([
    'id' => 'formModalStep1',
    'title' => 'Создание формы — шаг 1',
]); ?>

<div>
    <div class="mb-3">
        <?= Html::label('Название формы', 'form-name', ['class' => 'form-label']) ?>
        <?= Html::textInput('Form[form_name]', '', [
            'class' => 'form-control',
            'required' => true,
            'id' => 'form-name',
            'placeholder' => 'Например: Обратная связь'
        ]) ?>
    </div>
    <div class="text-end">
        <button type="button"
                class="btn btn-primary"
                data-bs-target="#formModalStep2"
                data-bs-toggle="modal"
                data-bs-dismiss="modal">Далее</button>
    </div>
</div>

<?php Modal::end(); ?>

<?php Modal::begin([
    'id' => 'formModalStep2',
    'title' => 'Создание формы — шаг 2',
]); ?>

<div id="fieldContainer">
    <div id="fieldInputs" class="mb-3">
        <!-- Динамически добавляемые поля -->
    </div>


    <button type="button" class="btn btn-outline-secondary btn-sm mb-3" id="addField">
        + Добавить поле
    </button>

    <div class="text-end">
        <button type="submit" class="btn btn-success">Сохранить форму</button>
    </div>


</div>

<?php Modal::end(); ?>

<!-- Modal -->

<div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Modal title</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                ...
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary">Save changes</button>
            </div>
        </div>
    </div>
</div>

<?php
// Закрываем форму ПОСЛЕ всех модалок
echo Html::endForm();

$addFieldJs = <<<JS
let fieldIndex = 0;
const optionsHtml = `$optionsHtml`;

document.getElementById('addField').addEventListener('click', () => {
    const container = document.getElementById('fieldInputs');
    const wrapper = document.createElement('div');
    wrapper.classList.add('mb-2');
    wrapper.innerHTML = `
        <input type="text" name="fields[\${fieldIndex}][field_name]" class="form-control mb-1" placeholder="Название поля" required>
        <select name="fields[\${fieldIndex}][type_id]" class="form-control mb-1" required>
            \${optionsHtml}
        </select>
    `;
    container.appendChild(wrapper);
    fieldIndex++;
});

$('.toggle-form-btn').on('click', function () {
    const formId = $(this).data('form');
    const modal = $('#exampleModal');
    const modalBody = modal.find('.modal-body');
    const modalTitle = modal.find('.modal-title');

    modalBody.html('<p>Загрузка...</p>');

    $.ajax({
        url: '/admin/fetch-fields-by-form-id', // путь может отличаться!
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

$this->registerJs($addFieldJs);
?>
