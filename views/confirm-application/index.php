<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var app\models\FormConfirmApplication[] $applications */

$this->title = 'Заявки на подтверждение';
?>

<head>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/core@latest/dist/css/tabler.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css">
    <title><?= Html::encode($this->title) ?></title>
</head>

<script src="https://cdn.jsdelivr.net/npm/@tabler/core@latest/dist/js/tabler.min.js"></script>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="fw-bold"><?= Html::encode($this->title) ?></h1>
    </div>

    <?php if (empty($applications)): ?>
        <div class="alert alert-info d-flex align-items-center">
            <i class="ti ti-info-circle me-2"></i> Нет заявок для подтверждения.
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover table-bordered align-middle shadow-sm rounded">
                <thead class="table-primary text-center">
                <tr>
                    <th>ID</th>
                    <th>Создано</th>
                    <th>Ответственный</th>
                    <th>Дата заявки</th>
                    <th>Дата обработки</th>
                    <th>Статус</th>
                    <th>Действия</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($applications as $application): ?>
                    <?php
                    $status = $application->status;
                    $label = $application->getStatusLabel();
                    $class = match ($status) {
                        $application::STATUS_PENDING => 'warning',
                        $application::STATUS_CONFIRMED => 'success',
                        $application::STATUS_REJECTED => 'danger',
                        default => 'secondary'
                    };
                    ?>
                    <tr>
                        <td class="text-center"><?= $application->id ?></td>
                        <td><?= Html::encode($application->creator->username ?? '—') ?></td>
                        <td><?= Html::encode($application->assignee->username ?? '—') ?></td>
                        <td><?= Yii::$app->formatter->asDatetime($application->created_at, 'php:d.m.Y H:i') ?></td>
                        <td>
                            <?= $application->confirmed_at
                                ? Yii::$app->formatter->asDatetime($application->confirmed_at, 'php:d.m.Y H:i')
                                : '—' ?>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-<?= $class ?>"><?= Html::encode($label) ?></span>
                        </td>
                        <td class="text-center">
                            <div class="d-flex justify-content-center align-items-center gap-1">

                                <?= Html::a('<i class="ti ti-eye"></i>', ['view', 'id' => $application->id], [
                                    'class' => 'btn btn-outline-primary btn-sm d-flex align-items-center justify-content-center',
                                    'style' => 'height:32px; width:32px; padding:0;',
                                ]) ?>

                                <?php if ($status == $application::STATUS_PENDING): ?>

                                    <?= Html::beginForm(['confirm', 'id' => $application->id], 'post', ['class' => 'd-inline']) ?>
                                    <?= Html::submitButton('<i class="ti ti-check"></i>', [
                                        'class' => 'btn btn-outline-success btn-sm d-flex align-items-center justify-content-center',
                                        'style' => 'height:32px; width:32px; padding:0;',
                                        'data-confirm' => 'Вы уверены, что хотите подтвердить эту заявку?',
                                    ]) ?>
                                    <?= Html::endForm() ?>

                                    <?= Html::beginForm(['reject', 'id' => $application->id], 'post', ['class' => 'd-inline']) ?>
                                    <?= Html::submitButton('<i class="ti ti-x"></i>', [
                                        'class' => 'btn btn-outline-danger btn-sm d-flex align-items-center justify-content-center',
                                        'style' => 'height:32px; width:32px; padding:0;',
                                        'data-confirm' => 'Вы уверены, что хотите отклонить эту заявку?',
                                    ]) ?>
                                    <?= Html::endForm() ?>

                                <?php endif; ?>
                            </div>
                        </td>


                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php
$this->registerJs("
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle=\"tooltip\"]'));
    tooltipTriggerList.map(el => new bootstrap.Tooltip(el));
");
?>
