<?php

use yii\helpers\Html;

/** @var yii\web\View $this */
/** @var app\models\FormConfirmApplication $model */

$this->title = "Заявка №{$model->id}";
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
