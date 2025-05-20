<?php

use app\models\DataSearch;
use yii\data\ActiveDataProvider;
use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\grid\GridView;
use app\models\FormField;

/** @var DataSearch $searchModel */
/** @var ActiveDataProvider $dataProvider */

$formFields = FormField::find()->select(['field_name'])->indexBy('field_name')->column();
?>

<div class="data-search">

    <?php $form = ActiveForm::begin([
        'action' => ['search'],
        'method' => 'get',
    ]); ?>

    <?= $form->field($searchModel, 'field_name')->dropDownList($formFields, ['prompt' => 'Выберите поле']) ?>
    <?= $form->field($searchModel, 'value')->textInput(['placeholder' => 'Введите значение']) ?>

    <div class="form-group">
        <?= Html::submitButton('Поиск', ['class' => 'btn btn-primary']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>

<?= GridView::widget([
    'dataProvider' => $dataProvider,
    'columns' => [
        'id',
        'profile_id',
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
