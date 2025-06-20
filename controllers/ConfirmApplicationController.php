<?php

namespace app\controllers;

use app\models\Data;
use app\models\Form;
use app\models\FormConfirmApplication;
use app\models\FormConfirmPerson;
use app\models\FormField;
use Yii;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

class ConfirmApplicationController extends Controller
{
    public function actionIndex()
    {
        $userId = Yii::$app->user->id;

        $applications = FormConfirmApplication::find()->where(['assigned_to' => $userId])->orderBy(['created_at' => SORT_DESC])->all();

        return $this->render('index', [
            'applications' => $applications,
        ]);
    }

    public function actionView($id)
    {
        $model = $this->findModel($id);
        $forms = Form::find()->all();

        $userData = Data::find()->where(['record_index' => $model->record_index])->all();

        $groupedData = [];
        foreach ($userData as $data) {
            $groupedData[$data->field_id][] = [
                'id' => $data->id,
                'data' => $data->data,
            ];
        }

        $formFields = FormField::find()->with(['type', 'autocompleteOptions'])->all();

        return $this->render('view', [
            'model' => $model,
            'forms' => $forms,
            'formFields' => $formFields,
            'userData' => $groupedData,
        ]);
    }

    public function actionConfirm($id)
    {

        $model = $this->findModel($id);
        $userId = Yii::$app->user->identity->id;

        $records = Data::find()->where(['record_index' => $model->record_index])->all();
        $ff = FormField::findOne($records[0]->field_id);
        $assignees = FormConfirmPerson::find()->select('user_id')->where(['form_id' => $ff->form_id])->column();
        Yii::info($assignees);

        if (in_array($userId, $assignees)) {
            Yii::info('true');
            $model->confirm();


            foreach ($records as $record) {
                $record->verification_status = 1;
                $record->save();
            }

            Yii::$app->session->setFlash('success', 'Заявка подтверждена.');
            return $this->redirect(['index']);
        } else {
            Yii::info('false');
            Yii::$app->session->setFlash('error', 'Заявка не подтверждена.');
            return $this->redirect(['index']);
        }
    }

    public function actionReject($id)
    {
        $model = $this->findModel($id);

        $userId = Yii::$app->user->identity->id;

        $records = Data::find()->where(['record_index' => $model->record_index])->all();
        $ff = FormField::findOne($records[0]->field_id);
        $assignees = FormConfirmPerson::find()->select('user_id')->where(['form_id' => $ff->form_id])->column();

        if (in_array($userId, $assignees)) {
            $model->reject();

            Yii::$app->session->setFlash('warning', 'Заявка отклонена.');
            return $this->redirect(['index']);
        } else {
            Yii::$app->session->setFlash('warning', 'Шта?');
            return $this->redirect(['index']);
        }
    }

    protected function findModel($id)
    {
        if (($model = FormConfirmApplication::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('Заявка не найдена.');
    }
}
