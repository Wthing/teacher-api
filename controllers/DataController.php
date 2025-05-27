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

        // ВСЕ формы для выпадающего списка (нефильтрованные)
        $allForms = Form::find()
            ->where(['status' => true])
            ->orderBy(['form_name' => SORT_ASC])
            ->all();

        // Только те формы, у которых есть данные
        $formsQuery = Form::find()
            ->joinWith(['formFields.data d'])
            ->where(['forms.status' => true])
            ->andWhere(['IS NOT', 'd.data', null]);

        // Применяем фильтр ТОЛЬКО если нужно отображать данные по одной форме
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
            'forms' => $forms,              // отображаемые формы с данными
            'allForms' => $allForms,        // все формы для дропа
            'fields' => $formFields,
            'userData' => $groupedData,
            'searchModel' => $searchModel,
        ]);
    }






}