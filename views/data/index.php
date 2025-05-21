<?php

use app\models\DataSearch;
use app\models\Form;
use yii\data\ActiveDataProvider;
use yii\grid\GridView;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/** @var DataSearch $searchModel */
/** @var ActiveDataProvider $dataProvider */

$forms = ArrayHelper::map(Form::find()->all(), 'id', 'form_name');
?>

<div class="data-search">

    <?php $form = ActiveForm::begin([
        'action' => ['search'],
        'method' => 'get',
    ]); ?>

    <?= $form->field($searchModel, 'form_id')->dropDownList($forms, ['prompt' => 'Выберите форму']) ?>
<!--    --><?php //= $form->field($searchModel, 'field_name')->textInput(['placeholder' => 'Введите имя поля']) ?>
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
