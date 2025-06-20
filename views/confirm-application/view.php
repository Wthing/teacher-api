<?php
/**  @var yii\web\View                     $this
 *   @var app\models\FormConfirmApplication $model
 *   @var app\models\Form[]                $forms
 *   @var array                            $userData
 */

use yii\helpers\Html;

$this->title = "Заявка №{$model->id}";

/* — mini‑helper — */
$isUrl = fn($v) => is_string($v) && filter_var($v, FILTER_VALIDATE_URL);
$fmt   = Yii::$app->formatter;
?>

<head>
    <!-- MDB CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/core@latest/dist/css/tabler.min.css">
    <title>Профиль</title>
</head>

<script src="https://cdn.jsdelivr.net/npm/@tabler/core@latest/dist/js/tabler.min.js"></script>

<div class="container py-4">

    <!-- ===== Card‑шапка заявки ===== -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">

            <div>
                <h2 class="mb-2">Заявка №<?= $model->id ?></h2>

                <span class="text-muted small me-2">
                    Создал: <strong><?= Html::encode($model->creator->username ?? '—') ?></strong>
                </span>
                <span class="text-muted small">
                    Назначен: <strong><?= Html::encode($model->assignee->username ?? '—') ?></strong>
                </span>
            </div>

            <!-- Статус в виде яркой «badgy» -->
            <?php
            $badgeClass = [
                $model::STATUS_PENDING   => 'bg-warning text-dark',
                $model::STATUS_CONFIRMED  => 'bg-success',
                $model::STATUS_REJECTED  => 'bg-danger',
            ][$model->status] ?? 'bg-secondary';
            ?>
            <span class="badge <?= $badgeClass ?> py-2 px-3 fs-6">
                <?= Html::encode($model->getStatusLabel()) ?>
            </span>
        </div>

        <div class="card-footer d-flex flex-wrap justify-content-between align-items-center gap-2">
            <small class="text-muted">
                Дата создания: <?= $fmt->asDatetime($model->created_at) ?>
            </small>

            <div class="btn-group">
                <?= Html::a('↩︎ К списку', ['index'], ['class' => 'btn btn-outline-secondary btn-sm']) ?>

                <?php if ($model->status === $model::STATUS_PENDING): ?>
                    <?= Html::a('✅ Подтвердить', ['confirm', 'id' => $model->id], [
                        'class' => 'btn btn-success btn-sm',
                        'data-method'  => 'post',
                        'data-confirm' => 'Подтвердить заявку?'
                    ]) ?>
                    <?= Html::a('❌ Отклонить', ['reject', 'id' => $model->id], [
                        'class' => 'btn btn-danger btn-sm',
                        'data-method'  => 'post',
                        'data-confirm' => 'Отклонить заявку?'
                    ]) ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ===== Данные по формам ===== -->
    <?php foreach ($forms as $form):

        // Подготовка данных
        $formFields   = $form->formFields;
        $fieldDataMap = array_map(
            fn($f) => $userData[$f->id] ?? [],
            $formFields
        );
        $maxRows = max(array_map('count', $fieldDataMap));

        if (!$maxRows) { continue; }

        ?>
        <div class="card border-0 shadow-sm mb-5">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><?= Html::encode($form->form_name) ?></h5>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered align-middle mb-0">
                    <thead class="table-light">
                    <tr>
                        <?php foreach ($formFields as $field): ?>
                            <th><?= Html::encode($field->field_name) ?></th>
                        <?php endforeach; ?>
                    </tr>
                    </thead>

                    <tbody>
                    <?php for ($r = 0; $r < $maxRows; $r++): ?>
                        <tr>
                            <?php foreach ($formFields as $field): ?>
                                <td>
                                    <?php
                                    $raw  = $fieldDataMap[$field->id][$r]['data'] ?? $fieldDataMap[$field->id][$r] ?? '';
                                    $out  = Html::encode($raw);

                                    /* — файлы — */
                                    if ($field->type_id == 5 && is_string($raw) && $raw !== '') {
                                        $ext = strtolower(pathinfo($raw, PATHINFO_EXTENSION));
                                        $imgOk = ['jpg','jpeg','png','gif','bmp','webp'];
                                        $docOk = ['pdf','doc','docx','xls','xlsx'];

                                        if (in_array($ext, $imgOk)) {
                                            $src = Yii::getAlias('@web/'.ltrim($raw,'/'));
                                            $out = Html::img($src,['style'=>'max-width:100px','class'=>'rounded']);
                                        } elseif (in_array($ext,$docOk)) {
                                            $thumb = Yii::getAlias('@web/uploads/previews/'.ltrim($raw,'/uploads'));
                                            $thumb = rtrim($thumb,'.'.$ext).'.jpg';
                                            $out   = Html::a(
                                                Html::img($thumb,['style'=>'max-width:100px','class'=>'rounded shadow-sm']),
                                                '/'.$raw, ['target'=>'_blank']
                                            );
                                        } else {
                                            $out = Html::a(basename($raw), '/'.$raw, ['target'=>'_blank']);
                                        }

                                        /* — URL — */
                                    } elseif ($isUrl($raw)) {
                                        $out = Html::a(Html::encode($raw), $raw, ['target'=>'_blank']);
                                        /* — массив — */
                                    } elseif (is_array($raw)) {
                                        $out = Html::encode(implode(', ', $raw));
                                    }
                                    echo $out;
                                    ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endfor; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endforeach; ?>
    <!-- ===== /данные ===== -->

</div>
