<?php

use app\models\DataSearch;
use app\models\Form;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\widgets\ActiveForm;

/** @var DataSearch $searchModel */
/** @var Form[] $forms */
/** @var array $fields */
/** @var array $userData */

function isValidUrl($url) {
    return filter_var($url, FILTER_VALIDATE_URL) !== false;
}

$availableForms = ArrayHelper::map($forms, 'id', 'form_name');

?>

<div class="data-search">

    <?php $form = ActiveForm::begin([
        'action' => ['search'],
        'method' => 'get',
    ]); ?>

    <div>
        <?= $form->field($searchModel, 'form_id')->dropDownList($availableForms, ['prompt' => 'Выберите форму']) ?>
        <?= $form->field($searchModel, 'value')->textInput(['placeholder' => 'Введите значение']) ?>
    </div>

    <div class="form-group">
        <?= Html::submitButton('Поиск', ['class' => 'btn btn-primary']) ?>
    </div>

    <?php ActiveForm::end(); ?>

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
                $rowDisplay = [];
                $rowData = [];
                $recordIds = [];

                foreach ($formFields as $field) {
                    $fieldId = $field->id;
                    $value = $fieldDataMap[$fieldId][$i]['data'] ?? $fieldDataMap[$fieldId][$i] ?? '';

                    if ($field->type_id == 5 && is_string($value) && $value !== '') {
                        $ext = strtolower(pathinfo($value, PATHINFO_EXTENSION));
                        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'];

                        if (in_array($ext, $imageExtensions)) {
                            $url = Yii::getAlias('@web') . '/' . ltrim($value, '/');
                            $imgTag = Html::img($url, ['style' => 'max-height:100px; max-width:150px; margin-right:10px;', 'alt' => basename($value)]);
                            $rowDisplay[] = $imgTag;
                        } else {
                            $rowDisplay[] = Html::a(basename($value), Yii::getAlias('@web') . '/' . ltrim($value, '/'), ['target' => '_blank']);
                        }
                    } else {
                        if (is_string($value) && isValidUrl($value)) {
                            $rowDisplay[] = Html::a(
                                Html::encode($value),
                                $value,
                                ['target' => '_blank', 'rel' => 'noopener noreferrer']
                            );
                        } else if (is_array($value)) {
                            $rowDisplay[] = Html::encode(implode(', ', $value));
                        } else {
                            $rowDisplay[] = Html::encode($value);
                        }
                    }

                    $rowData[$fieldId] = $value;
                    if (isset($fieldDataMap[$fieldId][$i]['id'])) {
                        $recordIds[] = $fieldDataMap[$fieldId][$i]['id'];
                    }
                }
                ?>

                <div class="row mb-2 align-items-center" style="white-space: nowrap; overflow-x: auto;">
                    <div class="col-md-10 text-truncate data-row" style="white-space: nowrap; overflow-x: auto; display: flex; align-items: center; gap: 10px;">
                        <?= implode(' - ', $rowDisplay) ?>
                    </div>
                </div>
            <?php endfor; ?>
        <?php endforeach; ?>
    </div>

</div>
