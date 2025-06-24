<?php

use app\models\DataSearch;
use app\models\Form;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
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

<head>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/core@latest/dist/css/tabler.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <title>Профиль</title>
</head>

<script src="https://cdn.jsdelivr.net/npm/@tabler/core@latest/dist/js/tabler.min.js"></script>

<div class="container mb-4">

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
                <div class="form-group d-flex gap-2 align-items-center h-100">
                    <?= Html::submitButton('<i class="ti ti-search me-1"></i>Поиск', ['class' => 'btn btn-primary']) ?>
                    <?= Html::a('<i class="ti ti-eraser me-1"></i>Очистить', ['search'], ['class' => 'btn btn-warning']) ?>
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

            if ($maxCount === 0) continue;
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
                    <?php
                    $recordIndexes = [];
                    foreach ($fieldDataMap as $fieldRows) {
                        foreach ($fieldRows as $recordIndex => $valueData) {
                            $recordIndexes[$recordIndex] = true;
                        }
                    }
                    foreach (array_keys($recordIndexes) as $recordIndex):
                        $userId = null;
                        foreach ($formFields as $f) {
                            $fieldId = $f->id;
                            if (isset($fieldDataMap[$fieldId][$recordIndex]['user_id'])) {
                                $userId = $fieldDataMap[$fieldId][$recordIndex]['user_id'];
                                break;
                            }
                        }
                        ?>
                        <tr class="clickable-row" data-user-id="<?= Html::encode($userId) ?>">
                            <?php foreach ($formFields as $field): ?>
                                <?php
                                $fieldId = $field->id;
                                $value = $fieldDataMap[$fieldId][$recordIndex]['data'] ?? ' - ';
                                $displayValue = '';

                                if ($field->type_id == 5 && is_string($value) && $value !== '') {
                                        $ext = strtolower(pathinfo(parse_url($value, PHP_URL_PATH), PATHINFO_EXTENSION));
                                        $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'];

                                        if (in_array($ext, $imageExtensions)) {
                                            $displayValue = Html::img($value, [
                                                'style' => 'max-width: 120px; height: auto; border-radius: 4px;',
                                                'class' => 'img-thumbnail',
                                            ]);
                                        } elseif (in_array($ext, ['pdf', 'doc', 'docx', 'xls', 'xlsx'])) {
                                            $parsedPath = parse_url($value, PHP_URL_PATH);
                                            $filename = pathinfo($parsedPath, PATHINFO_FILENAME);
                                            $previewKey = 'uploads/previews/' . $filename . '.jpg';

                                            try {
                                                $previewUrl = Yii::$app->s3->getPresignedUrl($previewKey, '+30 minutes');
                                            } catch (\Throwable $e) {
                                                Yii::error("Ошибка генерации preview S3 URL: " . $e->getMessage(), 'form');
                                                $previewUrl = null;
                                            }

                                            Yii::info($previewUrl);
                                            $displayValue = Html::a(
                                                Html::img($previewUrl, [
                                                    'style' => 'max-width: 120px; height: auto; border-radius: 4px;',
                                                    'class' => 'img-thumbnail',
                                                    'alt' => 'Превью'
                                                ]),
                                                $value,
                                                ['target' => '_blank']
                                            );
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
                                }
                                ?>
                                <td><?= $displayValue ?></td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<style>
    .clickable-row {
        cursor: pointer;
        transition: background-color 0.2s ease-in-out;
    }
    .clickable-row:hover {
        background-color: #f1f3f5;
    }
</style>

<?php
$this->registerJs(<<<JS
document.querySelectorAll('.clickable-row').forEach(row => {
    row.addEventListener('click', () => {
        const userId = row.dataset.userId;
        if (userId) {
            window.location.href = '/site/fetch-profile?profileId=' + encodeURIComponent(userId);
        }
    });
});
JS);
?>
