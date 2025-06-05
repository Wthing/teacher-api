<?php

namespace app\controllers;

use app\models\Data;
use Yii;
use app\models\FormConfirmApplication;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

class ConfirmApplicationController extends Controller
{
    public function actionIndex()
    {
        $applications = FormConfirmApplication::find()->orderBy(['created_at' => SORT_DESC])->all();

        return $this->render('index', [
            'applications' => $applications,
        ]);
    }

    public function actionView($id)
    {
        $model = $this->findModel($id);

        return $this->render('view', [
            'model' => $model,
        ]);
    }

    public function actionConfirm($id)
    {
        $model = $this->findModel($id);
        $model->confirm();

        $records = Data::find()->where(['record_index' => $model->record_index])->all();
        foreach ($records as $record) {
            $record->verification_status = 1;
            $record->save();
        }

        Yii::$app->session->setFlash('success', 'Заявка подтверждена.');
        return $this->redirect(['index']);
    }

    public function actionReject($id)
    {
        $model = $this->findModel($id);
        $model->reject();

        Yii::$app->session->setFlash('warning', 'Заявка отклонена.');
        return $this->redirect(['index']);
    }

    protected function findModel($id)
    {
        if (($model = FormConfirmApplication::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('Заявка не найдена.');
    }
}
