<?php

namespace app\services;

use app\models\Form;
use app\models\FormConfirmPerson;
use app\models\FormField;
use app\models\FormFieldAutocomplete;
use app\models\FormFieldType;
use Yii;

class SuperUserService
{
    public function createFormWithFields(Form $form, array $fields, ?array $confirmPersonData): bool
    {
        $transaction = Yii::$app->db->beginTransaction();
        try {
            if (!$form->save()) {
                throw new \Exception('Ошибка при сохранении формы.');
            }

            foreach ($fields as $fieldData) {
                $field = new FormField();
                $field->form_id = $form->id;
                $field->field_name = $fieldData['field_name'] ?? null;
                $field->type_id = $fieldData['type_id'] ?? null;
                if ($form->requires_verification) {
                    $field->status = 1;
                }

                if (!$field->save()) {
                    throw new \Exception('Ошибка при сохранении поля: ' . json_encode($field->errors));
                }
            }

            if ($form->requires_verification && $confirmPersonData) {
                $confirmPerson = new FormConfirmPerson([
                    'form_id' => $form->id,
                    'user_id' => $confirmPersonData['user_id'] ?? null,
                ]);
                if (!$confirmPerson->save()) {
                    throw new \Exception('Ошибка при сохранении подтверждающего: ' . json_encode($confirmPerson->errors));
                }
            }

            $transaction->commit();
            return true;

        } catch (\Exception $e) {
            $transaction->rollBack();
            Yii::$app->session->setFlash('error', $e->getMessage());
            return false;
        }
    }

    public function softDeleteForm($id): array
    {
        $form = Form::findOne($id);
        if (!$form) return ['success' => false, 'error' => 'Форма не найдена'];
        $form->status = false;
        return ['success' => $form->save()];
    }

    public function restoreForm($id): array
    {
        $form = Form::findOne($id);
        if (!$form) return ['success' => false, 'error' => 'Форма не найдена'];
        $form->status = true;
        return ['success' => $form->save()];
    }

    public function getFieldsByFormId($id): array
    {
        $form = Form::findOne($id);
        if (!$form) return ['success' => false, 'error' => 'Форма не найдена'];

        $fields = FormField::find()
            ->where(['form_id' => $form->id])
            ->joinWith('type')
            ->select(['form_fields.field_name', 'form_fields_type.type_name AS type_name'])
            ->asArray()
            ->all();

        return [
            'success' => true,
            'form_name' => $form->form_name,
            'fields' => $fields,
        ];
    }

    public function createAutocompleteEntries(array $entries): array
    {
        $success = 0;
        $errors = [];

        foreach ($entries as $i => $data) {
            $model = new FormFieldAutocomplete([
                'field_id' => $data['field_id'] ?? null,
                'content' => $data['content'] ?? null,
            ]);

            if ($model->validate() && $model->save()) {
                $success++;
            } else {
                $errors[$i] = $model->errors;
            }
        }

        return ['successCount' => $success, 'errors' => $errors];
    }

    public function createTypes(array $entries): array
    {
        $success = 0;
        $errors = [];

        foreach ($entries as $i => $data) {
            $model = new FormFieldType([
                'type_name' => $data['type_name'] ?? null,
                'status' => 0,
            ]);

            if ($model->validate() && $model->save()) {
                $success++;
            } else {
                $errors[$i] = $model->errors;
            }
        }

        return ['successCount' => $success, 'errors' => $errors];
    }
}