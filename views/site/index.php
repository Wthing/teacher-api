<?php


/** @var yii\web\View $this */
/** @var DataSearch $searchModel */
/** @var Form[] $forms */
/** @var Form[] $allForms */
/** @var array $fields */
/** @var array $userData */

use app\models\DataSearch;
use app\models\Form;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\widgets\ActiveForm;

function isValidUrl($url): bool
{
    return filter_var($url, FILTER_VALIDATE_URL) !== false;
}

$availableForms = ArrayHelper::map($allForms, 'id', 'form_name');

$this->title = 'My Yii Application';
?>
<head>
    <!-- MDB CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/core@latest/dist/css/tabler.min.css">
    <title>Главная</title>
</head>

<script src="https://cdn.jsdelivr.net/npm/@tabler/core@latest/dist/js/tabler.min.js"></script>

<div class="site-index">

    <div class="container d-flex justify-content-center align-items-center" style="min-height: 70vh;">
        <div class="data-search" style="width: 100%; max-width: 1200px;">
            <div class="bg-blue-500 text-black text-center p-4 rounded-lg">

                <h1 class="display-4 mb-4">Извините, а вы не знаете где...</h1>

                <?php $form = ActiveForm::begin([
                    'action' => ['data/search'],
                    'method' => 'get',
                ]); ?>

                <div class="row g-3">
                    <!-- Селект формы — уже -->
                    <div class="col-md-3 col-12">
                        <?= $form->field($searchModel, 'form_id')
                            ->dropDownList($availableForms, [
                                'prompt' => 'Выберите форму',
                                'class' => 'form-select rounded-pill'
                            ])
                            ->label(false) ?>
                    </div>

                    <!-- Поле поиска — шире -->
                    <div class="col-md-9 col-12">
                        <?= $form->field($searchModel, 'value')
                            ->textInput([
                                'placeholder' => 'Введите значение',
                                'class' => 'form-control rounded-pill'
                            ])
                            ->label(false) ?>
                    </div>
                </div>

                <!-- Кнопки по центру -->
                <div class="text-center mt-4">
                    <?= Html::submitButton('Поиск', [
                        'class' => 'btn btn-primary',
                        'data-mdb-ripple-init' => true
                    ]) ?>

                    <?= Html::a('Очистить', ['data/search'], [
                        'class' => 'btn btn-secondary',
                        'data-mdb-ripple-init' => true,
                        'data-mdb-ripple-color' => 'light'
                    ]) ?>
                </div>

                <?php ActiveForm::end(); ?>
            </div>
        </div>
    </div>
</div>


