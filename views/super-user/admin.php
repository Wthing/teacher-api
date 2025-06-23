<?php
/**
 * @var yii\web\View  $this
 * @var yii\db\ActiveRecord[] $forms
 */

use app\models\Form;
use app\models\FormField;
use app\models\FormFieldType;
use app\models\User;
use yii\bootstrap5\Modal;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Конструктор форм';
$this->registerCsrfMetaTags();

/* ---------- данные для селектов ---------- */
$fieldTypes   = FormFieldType::find()->all();
$optionsTypes = Html::renderSelectOptions(null, ArrayHelper::map($fieldTypes, 'id', 'type_name'));

$formFields   = FormField::find()->all();
$optionsFields = Html::renderSelectOptions(null,
    ArrayHelper::map($formFields,'id',fn($f)=>"{$f->field_name} ({$f->form->form_name})"));

$profiles   = User::find()->all();
$optionsProfiles = Html::renderSelectOptions(null, ArrayHelper::map($profiles,'id','username'));

/* ---------- фильтр ---------- */
$statusFilter = Yii::$app->request->get('statusFilter','active');
$query = Form::find();
$statusFilter==='active'   ? $query->where(['status'=>true])  :
    ($statusFilter==='disabled'? $query->where(['status'=>false]):null);
$forms = $query->orderBy(['id'=>SORT_DESC])->all();
?>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/core@latest/dist/css/tabler.min.css">
    <script src="https://cdn.jsdelivr.net/npm/@tabler/core@latest/dist/js/tabler.min.js"></script>

    <style>
        .card.disabled{opacity:.55;filter:grayscale(.15)}
        .badge-type{font-size:.72rem;background:#e9ecef;color:#495057}
    </style>

    <div class="container-xl mt-4">
        <!-- кнопки -->
        <div class="d-flex flex-wrap gap-2 mb-4">
            <?= Html::button('＋Новая форма',   ['class'=>'btn btn-success','data-bs-toggle'=>'modal','data-bs-target'=>'#formModalStep1']) ?>
            <?= Html::button('＋Автозаполнение',['class'=>'btn btn-info','data-bs-toggle'=>'modal','data-bs-target'=>'#autocompleteModal']) ?>
            <?= Html::button('＋Типы полей',    ['class'=>'btn btn-warning','data-bs-toggle'=>'modal','data-bs-target'=>'#typeModal']) ?>
        </div>

        <!-- фильтр -->
        <ul class="nav nav-pills mb-4">
            <?php foreach(['all'=>'Все','active'=>'Активные','disabled'=>'Отключённые'] as $k=>$lbl): ?>
                <li class="nav-item">
                    <?= Html::a($lbl,
                        ['super-user/index','statusFilter'=>$k],
                        ['class'=>'nav-link'.($statusFilter===$k?' active':'')]) ?>
                </li>
            <?php endforeach; ?>
        </ul>

        <!-- карточки форм -->
        <div class="row g-4">
            <?php foreach($forms as $form): ?>
                <?php $disabled=!$form->status;$cnt=$form->getFormFields()->count(); ?>
                <div class="col-sm-6 col-lg-4">
                    <div class="card shadow-sm <?= $disabled?'disabled':'' ?>">
                        <div class="card-body d-flex flex-column">
                            <div class="d-flex justify-content-between mb-2">
                                <h4 class="fs-5 mb-0"><?= Html::encode($form->form_name) ?></h4>
                                <span class="badge bg-secondary"><?= $cnt ?></span>
                            </div>
                            <?php if($disabled):?><span class="badge bg-secondary mb-3">Отключена</span><?php endif;?>
                            <div class="mt-auto d-flex gap-2">
                                <?= Html::button('Подробности',[
                                    'class'=>'btn btn-primary flex-fill toggle-form-btn',
                                    'data-form'=>$form->id,
                                    'disabled'=>$disabled,
                                    'data-bs-toggle'=>'modal',
                                    'data-bs-target'=>'#formViewModal'
                                ]) ?>
                                <?= Html::button($disabled?'Восстановить':'Отключить',[
                                    'class'=>$disabled?'btn btn-success flex-fill restore-form-btn'
                                        :'btn btn-outline-danger flex-fill delete-form-btn',
                                    'data-form-id'=>$form->id
                                ]) ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach;?>
            <?php if(empty($forms)):?>
                <div class="col-12 text-center text-muted py-5">Формы не найдены</div>
            <?php endif;?>
        </div>
    </div>

    <!-- ---------------- ШАГ 1 ---------------- -->
<?php Modal::begin([
    'id'=>'formModalStep1',
    'title'=>'Новая форма — шаг 1 / 2'
]);?>
    <div class="mb-3">
        <?= Html::label('Название формы','form-name',['class'=>'form-label fw-semibold']) ?>
        <?= Html::textInput('tmp_form_name','',[
            'class'=>'form-control','required'=>true,'id'=>'form-name',
            'placeholder'=>'Например: Заявка на отпуск'
        ]) ?>
    </div>
    <div class="form-check form-switch mb-3">
        <?= Html::checkbox('tmp_requires_verification',false,['class'=>'form-check-input','id'=>'requires-verification']) ?>
        <?= Html::label('Нужна проверка полей','requires-verification',['class'=>'form-check-label']) ?>
    </div>
    <div class="mb-4">
        <?= Html::label('Ответственный профиль','user-id',['class'=>'form-label fw-semibold']) ?>
        <?= Html::dropDownList('tmp_user_id',null,
            ArrayHelper::map($profiles,'id','username'),[
                'prompt'=>'Выберите профиль','class'=>'form-select','required'=>true,'id'=>'user-id'
            ]) ?>
    </div>
    <div class="text-end">
        <button class="btn btn-primary"
                data-bs-target="#formModalStep2"
                data-bs-toggle="modal"
                data-bs-dismiss="modal">
            Далее →
        </button>
    </div>
<?php Modal::end();?>

    <!-- ---------------- ШАГ 2 ---------------- -->
<?php Modal::begin([
    'id'=>'formModalStep2',
    'title'=>'Новая форма — шаг 2 / 2'
]);?>

<?= Html::beginForm(['super-user/create'],'post',['id'=>'mainForm']) ?>
<?= Html::hiddenInput(Yii::$app->request->csrfParam,Yii::$app->request->csrfToken) ?>

    <!-- hidden из шага 1 -->
<?= Html::hiddenInput('Form[form_name]','',['id'=>'hidden-form-name']) ?>
<?= Html::hiddenInput('Form[requires_verification]','0',['id'=>'hidden-verification']) ?>
<?= Html::hiddenInput('FormConfirmPerson[user_id]','',['id'=>'hidden-user-id']) ?>

    <div id="fieldContainer">
        <div id="fieldInputs"></div>
        <button type="button" id="addField"
                class="btn btn-outline-secondary btn-sm my-3 w-100">
            + Добавить поле
        </button>
    </div>

    <div class="text-end">
        <?= Html::submitButton('Сохранить форму',['class'=>'btn btn-success']) ?>
    </div>

<?= Html::endForm() ?>
<?php Modal::end();?>
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
/** --------------- JS --------------- */
$this->registerJs(<<<JS
let fieldIdx = 0,
    typeIdx = 1,
    autoIdx = 1;

(function(){
    // --- шаг 1 → шаг 2: перенос значений ---
    document.querySelector('[data-bs-target="#formModalStep2"]').addEventListener('click', () => {
        document.getElementById('hidden-form-name').value =
            document.getElementById('form-name').value.trim();

        document.getElementById('hidden-verification').value =
            document.getElementById('requires-verification').checked ? 1 : 0;

        document.getElementById('hidden-user-id').value =
            document.getElementById('user-id').value;
    });

    // --- динамика: добавление полей ---
    let fieldIdx = 0;
    document.getElementById('addField').addEventListener('click', () => {
        const tpl = `<div class="input-group mb-2">
            <input class="form-control" name="fields[\${fieldIdx}][field_name]" placeholder="Название" required>
            <select class="form-select" name="fields[\${fieldIdx}][type_id]" required>
                <option value="">Тип…</option>
                $$optionsTypes
            </select>
        </div>`;
        document.getElementById('fieldInputs').insertAdjacentHTML('beforeend', tpl);
        fieldIdx++;
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

    
    // --- просмотр формы ---
    $('.toggle-form-btn').on('click', function () {
        const id = $(this).data('form');
        const modal = $('#formViewModal');
        const body = modal.find('.modal-body');
        body.html('<p class="text-center my-4">Загрузка…</p>');
        $.get('/super-user/fetch-fields-by-form-id', { id })
            .done(res => {
                if (!res.success) return body.html(`<p class='text-danger'>\${res.error}</p>`);
                let html = '<ul class="list-group">';
                res.fields.forEach(f => {
                    html += `<li class='list-group-item d-flex justify-content-between align-items-center'>
                                <span>\${f.field_name}</span>
                                <span class='badge badge-type'>\${f.type_name}</span>
                             </li>`;
                });
                html += '</ul>';
                body.html(html);
                modal.find('.modal-title').text(res.form_name);
            })
            .fail(() => body.html('<p class="text-danger">Ошибка загрузки</p>'));
    });

    // --- удаление / восстановление формы ---
    const csrf = yii.getCsrfToken();
    function toggleForm(url, btn, disable) {
        const id = $(btn).data('form-id'),
              card = $(btn).closest('.card');
        if (!confirm(disable ? 'Отключить форму?' : 'Восстановить форму?')) return;
        $.post(url, { id, _csrf: csrf }).done(r => {
            if (!r.success) return alert(r.error || 'Ошибка');
            location.reload();
        }).fail(() => alert('Серверная ошибка'));
    }

    $('.delete-form-btn').on('click', function () {
        toggleForm('/super-user/delete-form', this, true);
    });

    $('.restore-form-btn').on('click', function () {
        toggleForm('/super-user/restore-form', this, false);
    });

})();
JS);

?>