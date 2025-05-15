<?php

namespace app\controllers;

use app\assets\AppAsset;
use app\models\Data;
use app\models\Form;
use app\models\FormField;
use app\models\Profile;
use Yii;
use yii\filters\AccessControl;
use yii\helpers\Json;
use yii\helpers\Url;
use yii\web\BadRequestHttpException;
use yii\web\Controller;
use yii\web\Response;
use yii\filters\VerbFilter;
use app\models\LoginForm;
use app\models\ContactForm;
use yii\web\UploadedFile;

class SiteController extends Controller
{

//    private AppAsset $asset;
//
//    public function __construct(AppAsset $asset)
//    {
//        $this->asset = $asset;
//    }

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
        $profile = Profile::findOne(1);
        $request = Yii::$app->request;

        if (!$request->isPost) {
            throw new BadRequestHttpException('Only POST allowed');
        }

        $formId = $request->post('form_id');
        $fieldValues = $request->post('field_values', []);

        if (empty($formId) || (empty($fieldValues) && empty($_FILES['field_files']['name']))) {
            Yii::$app->session->setFlash('error', 'Форма или данные пустые');
            return $this->redirect(Yii::$app->request->referrer);
        }

        $uploadDir = Yii::getAlias('@webroot/uploads/');
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $files = [];
        if (isset($_FILES['field_files'])) {
            foreach ($_FILES['field_files']['name'] as $fieldId => $name) {
                if ($_FILES['field_files']['error'][$fieldId] === UPLOAD_ERR_OK) {
                    $files[$fieldId] = new UploadedFile([
                        'name' => $_FILES['field_files']['name'][$fieldId],
                        'tempName' => $_FILES['field_files']['tmp_name'][$fieldId],
                        'type' => $_FILES['field_files']['type'][$fieldId],
                        'size' => $_FILES['field_files']['size'][$fieldId],
                        'error' => $_FILES['field_files']['error'][$fieldId],
                    ]);
                }
            }
        }

        $allFieldIds = array_unique(array_merge(array_keys($fieldValues), array_keys($files)));

        foreach ($allFieldIds as $fieldId) {
            $record = new Data();
            $record->field_id = $fieldId;
            $record->profile_id = $profile->id;

            $file = $files[$fieldId] ?? null;
            $value = $fieldValues[$fieldId] ?? null;

            if ($file && is_file($file->tempName)) {
                $safeName = preg_replace('/[^a-zA-Z0-9_]/', '_', $profile->firstname . '_' . $profile->surename);
                $fileName = uniqid() . '_' . $safeName . '.' . $file->getExtension();
                $uploadPath = $uploadDir . $fileName;

                if ($file->saveAs($uploadPath)) {
                    $record->data = 'uploads/' . $fileName;
                } else {
                    Yii::$app->session->setFlash('error', 'Ошибка сохранения файла');
                    return $this->redirect(Yii::$app->request->referrer);
                }
            } elseif ($value !== null) {
                $record->data = $value;
            } else {
                continue; // поле пустое и файл не загружен — пропускаем
            }

            if (!$record->save()) {
                Yii::error($record->getErrors(), 'form');
                Yii::$app->session->setFlash('error', 'Ошибка сохранения данных');
                return $this->redirect(Yii::$app->request->referrer);
            }
        }

        Yii::$app->session->setFlash('success', 'Данные успешно сохранены');
        return $this->redirect(Yii::$app->request->referrer);
    }

    public function actionUpdateFormData()
    {
        $profile = Profile::findOne(1);
        $request = Yii::$app->request;

        if (!$request->isPost) {
            throw new BadRequestHttpException('Only POST allowed');
        }

        $formId = $request->post('form_id');
        $fieldValues = $request->post('field_values', []);

        if (empty($formId) || (empty($fieldValues) && empty($_FILES['field_files']['name']))) {
            Yii::$app->session->setFlash('error', 'Форма или данные пустые');
            return $this->redirect(Yii::$app->request->referrer);
        }

        $uploadDir = Yii::getAlias('@webroot/uploads/');
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $files = [];
        if (isset($_FILES['field_files'])) {
            foreach ($_FILES['field_files']['name'] as $fieldId => $name) {
                if ($_FILES['field_files']['error'][$fieldId] === UPLOAD_ERR_OK) {
                    $files[$fieldId] = new UploadedFile([
                        'name' => $_FILES['field_files']['name'][$fieldId],
                        'tempName' => $_FILES['field_files']['tmp_name'][$fieldId],
                        'type' => $_FILES['field_files']['type'][$fieldId],
                        'size' => $_FILES['field_files']['size'][$fieldId],
                        'error' => $_FILES['field_files']['error'][$fieldId],
                    ]);
                }
            }
        }

        $allFieldIds = array_unique(array_merge(array_keys($fieldValues), array_keys($files)));

        foreach ($allFieldIds as $fieldId) {
            // Пытаемся найти существующую запись
            $record = Data::find()
                ->where(['field_id' => $fieldId, 'profile_id' => $profile->id])
                ->one();

            if (!$record) {
                // если нет — создаём новую
                $record = new Data();
                $record->field_id = $fieldId;
                $record->profile_id = $profile->id;
            }

            $file = $files[$fieldId] ?? null;
            $value = $fieldValues[$fieldId] ?? null;

            if ($file && is_file($file->tempName)) {
                // Удаляем старый файл, если он есть и отличается от нового
                if ($record->data && strpos($record->data, 'uploads/') === 0) {
                    $oldFilePath = Yii::getAlias('@webroot/') . $record->data;
                    if (is_file($oldFilePath)) {
                        @unlink($oldFilePath);
                    }
                }

                $safeName = preg_replace('/[^a-zA-Z0-9_]/', '_', $profile->firstname . '_' . $profile->surename);
                $fileName = uniqid() . '_' . $safeName . '.' . $file->getExtension();
                $uploadPath = $uploadDir . $fileName;

                if ($file->saveAs($uploadPath)) {
                    $record->data = 'uploads/' . $fileName;
                } else {
                    Yii::$app->session->setFlash('error', "Ошибка загрузки файла поля $fieldId");
                    return $this->redirect(Yii::$app->request->referrer);
                }
            } elseif ($value !== null) {
                // Если меняется значение, и был файл, удаляем файл
                if ($record->data && strpos($record->data, 'uploads/') === 0) {
                    $oldFilePath = Yii::getAlias('@webroot/') . $record->data;
                    if (is_file($oldFilePath)) {
                        @unlink($oldFilePath);
                    }
                }
                $record->data = $value;
            } else {
                // ни файл, ни значение — пропускаем
                continue;
            }

            if (!$record->save()) {
                Yii::error($record->getErrors(), 'form');
                Yii::$app->session->setFlash('error', "Ошибка при сохранении поля $fieldId");
                return $this->redirect(Yii::$app->request->referrer);
            }
        }

        Yii::$app->session->setFlash('success', 'Данные успешно обновлены');
        return $this->redirect(Yii::$app->request->referrer);
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
                // Если в поле data есть путь к файлу из папки uploads/
                if ($fieldData->data && strpos($fieldData->data, 'uploads/') === 0) {
                    $filePath = Yii::getAlias('@webroot/') . $fieldData->data;
                    if (is_file($filePath)) {
                        if (!@unlink($filePath)) {
                            Yii::error("Не удалось удалить файл $filePath", __METHOD__);
                        }
                    }
                }

                // Удаляем запись из базы
                $fieldData->delete();
            }
        }

        return ['success' => true];
    }


}
