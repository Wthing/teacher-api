<?php
// views/data/create.php
use yii\widgets\ActiveForm;
use yii\helpers\Html;

?>

<div class="data-create">
    <h1>Create Data Entry</h1>

    <?php $form = ActiveForm::begin(); ?>

    <?php foreach ($formFields as $field): ?>
        <div class="form-group">
            <label for="data-<?= $field->id ?>"><?= $field->field_name ?></label>
            <?php if ($field->type_id == 1): ?>
                <!-- Example for text field type (change as necessary based on field type) -->
                <?= $form->field($model, 'data[]')->textInput(['id' => 'data-' . $field->id]) ?>
            <?php elseif ($field->type_id == 2): ?>
                <!-- Example for a select dropdown -->
                <?= $form->field($model, 'data[]')->dropDownList(FormFieldAutocomplete::getOptions($field->id), ['id' => 'data-' . $field->id]) ?>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>

    <div class="form-group">
        <?= Html::submitButton('Create', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>
</div>
