<?php

namespace app\controllers;

use app\models\Form;
use app\models\FormConfirmPerson;
use app\models\FormField;
use app\models\FormFieldAutocomplete;
use app\models\FormFieldType;
use Yii;
use yii\filters\AccessControl;
use yii\web\BadRequestHttpException;
use yii\web\Controller;
use yii\web\Response;

class SuperUserController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'rules' => [
                    [
                        'allow' => true,
                        'roles' => ['admin', 'super-teacher'],
                    ],
                ],
            ],
        ];
    }

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
                    if ($form['requires_verification']) {
                        $field->status = 1;
                    }

                    if (!$field->save()) {
                        throw new \Exception('Ошибка при сохранении поля: ' . json_encode($field->errors));
                    }
                }

                if ($form->requires_verification) {
                    $formConfirmPost = Yii::$app->request->post('FormConfirmPerson');
                    Yii::info($formConfirmPost);
                    $formConfirmPerson = new FormConfirmPerson();
                    $formConfirmPerson->form_id = $form->id;
                    $formConfirmPerson->user_id = $formConfirmPost['profile_id'] ?? null;

                    if (!$formConfirmPerson->save()) {
                        throw new \Exception('Ошибка при сохранении поля: ' . json_encode($formConfirmPerson->errors));
                    }
                }

                $transaction->commit();
                return $this->redirect(['index']);

            } catch (\Exception $e) {
                $transaction->rollBack();
                Yii::$app->session->setFlash('error', 'Ошибка при сохранении: ' . $e->getMessage());
            }
        }

        return $this->render('admin', [
            'form' => $form,
        ]);
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

    public function actionCreateTypes()
    {
        $request = Yii::$app->request;

        if ($request->isPost) {
            $entries = $request->post('FormFieldTypes', []);
            $successCount = 0;
            $errors = [];

            foreach ($entries as $i => $data) {
                $model = new FormFieldType();
                $model->type_name = $data['type_name'] ?? null;
                $model->status = $data['status'] ?? 0;

                if ($model->validate() && $model->save()) {
                    $successCount++;
                } else {
                    $errors[$i] = $model->errors;
                }
            }

            if ($successCount > 0) {
                Yii::$app->session->setFlash('success', "Успешно добавлено {$successCount} новых типа(ов).");
            }

            if (!empty($errors)) {
                Yii::$app->session->setFlash('error', "Некоторые типы не были сохранены: " . json_encode($errors));
            }

            return $this->redirect(['index']);
        }

        throw new \yii\web\BadRequestHttpException('Неверный запрос.');
    }

}