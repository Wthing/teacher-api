<?php

namespace app\controllers;

use app\models\DataSearch;
use app\models\Form;
use app\models\FormField;
use mdm\admin\components\AccessControl;
use Yii;
use yii\web\Controller;

class DataController extends Controller
{

    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
            ]
        ];
    }

    public function actionSearch()
    {
        $s3 = Yii::$app->s3;

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
            $url = $data->data;

            if (is_string($url) && (str_starts_with($url, 'uploads/') || str_starts_with($url, 'uploads/previews/'))) {
                try {
                    // Генерация временной ссылки
                    $url = $s3->getPresignedUrl($url, '+30 minutes');
                } catch (\Throwable $e) {
                    Yii::error("Ошибка S3 URL: " . $e->getMessage(), 'form');
                    $url = null;
                }
            }

            $groupedData[$data->field_id][] = [
                'id' => $data->id,
                'user_id' => $data->user_id,
                'data' => $url,
            ];
        }

        Yii::info($groupedData);

        return $this->render('index', [
            'forms' => $forms,
            'allForms' => $allForms,
            'fields' => $formFields,
            'userData' => $groupedData,
            'searchModel' => $searchModel,
        ]);
    }






}