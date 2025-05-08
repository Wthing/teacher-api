<?php

namespace app\controllers;

use app\models\Data;
use app\models\Form;
use app\models\FormField;
use Yii;
use yii\filters\AccessControl;
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
        $profileId = Yii::$app->user->id;

        // Получаем все формы и поля
        $forms = Form::find()->all();
        $formFields = FormField::find()->with(['type', 'autocompleteOptions'])->all();

        // Получаем все данные пользователя по profile_id
        $rawData = Data::find()
            ->where(['profile_id' => 1])
            ->orderBy(['field_id' => SORT_ASC])
            ->all();

        // Проверим, что данные загружены
        Yii::info('Raw Data: ' . json_encode($rawData), 'profile');  // Логирование для проверки

        // Индексируем данные по field_id
        $groupedData = [];
        foreach ($rawData as $data) {
            $groupedData[$data->field_id][] = $data->data;
        }

        // Логируем группированные данные для отладки
        Yii::info('Grouped Data: ' . json_encode($groupedData), 'profile');

        return $this->render('profile', [
            'forms' => $forms,
            'fields' => $formFields,
            'userData' => $groupedData,
        ]);
    }





    public function actionGetFormFields($form_id)
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $fields = FormField::find()->where(['form_id' => $form_id])->all();

        return array_map(fn($f) => [
            'id' => $f->id,
            'field_name' => $f->field_name
        ], $fields);
    }



    public function actionSaveFormData()
    {
        $post = Yii::$app->request->post();
        $profileId = 1;
        $fields = $post['fields'];

        // Стартуем транзакцию
        $transaction = Yii::$app->db->beginTransaction();

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
                    throw new \Exception("Ошибка при сохранении данных для поля ID: $fieldId");
                }
            }

            $transaction->commit();
            return $this->asJson(['status' => 'success', 'message' => 'Данные успешно сохранены']);
        } catch (\Exception $e) {
            $transaction->rollBack();
            return $this->asJson(['status' => 'error', 'message' => $e->getMessage()]);
        }
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


}
