<?php

use app\models\Form;
use app\models\FormFieldType;
use yii\bootstrap5\Modal;
use yii\helpers\Html;
use yii\helpers\Url;

/** @var yii\web\View $this */

$this->title = 'Конструктор форм';
$this->registerCsrfMetaTags();

$fieldTypes = FormFieldType::find()->all();

$statusFilter = Yii::$app->request->get('statusFilter', 'active');
$query = Form::find();

if ($statusFilter === 'active') {
    $query->where(['status' => true]);
} elseif ($statusFilter === 'disabled') {
    $query->where(['status' => false]);
}
$forms = $query->all();

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

    <div class="mb-4">
        <form method="get">
            <label for="statusFilter" class="form-label">Фильтр по статусу:</label>
            <select id="statusFilter" name="statusFilter" class="form-select" onchange="this.form.submit()">
                <option value="all" <?= $statusFilter === 'all' ? 'selected' : '' ?>>Все формы</option>
                <option value="active" <?= $statusFilter === 'active' ? 'selected' : '' ?>>Только активные</option>
                <option value="disabled" <?= $statusFilter === 'disabled' ? 'selected' : '' ?>>Только отключённые</option>
            </select>
        </form>
    </div>

    <div class="row">
        <?php foreach ($forms as $form): ?>
            <div class="col-md-4">
                <div class="card mb-4 <?= !$form->status ? 'disabled-card' : '' ?>" style="<?= !$form->status ? 'opacity: 0.5;' : '' ?>">
                    <div class="card-body">
                        <h5 class="card-title">
                            <?= Html::encode($form->form_name) ?>
                            <?php if (!$form->status): ?>
                                <span class="badge bg-secondary">Отключена</span>
                            <?php endif; ?>
                        </h5>

                        <button type="button" class="btn btn-primary toggle-form-btn"
                                data-bs-toggle="modal" data-bs-target="#exampleModal"
                                data-form="<?= $form->id ?>" <?= !$form->status ? 'disabled' : '' ?>>
                            Открыть
                        </button>

                        <?php if ($form->status): ?>
                            <button type="button"
                                    class="btn btn-danger btn-sm delete-form-btn"
                                    data-form-id="<?= $form->id ?>">
                                Отключить
                            </button>
                        <?php else: ?>
                            <button type="button"
                                    class="btn btn-success btn-sm restore-form-btn"
                                    data-form-id="<?= $form->id ?>">
                                Восстановить
                            </button>
                        <?php endif; ?>
                    </div>

                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php echo Html::beginForm(Url::to(['admin/create']), 'post', ['id' => 'mainForm']); ?>

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
    <div id="fieldInputs" class="mb-3"></div>

    <button type="button" class="btn btn-outline-secondary btn-sm mb-3" id="addField">
        + Добавить поле
    </button>

    <div class="text-end">
        <button type="submit" class="btn btn-success">Сохранить форму</button>
    </div>
</div>

<?php Modal::end(); ?>

<!-- Modal для просмотра формы -->
<div class="modal fade" id="exampleModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Modal title</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">...</div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Закрыть</button>
            </div>
        </div>
    </div>
</div>

<?php echo Html::endForm(); ?>

<?php
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

$('.delete-form-btn').on('click', function () {
    const formId = $(this).data('form-id');
    const card = $(this).closest('.col-md-4');

    if (!confirm('Вы уверены, что хотите отключить эту форму?')) {
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
                card.addClass('disabled-card');
                card.find('.card-title').append(' <span class="badge bg-secondary">Отключена</span>');
                card.css('opacity', '0.5');
                card.find('button, a, input, select, textarea').prop('disabled', true);
            } else {
                alert('Ошибка при отключении: ' + response.error);
            }
        },
        error: function () {
            alert('Серверная ошибка при удалении формы.');
        }
    });
});

$('.restore-form-btn').on('click', function () {
    const formId = $(this).data('form-id');
    const card = $(this).closest('.col-md-4');

    if (!confirm('Вы уверены, что хотите восстановить эту форму?')) {
        return;
    }

    $.ajax({
        url: '/admin/restore-form',
        type: 'POST',
        data: {
            id: formId,
            _csrf: yii.getCsrfToken()
        },
        success: function (response) {
            if (response.success) {
                location.reload(); 
            } else {
                alert('Ошибка: ' + response.error);
            }
        },
        error: function () {
            alert('Произошла ошибка при восстановлении.');
        }
    });
});

JS;

$this->registerJs($addFieldJs);
?>
