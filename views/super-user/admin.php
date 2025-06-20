<?php
/**
 * @var yii\web\View            $this
 * @var yii\db\ActiveRecord[]   $forms
 * @var yii\db\ActiveRecord[]   $profiles
 */

use app\models\Form;
use app\models\FormField;
use app\models\FormFieldType;
use app\models\User;
use yii\bootstrap5\Modal;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;

$this->title = 'Конструктор форм';
$this->registerCsrfMetaTags();

/* === Данные для селектов ================================================= */
$fieldTypes   = FormFieldType::find()->all();
$optionsTypes = Html::renderSelectOptions(
    null,
    ArrayHelper::map($fieldTypes, 'id', 'type_name')
);

$formFields   = FormField::find()->all();
$optionsFields = Html::renderSelectOptions(
    null,
    ArrayHelper::map($formFields, 'id', fn($f) => "{$f->field_name} ({$f->form->form_name})")
);

$profiles   = User::find()->all();
$optionsProfiles = Html::renderSelectOptions(
    null,
    ArrayHelper::map($profiles, 'id', 'username')
);

/* === Фильтр форм ========================================================= */
$statusFilter = Yii::$app->request->get('statusFilter', 'active');
$query        = Form::find();
$statusFilter === 'active'   ? $query->where(['status' => true])  :
    ($statusFilter === 'disabled' ? $query->where(['status' => false]) : null);
$forms = $query->orderBy(['id'=>SORT_DESC])->all();
?>

<head>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/core@latest/dist/css/tabler.min.css">
    <title>Профиль</title>
</head>

<script src="https://cdn.jsdelivr.net/npm/@tabler/core@latest/dist/js/tabler.min.js"></script>

<style>
    .card.disabled {
        opacity:.55;
        filter:grayscale(.15);
    }
    .badge-type {
        font-size:.72rem;
        background:#e9ecef;
        color:#495057
    }
</style>


<div class="container-xl mt-4">

    <div class="d-flex flex-wrap gap-2 mb-4">
        <?= Html::button('＋Новая форма',   ['class'=>'btn btn-success',   'data-bs-toggle'=>'modal', 'data-bs-target'=>'#formModalStep1']) ?>
        <?= Html::button('＋Автозаполнение',['class'=>'btn btn-info',      'data-bs-toggle'=>'modal', 'data-bs-target'=>'#autocompleteModal']) ?>
        <?= Html::button('＋Типы полей',    ['class'=>'btn btn-warning',   'data-bs-toggle'=>'modal', 'data-bs-target'=>'#typeModal']) ?>
    </div>

    <ul class="nav nav-pills mb-4" id="formStatusFilter">
        <?php
        foreach (['all'=>'Все','active'=>'Активные','disabled'=>'Отключённые'] as $key=>$label){
            echo Html::tag(
                'li',
                Html::a($label, ['super-user/index','statusFilter'=>$key],
                    ['class'=>'nav-link' . ($statusFilter===$key?' active':'')]
                ),
                ['class'=>'nav-item']
            );
        }
        ?>
    </ul>

    <div class="row g-4">
        <?php foreach ($forms as $form): ?>
            <?php
            $disabled = !$form->status;
            $fieldsCnt = $form->getFormFields()->count();
            ?>
            <div class="col-sm-6 col-lg-4">
                <div class="card shadow-sm <?= $disabled?'disabled':'' ?>">
                    <div class="card-body d-flex flex-column">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <h4 class="m-0 fs-5"><?= Html::encode($form->form_name) ?></h4>
                            <span class="badge rounded-pill bg-secondary"><?= $fieldsCnt ?></span>
                        </div>

                        <?php if ($disabled): ?>
                            <span class="badge bg-secondary mb-3">Отключена</span>
                        <?php endif; ?>

                        <div class="mt-auto d-flex gap-2">
                            <?= Html::button('Подробности', [
                                'class'       => 'btn btn-primary btn-icon flex-fill toggle-form-btn',
                                'data-form'   => $form->id,
                                'title'       => 'Подробности',
                                'disabled'    => $disabled,
                                'data-bs-toggle'=>'modal',
                                'data-bs-target'=>'#formViewModal'
                            ]) ?>
                            <?php if ($disabled): ?>
                                <?= Html::button('Восстановить', [
                                    'class'=>'btn btn-success btn-icon flex-fill restore-form-btn',
                                    'title'=>'Восстановить',
                                    'data-form-id'=>$form->id
                                ]) ?>
                            <?php else: ?>
                                <?= Html::button('Отключить', [
                                    'class'=>'btn btn-outline-danger btn-icon flex-fill delete-form-btn',
                                    'title'=>'Отключить',
                                    'data-form-id'=>$form->id
                                ]) ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>

        <?php if(empty($forms)): ?>
            <div class="col-12 text-center text-muted py-5">Формы не найдены</div>
        <?php endif; ?>
    </div>
</div>


<?php Modal::begin([
    'id'    => 'formModalStep1',
    'title' => 'Новая форма—шаг1/2',
    'size'  => Modal::SIZE_DEFAULT
]); ?>
<div class="mb-3">
    <?= Html::label('Названиеформы','form-name',['class'=>'form-label fw-semibold']) ?>
    <?= Html::textInput('Form[form_name]', '', [
        'class'=>'form-control',
        'placeholder'=>'Например: Заявка на отпуск',
        'required'=>true,
        'id'=>'form-name'
    ]) ?>
</div>

<div class="form-check form-switch mb-3">
    <?= Html::checkbox('Form[requires_verification]', false, [
        'class'=>'form-check-input',
        'id'=>'requires-verification'
    ]) ?>
    <?= Html::label('Нужна проверка полями','requires-verification',['class'=>'form-check-label']) ?>
</div>

<div class="mb-4">
    <?= Html::label('Ответственный профиль','user-id',['class'=>'form-label fw-semibold']) ?>
    <?= Html::dropDownList('FormConfirmPerson[user_id]', null, $profiles ?
        ArrayHelper::map($profiles,'id','username') : [], [
        'prompt'=>'Выберите профиль',
        'class'=>'form-select',
        'required'=>true,
        'id'=>'user-id'
    ]) ?>
</div>

<div class="d-flex justify-content-end">
    <button class="btn btn-primary" data-bs-target="#formModalStep2"
            data-bs-toggle="modal" data-bs-dismiss="modal">
        Далее →
    </button>
</div>
<?php Modal::end(); ?>


<?php Modal::begin([
    'id'    => 'formModalStep2',
    'title' => 'Новая форма—шаг2/2',
    'size'  => Modal::SIZE_DEFAULT
]); ?>
<div id="fieldContainer">
    <div id="fieldInputs"></div>
    <button type="button" id="addField"
            class="btn btn-outline-secondary btn-sm my-3 w-100">
        + Добавить поле
    </button>
</div>

<div class="text-end">
    <?= Html::submitButton('Сохранить форму', ['class'=>'btn btn-success']) ?>
</div>
<?php Modal::end(); ?>


<?php Modal::begin([
    'id'    => 'formViewModal',
    'title' => 'Детали формы',
    'size'  => Modal::SIZE_DEFAULT
]); ?>
<div class="modal-body">
    <p class="text-center text-muted mb-0">Загрузка…</p>
</div>
<?php Modal::end(); ?>


<?php Modal::begin(['id'=>'typeModal','title'=>'Новые типы полей']); ?>
<?= Html::beginForm(['super-user/create-types'],'post',['id'=>'typeForm']) ?>
<?= Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->csrfToken) ?>

<div id="typeFieldsContainer">
    <div class="input-group mb-2 type-entry">
        <input type="text" name="FormFieldTypes[0][type_name]" class="form-control"
               placeholder="Название типа" required maxlength="255">
        <button class="btn btn-outline-danger remove-entry-btn" type="button">×</button>
    </div>
</div>

<button id="addTypeEntry" class="btn btn-outline-secondary btn-sm mb-3" type="button">
    + Ещё
</button>

<div class="text-end">
    <?= Html::submitButton('Сохранить',['class'=>'btn btn-success']) ?>
</div>
<?= Html::endForm() ?>
<?php Modal::end(); ?>


<?php Modal::begin(['id'=>'autocompleteModal','title'=>'Записи автозаполнения']); ?>
<?= Html::beginForm(['super-user/create-autocomplete'],'post',['id'=>'autocompleteForm']) ?>
<?= Html::hiddenInput(Yii::$app->request->csrfParam, Yii::$app->request->csrfToken) ?>

<div id="autocompleteFieldsContainer">
    <div class="input-group mb-2 autocomplete-entry">
        <select name="FormFieldAutocompleteEntries[0][field_id]" class="form-select" required>
            <option value="">Поле…</option><?= $optionsFields ?>
        </select>
        <input type="text" name="FormFieldAutocompleteEntries[0][content]"
               class="form-control" placeholder="Значение" required maxlength="255">
        <button class="btn btn-outline-danger remove-entry-btn" type="button">×</button>
    </div>
</div>

<button id="addAutocompleteEntry" class="btn btn-outline-secondary btn-sm mb-3" type="button">
    + Ещё
</button>

<div class="text-end">
    <?= Html::submitButton('Сохранить',['class'=>'btn btn-success']) ?>
</div>
<?= Html::endForm() ?>
<?php Modal::end(); ?>


<?php
$this->registerJs(/** @lang JavaScript */"
let fieldIdx = 0,
    typeIdx = 1,
    autoIdx = 1,
    typeOptions = ".json_encode($optionsTypes).";

const addFieldBtn   = document.getElementById('addField'),
      fieldInputs   = document.getElementById('fieldInputs');

addFieldBtn?.addEventListener('click', ()=>{
    const wrap = document.createElement('div');
    wrap.className = 'input-group mb-2';
    wrap.innerHTML = `
        <input class=\"form-control\" name=\"fields[\${fieldIdx}][field_name]\" placeholder=\"Название\" required>
        <select class=\"form-select\" name=\"fields[\${fieldIdx}][type_id]\" required>
            ".addslashes($optionsTypes)."
        </select>
    `;
    fieldInputs.append(wrap); fieldIdx++;
});

/* === динамика «типы полей» === */
document.getElementById('addTypeEntry').onclick = ()=>{
    const el = document.createElement('div');
    el.className='input-group mb-2 type-entry';
    el.innerHTML = `
        <input class=\"form-control\" name=\"FormFieldTypes[\${typeIdx}][type_name]\" placeholder=\"Название типа\" required maxlength=\"255\">
        <button class=\"btn btn-outline-danger remove-entry-btn\" type=\"button\">×</button>
    `;
    document.getElementById('typeFieldsContainer').append(el); typeIdx++;
};
document.getElementById('typeFieldsContainer').addEventListener('click',e=>{
    if(e.target.matches('.remove-entry-btn')) e.target.closest('.type-entry').remove();
});

/* === динамика «автозаполнение» === */
document.getElementById('addAutocompleteEntry').onclick = ()=>{
    const el = document.createElement('div');
    el.className='input-group mb-2 autocomplete-entry';
    el.innerHTML = `
        <select class=\"form-select\" name=\"FormFieldAutocompleteEntries[\${autoIdx}][field_id]\" required>
            <option value=\"\">Поле…</option>".addslashes($optionsFields)."
        </select>
        <input class=\"form-control\" name=\"FormFieldAutocompleteEntries[\${autoIdx}][content]\" placeholder=\"Значение\" required maxlength=\"255\">
        <button class=\"btn btn-outline-danger remove-entry-btn\" type=\"button\">×</button>
    `;
    document.getElementById('autocompleteFieldsContainer').append(el); autoIdx++;
};
document.getElementById('autocompleteFieldsContainer').addEventListener('click',e=>{
    if(e.target.matches('.remove-entry-btn')) e.target.closest('.autocomplete-entry').remove();
});

/* === просмотр формы === */
$('.toggle-form-btn').on('click',function(){
    const id = $(this).data('form'),
          modal  = $('#formViewModal'),
          body   = modal.find('.modal-body');
    body.html('<p class=\"text-center my-4\">Загрузка…</p>');
    $.get('/super-user/fetch-fields-by-form-id',{id})
        .done(res=>{
            if(!res.success) return body.html(`<p class='text-danger'>\${res.error}</p>`);
            let html = '<ul class=\"list-group\">';
            res.fields.forEach(f=>{
                html += `<li class='list-group-item d-flex justify-content-between align-items-center'>
                            <span>\${f.field_name}</span>
                            <span class='badge badge-type'>\${f.type_name}</span>
                         </li>`;
            });
            html += '</ul>';
            body.html(html);
            modal.find('.modal-title').text(res.form_name);
        })
        .fail(()=>body.html('<p class=\"text-danger\">Ошибка загрузки</p>'));
});

/* === отключить / включить форму === */
const csrf = yii.getCsrfToken();
function toggleForm(url,btn,disable){
    const id   = $(btn).data('form-id'),
          card = $(btn).closest('.card');
    if(!confirm(disable?'Отключить форму?':'Восстановить форму?')) return;
    $.post(url,{id,_csrf:csrf}).done(r=>{
        if(!r.success) return alert(r.error||'Ошибка');
        location.reload();
    }).fail(()=>alert('Серверная ошибка'));
}
$('.delete-form-btn').on('click',function(){toggleForm('/super-user/delete-form',this,true)});
$('.restore-form-btn').on('click',function(){toggleForm('/super-user/restore-form',this,false)});
");
?>
