<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var app\models\FormConfirmApplication[] $applications */

$this->title = 'Заявки на подтверждение';
?>

    <head>
        <!-- MDB CSS -->
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/core@latest/dist/css/tabler.min.css">
        <title>Профиль</title>
    </head>

    <script src="https://cdn.jsdelivr.net/npm/@tabler/core@latest/dist/js/tabler.min.js"></script>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="fw-bold"><?= Html::encode($this->title) ?></h1>
        </div>

        <?php if (empty($applications)): ?>
            <div class="alert alert-info">Нет заявок для подтверждения.</div>
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
                                <span class="badge bg-<?= $class ?>"><?= Html::encode($label) ?></span>
                            </td>
                            <td class="text-center">
                                <div class="btn-group" role="group" aria-label="Действия">
                                    <?= Html::a('🔍', ['view', 'id' => $application->id], [
                                        'class' => 'btn btn-outline-primary btn-sm',
                                        'data-bs-toggle' => 'tooltip',
                                        'title' => 'Просмотреть',
                                    ]) ?>
                                    <?php if ($status == $application::STATUS_PENDING): ?>
                                        <?= Html::a('✅', ['confirm', 'id' => $application->id], [
                                            'class' => 'btn btn-outline-success btn-sm',
                                            'data-method' => 'post',
                                            'data-confirm' => 'Вы уверены, что хотите подтвердить эту заявку?',
                                            'data-bs-toggle' => 'tooltip',
                                            'title' => 'Подтвердить',
                                        ]) ?>
                                        <?= Html::a('❌', ['reject', 'id' => $application->id], [
                                            'class' => 'btn btn-outline-danger btn-sm',
                                            'data-method' => 'post',
                                            'data-confirm' => 'Вы уверены, что хотите отклонить эту заявку?',
                                            'data-bs-toggle' => 'tooltip',
                                            'title' => 'Отклонить',
                                        ]) ?>
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
// Bootstrap tooltip активация
$this->registerJs("var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle=\"tooltip\"]')); tooltipTriggerList.map(function (tooltipTriggerEl) { return new bootstrap.Tooltip(tooltipTriggerEl); });");
?>