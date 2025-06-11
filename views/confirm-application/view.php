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

        // Максимальное количество строк в текущем наборе данных
        $maxCount = 0;
        foreach ($fieldDataMap as $dataRows) {
            $maxCount = max($maxCount, count($dataRows));
        }

        // Если данных нет ни в одном поле формы, не отображаем таблицу
        if ($maxCount === 0) {
            continue;
        }
        ?>

        <div class="table-responsive mb-4">
            <h4><?= Html::encode($form->form_name) ?></h4>
            <table class="table table-bordered table-hover align-middle">
                <thead class="table-light">
                <tr>
                    <?php foreach ($formFields as $field): ?>
                        <th><?= Html::encode($field->field_name) ?></th>
                    <?php endforeach; ?>
                </tr>
                </thead>
                <tbody>
                <?php for ($i = 0; $i < $maxCount; $i++): ?>
                    <tr>
                        <?php foreach ($formFields as $field): ?>
                            <?php
                            $fieldId = $field->id;
                            $value = $fieldDataMap[$fieldId][$i]['data'] ?? $fieldDataMap[$fieldId][$i] ?? '';
                            $displayValue = '';

                            if ($field->type_id == 5 && is_string($value) && $value !== '') {
                                $ext = strtolower(pathinfo($value, PATHINFO_EXTENSION));
                                $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'];

                                if (in_array($ext, $imageExtensions)) {
                                    $url = Yii::getAlias('@web') . '/' . ltrim($value, '/');
                                    $displayValue = Html::img($url, [
                                        'style' => 'max-width: 120px; height: auto; border-radius: 4px;',
                                        'alt' => basename($value),
                                        'class' => 'img-thumbnail'
                                    ]);
                                } elseif (in_array($ext, ['pdf', 'doc', 'docx', 'xls', 'xlsx'])) {
                                    $url = Yii::getAlias('@web') . '/uploads/previews/' . ltrim($value, '/uploads');
                                    $previewUrl = rtrim($url, '.' . $ext) . '.jpg';
                                    $documentUrl = Yii::getAlias('@web') . $value;

                                    $displayValue = Html::a(
                                        Html::img($previewUrl, [
                                            'style' => 'max-width: 120px; height: auto; border-radius: 4px;',
                                            'alt' => basename($value),
                                            'class' => 'img-thumbnail',
                                        ]),
                                        $documentUrl,
                                        [
                                            'target' => '_blank',
                                            'title' => 'Открыть документ: ' . basename($value),
                                        ]
                                    );
                                } else {
                                    $displayValue = Html::a(basename($value), Yii::getAlias('@web') . '/' . ltrim($value, '/'), ['target' => '_blank']);
                                }
                            } else {
                                if (is_string($value) && filter_var($value, FILTER_VALIDATE_URL)) {
                                    $displayValue = Html::a(Html::encode($value), $value, [
                                        'target' => '_blank',
                                        'rel' => 'noopener noreferrer'
                                    ]);
                                } elseif (is_array($value)) {
                                    $displayValue = Html::encode(implode(', ', $value));
                                } else {
                                    $displayValue = Html::encode($value);
                                }
                            }
                            ?>
                            <td><?= $displayValue ?></td>
                        <?php endforeach; ?>
                    </tr>
                <?php endfor; ?>
                </tbody>
            </table>
        </div>
    <?php endforeach; ?>
</div>

