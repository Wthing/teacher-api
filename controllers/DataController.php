<?php

namespace app\controllers;

use app\models\DataSearch;
use Yii;
use yii\web\Controller;

class DataController extends Controller
{
    public function actionSearch()
    {
        $searchModel = new DataSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }
}