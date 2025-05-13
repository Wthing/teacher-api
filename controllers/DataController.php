<?php

namespace app\controllers;

use app\models\Data;
use app\models\FormField;
use Yii;
use yii\web\Controller;

class DataController extends Controller
{
    public function actionCreate($profile_id)
    {
        $formFields = FormField::find()->where(['form_id' => 1])->all();  // Replace '1' with dynamic form ID if needed
        $model = new Data();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('create', [
            'model' => $model,
            'formFields' => $formFields,
        ]);
    }

    public function actionUpdate($id)
    {
        $model = Data::findOne($id);
        $formFields = FormField::find()->where(['form_id' => 1])->all();  // Replace '1' with dynamic form ID if needed

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('update', [
            'model' => $model,
            'formFields' => $formFields,
        ]);
    }
}