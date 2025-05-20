<?php

namespace app\controllers;

use app\models\Form;
use app\models\FormField;
use Yii;
use yii\db\Exception;
use yii\web\Controller;
use yii\web\Response;

class AdminController extends Controller
{
    public function actionAdmin()
    {
        $fields = FormField::find()->all();

        return $this->render('admin',
            ['fields' => $fields]);
    }

    /**
     * @throws Exception
     */
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

                return $this->redirect(['admin/admin']);
            } catch (\Exception $e) {
                $transaction->rollBack();
                Yii::$app->session->setFlash('error', 'Ошибка при сохранении: ' . $e->getMessage());
            }
        }

        return $this->render('create', [
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




}