<?php

namespace app\controllers;

use app\models\Data;
use app\models\DataSearch;
use app\models\Form;
use app\models\FormField;
use Yii;
use yii\web\Controller;

class DataController extends Controller
{
    public function actionSearch()
    {
        $searchModel = new DataSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        $forms = Form::find()->joinWith(['formFields.data d'])->where(['forms.status' => true])->andWhere(['IS NOT', 'd.data', null])->all();
        $formFields = FormField::find()->with(['type', 'autocompleteOptions'])->all();

        $rawData = $dataProvider->getModels();
        $groupedData = [];
        foreach ($rawData as $data) {
            $groupedData[$data->field_id][] = [
                'id' => $data->id,
                'data' => $data->data,
            ];
        }

        return $this->render('index', [
            'forms' => $forms,
            'fields' => $formFields,
            'userData' => $groupedData,
            'searchModel' => $searchModel,
        ]);
    }





}