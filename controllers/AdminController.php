<?php

namespace app\controllers;

use app\models\Data;
use app\models\Form;
use app\models\FormField;
use app\models\FormFieldAutocomplete;
use Yii;
use yii\web\BadRequestHttpException;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class AdminController extends Controller
{
    public function actionIndex()
    {
        $fields = FormField::find()->all();

        return $this->render('admin',
            ['fields' => $fields]);
    }

    public function actionCreate()
    {
        $form = new Form();

        if ($form->load(Yii::$app->request->post())) {

            $transaction = Yii::$app->db->beginTransaction();
            try {
                if (!$form->save()) {
                    throw new \Exception('Ошибка при сохранении формы.');
                }

                $fields = Yii::$app->request->post('fields', []);
                foreach ($fields as $fieldData) {
                    $field = new FormField();
                    $field->form_id = $form->id;
                    $field->field_name = $fieldData['field_name'] ?? null;
                    $field->type_id = $fieldData['type_id'] ?? null;

                    if (!$field->save()) {
                        throw new \Exception('Ошибка при сохранении поля: ' . json_encode($field->errors));
                    }
                }

                $transaction->commit();

                return $this->redirect(['index']);
            } catch (\Exception $e) {
                $transaction->rollBack();
                Yii::$app->session->setFlash('error', 'Ошибка при сохранении: ' . $e->getMessage());
            }
        }

        return $this->render('create', [
            'form' => $form,
        ]);
    }

    public function actionConfirm($recordIndex)
    {
        $dataRecords = Data::find()->where(['record_index' => $recordIndex])->all();

        if (empty($dataRecords)) {
            throw new NotFoundHttpException('Данные не найдены');
        }

        // Пример отображения: можешь заменить на форму с кнопкой "Подтвердить"
        return $this->render('confirm', [
            'records' => $dataRecords,
        ]);
    }

    public function actionApprove()
    {
        $recordIndex = Yii::$app->request->post('record_index');

        $records = Data::find()->where(['record_index' => $recordIndex])->all();

        foreach ($records as $record) {
            $record->verification_status = Data::STATUS_VERIFIED; // нужно добавить поле в таблицу!
            $record->save(false);
        }

        Yii::$app->session->setFlash('success', 'Данные подтверждены');
        return $this->redirect(['index']); // или куда нужно
    }


    public function actionFetchFieldsByFormId($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $form = Form::findOne($id);
        if (!$form) {
            return ['success' => false, 'error' => 'Форма не найдена'];
        }

        $fields = FormField::find()
            ->where(['form_id' => $form->id])
            ->joinWith('type') // подключаем связь
            ->select(['form_fields.field_name', 'form_fields_type.type_name AS type_name']) // поле из связанной таблицы
            ->asArray()
            ->all();

        return [
            'success' => true,
            'form_name' => $form->form_name,
            'fields' => $fields,
        ];
    }

    public function actionDeleteForm()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $id = Yii::$app->request->post('id');

        $form = Form::findOne($id);
        if (!$form) {
            return ['success' => false, 'error' => 'Форма не найдена'];
        }

        try {
            $form->status = false;
            if ($form->save()) {
                return ['success' => true];
            } else {
                return ['success' => false, 'error' => 'Не удалось сохранить изменения.'];
            }
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function actionRestoreForm()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $id = Yii::$app->request->post('id');
        $form = Form::findOne($id);

        if (!$form) {
            return ['success' => false, 'error' => 'Форма не найдена.'];
        }

        $form->status = true;

        if ($form->save()) {
            return ['success' => true];
        }

        return ['success' => false, 'error' => 'Не удалось сохранить изменения.'];
    }

    public function actionCreateAutocomplete()
    {
        $request = Yii::$app->request;

        if ($request->isPost) {
            $entries = $request->post('FormFieldAutocompleteEntries', []);

            $successCount = 0;
            $errors = [];

            foreach ($entries as $i => $entryData) {
                $model = new FormFieldAutocomplete();
                $model->field_id = $entryData['field_id'] ?? null;
                $model->content = $entryData['content'] ?? null;

                if ($model->validate() && $model->save()) {
                    $successCount++;
                } else {
                    $errors[$i] = $model->errors;
                }
            }

            if ($successCount > 0) {
                Yii::$app->session->setFlash('success', "Успешно добавлено {$successCount} записей автозаполнения.");
            }

            if (!empty($errors)) {
                Yii::$app->session->setFlash('error', "Некоторые записи не были сохранены. Ошибки: " . json_encode($errors));
            }

            return $this->redirect(['admin/index']);
        }

        throw new BadRequestHttpException('Неверный запрос.');
    }


}