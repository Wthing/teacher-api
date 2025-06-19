<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var app\models\FormConfirmApplication[] $applications */

$this->title = 'Заявки на подтверждение';
?>

<h1><?= Html::encode($this->title) ?></h1>

<table class="table table-bordered">
    <thead>
    <tr>
        <th>ID</th>
        <th>Создано</th>
        <th>Ответственный</th>
        <th>Время заявки</th>
        <th>Время подтверждения/отклонения</th>
        <th>Статус</th>
        <th>Действия</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($applications as $application): ?>
        <tr>
            <td><?= $application->id ?></td>
            <td><?= $application->creator->username ?? 'Неизвестно' ?></td>
            <td><?= $application->assignee->username ?? 'Неизвестно' ?></td>
            <td><?= date('Y-m-d H:i:s', $application->created_at) ?></td>
            <td><?= date('Y-m-d H:i:s', $application->confirmed_at) ?></td>
            <td><?= $application->getStatusLabel() ?></td>
            <td>
                <?= Html::a('Просмотр', ['view', 'id' => $application->id], ['class' => 'btn btn-primary btn-sm']) ?>
                <?php if ($application->status == $application::STATUS_PENDING): ?>
                    <?= Html::a('Подтвердить', ['confirm', 'id' => $application->id], [
                        'class' => 'btn btn-success btn-sm',
                        'data-method' => 'post',
                        'data-confirm' => 'Вы уверены, что хотите подтвердить эту заявку?',
                    ]) ?>
                    <?= Html::a('Отклонить', ['reject', 'id' => $application->id], [
                        'class' => 'btn btn-danger btn-sm',
                        'data-method' => 'post',
                        'data-confirm' => 'Вы уверены, что хотите отклонить эту заявку?',
                    ]) ?>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
