<?php

namespace app\controllers;

use app\models\Form;
use app\services\SuperUserService;
use Yii;
use yii\web\Controller;
use yii\web\Response;
use yii\web\BadRequestHttpException;
use mdm\admin\components\AccessControl;

class SuperUserController extends Controller
{
    private SuperUserService $service;

    public function __construct($id, $module, SuperUserService $service, $config = [])
    {
        $this->service = $service;
        parent::__construct($id, $module, $config);
    }

    public function behaviors()
    {
        return ['access' => ['class' => AccessControl::class]];
    }

    public function actionIndex()
    {
        return $this->render('admin', ['fields' => \app\models\FormField::find()->all()]);
    }

    public function actionCreate()
    {
        $form = new Form();

        if ($form->load(Yii::$app->request->post())) {
            $fields = Yii::$app->request->post('fields', []);
            $confirmPerson = Yii::$app->request->post('FormConfirmPerson');

            $service = new SuperUserService();
            if ($service->createFormWithFields($form, $fields, $confirmPerson)) {
                return $this->redirect(['index']);
            }
        }

        return $this->render('super-user/admin', ['form' => $form]);
    }

    public function actionDeleteForm()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        return (new SuperUserService())->softDeleteForm(Yii::$app->request->post('id'));
    }

    public function actionRestoreForm()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        return (new SuperUserService())->restoreForm(Yii::$app->request->post('id'));
    }

    public function actionFetchFieldsByFormId($id)
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        return (new SuperUserService())->getFieldsByFormId($id);
    }

    public function actionCreateAutocomplete()
    {
        $request = Yii::$app->request;
        if (!$request->isPost) {
            throw new BadRequestHttpException('Неверный запрос.');
        }

        $entries = $request->post('FormFieldAutocompleteEntries', []);
        $result = (new SuperUserService())->createAutocompleteEntries($entries);

        if ($result['successCount']) {
            Yii::$app->session->setFlash('success', "Успешно добавлено {$result['successCount']} записей автозаполнения.");
        }
        if (!empty($result['errors'])) {
            Yii::$app->session->setFlash('error', "Ошибки: " . json_encode($result['errors']));
        }

        return $this->redirect(['index']);
    }

    public function actionCreateTypes()
    {
        $request = Yii::$app->request;
        if (!$request->isPost) {
            throw new BadRequestHttpException('Неверный запрос.');
        }

        $entries = $request->post('FormFieldTypes', []);
        $result = (new SuperUserService())->createTypes($entries);

        if ($result['successCount']) {
            Yii::$app->session->setFlash('success', "Добавлено типов: {$result['successCount']}");
        }
        if (!empty($result['errors'])) {
            Yii::$app->session->setFlash('error', "Ошибки: " . json_encode($result['errors']));
        }

        return $this->redirect(['index']);
    }
}