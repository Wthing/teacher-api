<?php

use app\models\DataSearch;
use app\models\Form;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\widgets\ActiveForm;

/** @var DataSearch $searchModel */
/** @var Form[] $forms */
/** @var Form[] $allForms */
/** @var array $fields */
/** @var array $userData */

function isValidUrl($url): bool
{
    return filter_var($url, FILTER_VALIDATE_URL) !== false;
}

$availableForms = ArrayHelper::map($allForms, 'id', 'form_name');


?>

<div class="data-search">

    <div class="container mb-4">
        <?php $form = ActiveForm::begin([
            'action' => ['search'],
            'method' => 'get',
        ]); ?>

        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="form-group mb-0">
                    <?= $form->field($searchModel, 'form_id')
                        ->dropDownList($availableForms, [
                            'prompt' => 'Выберите форму',
                            'class' => 'form-select'
                        ])
                        ->label(false)
                    ?>
                </div>
            </div>
            <div class="col">
                <div class="form-group mb-0">
                    <?= $form->field($searchModel, 'value')
                        ->textInput([
                            'placeholder' => 'Введите значение',
                            'class' => 'form-control'
                        ])
                        ->label(false)
                    ?>
                </div>
            </div>
            <div class="col-auto">
                <div class="form-group  d-flex gap-2 align-items-center h-100">
                    <?= Html::submitButton('Поиск', ['class' => 'btn btn-primary']) ?>
                    <?= Html::a('Очистить', ['search'], ['class' => 'btn btn-warning']) ?>
                </div>
            </div>
        </div>

        <?php ActiveForm::end(); ?>
    </div>




    <div class="container mt-3">
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

            <h3 class="mt-4"><?= Html::encode($form->form_name) ?></h3>


            <?php for ($i = 0; $i < $maxCount; $i++): ?>
                <?php
                $rowData = [];
                $recordIds = [];
                $imageColumn = '';
                $textColumnItems = [];

                foreach ($formFields as $field) {
                    $fieldId = $field->id;
                    $value = $fieldDataMap[$fieldId][$i]['data'] ?? $fieldDataMap[$fieldId][$i] ?? '';

                    $displayValue = '';

                    if ($field->type_id == 5 && is_string($value) && $value !== '') {
                        $ext = strtolower(pathinfo($value, PATHINFO_EXTENSION));
                        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'];

                        if (in_array($ext, $imageExtensions)) {
                            $url = Yii::getAlias('@web') . '/' . ltrim($value, '/');
                            $displayValue = Html::img($url, [
                                'style' => 'max-width: 200px; height: auto; border-radius: 8px;',
                                'alt' => basename($value),
                                'class' => 'img-thumbnail'
                            ]);
                            $imageColumn = $displayValue;
                        } else {
                            $displayValue = Html::a(basename($value), Yii::getAlias('@web') . '/' . ltrim($value, '/'), ['target' => '_blank']);
                            $textColumnItems[] = $displayValue;
                        }
                    } else {
                        if (is_string($value) && isValidUrl($value)) {
                            $displayValue = Html::a(Html::encode($value), $value, [
                                'target' => '_blank',
                                'rel' => 'noopener noreferrer'
                            ]);
                        } elseif (is_array($value)) {
                            $displayValue = Html::encode(implode(', ', $value));
                        } else {
                            $displayValue = Html::encode($value);
                        }

                        $textColumnItems[] = $displayValue;
                    }

                    $rowData[$fieldId] = $value;

                    if (isset($fieldDataMap[$fieldId][$i]['id'])) {
                        $recordIds[] = $fieldDataMap[$fieldId][$i]['id'];
                    }
                }
                ?>

                <div class="row mb-3 border p-2 rounded" style="align-items: center;">
                    <div class="col-auto">
                        <?= $imageColumn ?>
                    </div>
                    <div class="col text-start" style="word-break: break-word;">
                        <?= implode('<br>', $textColumnItems) ?>
                    </div>
                </div>
            <?php endfor; ?>
        <?php endforeach; ?>
    </div>

</div>
