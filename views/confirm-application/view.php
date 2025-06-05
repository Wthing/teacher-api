<?php

use app\models\Form;
use yii\helpers\Html;
use yii\helpers\Json;

/** @var yii\web\View $this */
/** @var app\models\FormConfirmApplication $model */
/** @var Form[] $forms */
/** @var array $userData */

$this->title = "Заявка №{$model->id}";

function isValidUrl($url) {
    return filter_var($url, FILTER_VALIDATE_URL) !== false;
}

?>

<h1><?= Html::encode($this->title) ?></h1>

<p><strong>Создал:</strong> <?= Html::encode($model->creator->login ?? '-') ?></p>
<p><strong>Назначен:</strong> <?= Html::encode($model->assignee->login ?? '-') ?></p>
<p><strong>Статус:</strong> <?= Html::encode($model->getStatusLabel()) ?></p>
<p><strong>Дата создания:</strong> <?= Yii::$app->formatter->asDatetime($model->created_at) ?></p>

<div>
    <?= Html::a('Назад к списку', ['index'], ['class' => 'btn btn-secondary']) ?>

    <?php if ($model->status == $model::STATUS_PENDING): ?>
        <?= Html::a('Подтвердить', ['confirm', 'id' => $model->id], [
            'class' => 'btn btn-success',
            'data-method' => 'post',
            'data-confirm' => 'Вы уверены, что хотите подтвердить эту заявку?',
        ]) ?>
        <?= Html::a('Отклонить', ['reject', 'id' => $model->id], [
            'class' => 'btn btn-danger',
            'data-method' => 'post',
            'data-confirm' => 'Вы уверены, что хотите отклонить эту заявку?',
        ]) ?>
    <?php endif; ?>
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
