<?php
/** @var \app\models\Data[] $records */

use yii\helpers\Url;

?>
<h2>Подтверждение данных</h2>

<?php foreach ($records as $record): ?>
    <div style="margin-bottom: 10px">
        <strong>Поле #<?= $record->field_id ?>:</strong>
        <?php if (strpos($record->data, 'uploads/') === 0): ?>
            <p><a href="/<?= $record->data ?>" target="_blank">Скачать файл</a></p>
        <?php else: ?>
            <p><?= htmlspecialchars($record->data) ?></p>
        <?php endif; ?>
    </div>
<?php endforeach; ?>

<form method="post" action="<?= Url::to(['admin/approve']) ?>">
    <input type="hidden" name="_csrf" value="<?= Yii::$app->request->csrfToken ?>">
    <input type="hidden" name="record_index" value="<?= $records[0]->record_index ?>">
    <button type="submit" class="btn btn-success">Подтвердить</button>
</form>
