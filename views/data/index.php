<?php

use app\models\DataSearch;
use yii\data\ActiveDataProvider;
use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\grid\GridView;
use app\models\FormField;

/** @var DataSearch $searchModel */
/** @var ActiveDataProvider $dataProvider */

$formFields = FormField::find()
    ->joinWith('forms') // assuming relation getForm() exists
    ->where(['forms.status' => true])
    ->select(['form_fields.field_name', 'form_fields.id']) // field_name as label, id as key
    ->indexBy('id') // use ID to ensure uniqueness
    ->column();

?>

<div class="data-search">

    <?php $form = ActiveForm::begin([
        'action' => ['search'],
        'method' => 'get',
    ]); ?>

    <?= $form->field($searchModel, 'field_id')->dropDownList($formFields, ['prompt' => 'Выберите поле']) ?>
    <?= $form->field($searchModel, 'value')->textInput(['placeholder' => 'Введите значение']) ?>

    <div class="form-group">
        <?= Html::submitButton('Поиск', ['class' => 'btn btn-primary']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>

<?= GridView::widget([
    'dataProvider' => $dataProvider,
    'columns' => [
        [
            'attribute' => 'profile_id',
            'label' => 'ФИО',
            'value' => function($model) {
                return $model->fullName;
            },
        ],
        [
            'attribute' => 'field_id',
            'value' => function($model) {
                return $model->formField->field_name;
            },
            'label' => 'Поле формы'
        ],
        'data',
    ],
]); ?>
