<?php

namespace app\controllers;

use app\models\Data;
use app\models\FormFieldAutocomplete;
use Yii;
use yii\web\Controller;
use yii\web\Response;

class UserController extends Controller
{
    public function actionAutocompleteOptions($field_id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        return FormFieldAutocomplete::find()->where(['field_id' => $field_id])->all();
    }

    public function actionSaveField()
    {
        $profileId = Yii::$app->user->id;
        $formId = Yii::$app->request->post('form_id');
        $fieldId = Yii::$app->request->post('field_id');
        $value = Yii::$app->request->post('value');

        $data = Data::findOne(['profile_id' => $profileId, 'form_id' => $formId]);
        if (!$data) {
            $data = new Data([
                'profile_id' => $profileId,
                'form_id' => $formId,
                'data' => json_encode([$fieldId => $value]),
            ]);
        } else {
            $existing = json_decode($data->data, true);
            $existing[$fieldId] = $value;
            $data->data = json_encode($existing);
        }

        $data->save();
        return $this->redirect(['profile']);
    }
}