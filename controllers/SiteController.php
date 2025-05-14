<?php

namespace app\controllers;

use app\models\Data;
use app\models\Form;
use app\models\FormField;
use Yii;
use yii\filters\AccessControl;
use yii\helpers\Url;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\VerbFilter;
use app\models\LoginForm;
use app\models\ContactForm;

class SiteController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'only' => ['logout'],
                'rules' => [
                    [
                        'actions' => ['logout'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::class,
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
            'captcha' => [
                'class' => 'yii\captcha\CaptchaAction',
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
            ],
        ];
    }

    /**
     * Displays homepage.
     *
     * @return string
     */
    public function actionIndex()
    {
        return $this->render('index');
    }

    /**
     * Login action.
     *
     * @return Response|string
     */
    public function actionLogin()
    {
        if (!Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        $model = new LoginForm();
        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            return $this->goBack();
        }

        $model->password = '';
        return $this->render('login', [
            'model' => $model,
        ]);
    }

    /**
     * Logout action.
     *
     * @return Response
     */
    public function actionLogout()
    {
        Yii::$app->user->logout();

        return $this->goHome();
    }

    /**
     * Displays contact page.
     *
     * @return Response|string
     */
    public function actionContact()
    {
        $model = new ContactForm();
        if ($model->load(Yii::$app->request->post()) && $model->contact(Yii::$app->params['adminEmail'])) {
            Yii::$app->session->setFlash('contactFormSubmitted');

            return $this->refresh();
        }
        return $this->render('contact', [
            'model' => $model,
        ]);
    }

    /**
     * Displays about page.
     *
     * @return string
     */
    public function actionAbout()
    {
        return $this->render('about');
    }

    public function actionProfile()
    {
        // Получаем текущий ID пользователя
        $profileId = Yii::$app->user->id;

        // Загружаем формы и поля
        $forms = Form::find()->all();
        $formFields = FormField::find()->with(['type', 'autocompleteOptions'])->all();

        // Изменяем запрос, чтобы получать данные для текущего пользователя
        $rawData = Data::find()
            ->where(['profile_id' => 1]) // Используем ID текущего пользователя
            ->orderBy(['field_id' => SORT_ASC])
            ->all();

        // Логируем ID полученных данных
        Yii::info('Raw Data IDs: ' . implode(',', array_map(fn($d) => $d->id, $rawData)), 'profile');

        // Группируем данные по field_id, добавляем ID и data
        $groupedData = [];
        foreach ($rawData as $data) {
            // Группируем данные по field_id
            $groupedData[$data->field_id][] = [
                'id' => $data->id,      // Сохраняем ID записи
                'data' => $data->data,  // Сохраняем данные
            ];
        }

        // Логируем сгруппированные данные
        Yii::info('Grouped Data: ' . json_encode($groupedData), 'profile');

        // Передаем данные в представление
        return $this->render('profile', [
            'forms' => $forms,
            'fields' => $formFields,
            'userData' => $groupedData,
        ]);
    }






    public function actionGetFormFields($form_id)
    {
        // Получаем поля формы по ID
        $formFields = FormField::find()->where(['form_id' => $form_id])->all();
        return $this->asJson($formFields);
    }



    public function actionCreateFormData()
    {
        $transaction = Yii::$app->db->beginTransaction();
        $fields = Yii::$app->request->post('field_values', []);

        $formId = Yii::$app->request->post('form_id');
        $profileId = 1;

        try {
            foreach ($fields as $fieldId => $value) {
                $data = new Data();
                $data->profile_id = $profileId;
                $data->field_id = $fieldId;
                $data->data = $value;

                if (!$data->save()) {
                    Yii::error([
                        'errors' => $data->errors,
                        'field_id' => $fieldId,
                        'value' => $value
                    ], __METHOD__);
                    throw new \Exception("Ошибка при создании записи для поля ID: $fieldId");
                }
            }

            $transaction->commit();
            Yii::$app->session->setFlash('success', 'Новая запись успешно добавлена.');
            return $this->redirect('profile');
        } catch (\Exception $e) {
            $transaction->rollBack();
            Yii::$app->session->setFlash('error', $e->getMessage());
            return $this->redirect('profile');
        }
    }

    public function actionUpdateFormData()
    {
        $formId = Yii::$app->request->post('form_id');
        $recordIds = json_decode(Yii::$app->request->post('data_ids', '[]'), true);
        $fieldValues = Yii::$app->request->post('field_values', []);

        if (!is_array($recordIds) || count($recordIds) !== count($fieldValues)) {
            Yii::$app->session->setFlash('error', 'Ошибка при передаче данных.');
            return $this->redirect(['view', 'id' => $formId]);
        }

        $i = 0;
        foreach ($fieldValues as $fieldId => $value) {
            $recordId = $recordIds[$i++] ?? null;
            if ($recordId) {
                $record = Data::findOne($recordId);
                if ($record) {
                    $record->data = $value;
                    $record->save();
                }
            }
        }

        Yii::$app->session->setFlash('success', 'Данные успешно обновлены.');
        return $this->redirect('profile');
    }

    public function actionViewFormData($form_id)
    {
        $userData = Data::find()
            ->joinWith('formField')
            ->where(['data.form_id' => $form_id])
            ->andWhere(['data.profile_id' => 1])
            ->all();

        return $this->render('view-form-data', [
            'userData' => $userData,
        ]);
    }

    public function actionDeleteFormData()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

        $dataIds = json_decode(Yii::$app->request->post('data_ids'), true);
        if (!is_array($dataIds)) {
            return ['success' => false, 'message' => 'Некорректный формат ID'];
        }

        foreach ($dataIds as $id) {
            $fieldData = Data::findOne($id);
            if ($fieldData) {
                $fieldData->delete();
            }
        }

        return ['success' => true];
    }

}
