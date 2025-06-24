<?php

namespace app\controllers;

use app\models\ContactForm;
use app\models\Data;
use app\models\DataSearch;
use app\models\Form;
use app\models\FormConfirmApplication;
use app\models\FormConfirmPerson;
use app\models\FormField;
use app\models\LoginForm;
use app\models\Profile;
use app\models\User;
use app\services\FormService;
use diecoding\aws\s3\Service;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\BadRequestHttpException;
use yii\web\Controller;
use yii\web\Response;
use yii\web\UploadedFile;

/** @var Service $s3 */
class SiteController extends Controller
{
    private FormService $formService;

    public function __construct($id, $module, FormService $formService, $config = [])
    {
        $this->formService = $formService;
        parent::__construct($id, $module, $config);
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'only' => ['*'],
                'rules' => [
                    [
                        'actions' => ['index', 'about', 'login', 'contact'],
                        'allow' => true,
                    ],
                    [
                        'allow' => true,
                        'roles' => ['@'],
                    ],
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
        $s3 = Yii::$app->s3;
        $profileId = Yii::$app->user->id;

        $forms = Form::find()->where(['status' => true])->all();
        $formFields = FormField::find()->with(['type', 'autocompleteOptions'])->column();
        $accessGranted = FormConfirmPerson::find()->select('user_id')->where(['user_id' => $profileId])->column();

        $rawData = Data::find()
            ->where(['user_id' => $profileId])
            ->andWhere(['verification_status' => true])
            ->orderBy(['field_id' => SORT_ASC])
            ->all();

        $unreadRequestsCount = 0;

        if (in_array($profileId, $accessGranted)) {
            $unreadRequestsCount = FormConfirmApplication::find()
                ->where(['assigned_to' => $profileId])
                ->andWhere(['status' => FormConfirmApplication::STATUS_PENDING])
                ->count();
        }

        $groupedData = [];

        foreach ($rawData as $data) {
            $url = $data->data;

            if (is_string($url) && (str_starts_with($url, 'uploads/') || str_starts_with($url, 'uploads/previews/'))) {
                try {
                    $url = $s3->getPresignedUrl($url, '+30 minutes');
                } catch (\Throwable $e) {
                    Yii::error("Ошибка S3 URL: " . $e->getMessage(), 'form');
                    $url = null;
                }
            }

            $groupedData[$data->field_id][] = [
                'id' => $data->id,
                'data' => $url,
            ];
        }

        Yii::info($groupedData);

        return $this->render('profile', [
            'profileId' => $profileId,
            'forms' => $forms,
            'fields' => $formFields,
            'userData' => $groupedData,
            'unreadRequestsCount' => $unreadRequestsCount,
            'accessGranted' => $accessGranted,
        ]);
    }

    public function actionCreateFormData()
    {
        $request = Yii::$app->request;

        if (!$request->isPost) {
            throw new BadRequestHttpException('Only POST allowed');
        }

        $formId = $request->post('form_id');
        $fieldValues = $request->post('field_values', []);
        $fieldFiles = $_FILES['field_files'] ?? [];

        if (empty($formId) || (empty($fieldValues) && empty($fieldFiles['name'] ?? []))) {
            Yii::$app->session->setFlash('error', 'Форма или данные пустые');
            return $this->redirect(Yii::$app->request->referrer);
        }

        $service = new FormService();
        $success = $service->saveFormData($fieldValues, $fieldFiles, $formId, Yii::$app->user->id);

        if ($success) {
            Yii::$app->session->setFlash('success', 'Данные успешно сохранены');
        } else {
            Yii::$app->session->setFlash('error', 'Ошибка при сохранении данных');
        }

        return $this->redirect(Yii::$app->request->referrer);
    }



    public function actionUpdateFormData()
    {
        $request = Yii::$app->request;

        if (!$request->isPost) {
            throw new BadRequestHttpException('Only POST allowed');
        }

        $formId = $request->post('form_id');
        $fieldValues = $request->post('field_values', []);
        $recordIds = $request->post('record_ids', []);
        $fieldFiles = $_FILES['field_files'] ?? [];

        if (empty($formId) || (empty($fieldValues) && empty($fieldFiles['name'] ?? []))) {
            Yii::$app->session->setFlash('error', 'Форма или данные пустые');
            return $this->redirect(Yii::$app->request->referrer);
        }

        $service = new FormService();
        $success = $service->updateFormData($fieldValues, $fieldFiles, $recordIds, $formId, Yii::$app->user->id);

        if ($success) {
            Yii::$app->session->setFlash('success', 'Данные успешно обновлены');
        } else {
            Yii::$app->session->setFlash('error', 'Ошибка при обновлении данных');
        }

        return $this->redirect(Yii::$app->request->referrer);
    }

    public function actionDeleteFormData()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $dataIds = json_decode(Yii::$app->request->post('data_ids'), true);
        if (!is_array($dataIds)) {
            return ['success' => false, 'message' => 'Некорректный формат ID'];
        }

        foreach ($dataIds as $id) {
            $fieldData = Data::findOne($id);
            if (!$fieldData) {
                continue;
            }

            $fileKey = $fieldData->data;

            // Проверяем, что путь начинается с 'uploads/' (файлы на S3)
            if ($fileKey && is_string($fileKey) && strpos($fileKey, 'uploads/') === 0) {
                try {
                    // Удаляем основной файл с S3
                    Yii::$app->s3->delete($fileKey);
                } catch (\Throwable $e) {
                    Yii::error("Ошибка удаления основного файла с S3: $fileKey — " . $e->getMessage(), __METHOD__);
                }

                // Генерируем ключи для возможных превью-файлов
                $fileInfo = pathinfo($fileKey);
                $previewPrefix = 'uploads/previews/' . $fileInfo['filename'];

                $previewVariants = [
                    $previewPrefix . '.jpg',
                    $previewPrefix . '.png',
                    $previewPrefix . '.pdf',
                ];

                foreach ($previewVariants as $previewKey) {
                    try {
                        Yii::$app->s3->delete($previewKey);
                    } catch (\Throwable $e) {
                        Yii::warning("Ошибка удаления превью с S3: $previewKey — " . $e->getMessage(), __METHOD__);
                    }
                }
            }

            // Удаление записи из базы
            $fieldData->delete();
        }

        return ['success' => true];
    }

    public function actionFetchProfile($profileId)
    {
        $s3 = Yii::$app->s3;

        $forms = Form::find()->where(['status' => true])->all();
        $formFields = FormField::find()->with(['type', 'autocompleteOptions'])->column();
        $accessGranted = FormConfirmPerson::find()->select('user_id')->where(['user_id' => $profileId])->column();

        $rawData = Data::find()
            ->where(['user_id' => $profileId])
            ->andWhere(['verification_status' => true])
            ->orderBy(['field_id' => SORT_ASC])
            ->all();

        $unreadRequestsCount = 0;

        if (in_array($profileId, $accessGranted)) {
            $unreadRequestsCount = FormConfirmApplication::find()
                ->where(['created_by' => $profileId])
                ->andWhere(['status' => FormConfirmApplication::STATUS_PENDING])
                ->count();
        }


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
                'data' => $url,
            ];
        }

        return $this->render('watch-profile', [
            'profileId' => $profileId,
            'forms' => $forms,
            'fields' => $formFields,
            'userData' => $groupedData,
            'unreadRequestsCount' => $unreadRequestsCount,
            'accessGranted' => $accessGranted,
        ]);
    }
}