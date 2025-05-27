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

        $allForms = Form::find()
            ->where(['status' => true])
            ->orderBy(['form_name' => SORT_ASC])
            ->all();

        $formsQuery = Form::find()
            ->joinWith(['formFields.data d'])
            ->where(['forms.status' => true])
            ->andWhere(['IS NOT', 'd.data', null]);

        if (!empty($searchModel->form_id)) {
            $formsQuery->andWhere(['forms.id' => $searchModel->form_id]);
        }

        $forms = $formsQuery->all();

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
            'allForms' => $allForms,
            'fields' => $formFields,
            'userData' => $groupedData,
            'searchModel' => $searchModel,
        ]);
    }






}