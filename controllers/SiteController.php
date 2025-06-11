<?php

namespace app\controllers;

use app\models\ContactForm;
use app\models\Data;
use app\models\Form;
use app\models\FormConfirmApplication;
use app\models\FormConfirmPerson;
use app\models\FormField;
use app\models\LoginForm;
use app\models\Profile;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\BadRequestHttpException;
use yii\web\Controller;
use yii\web\Response;
use yii\web\UploadedFile;

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

        $forms = Form::find()->where(['status' => true])->all();
        $formFields = FormField::find()->with(['type', 'autocompleteOptions'])->column();
        $accessGranted = FormConfirmPerson::find()->select('profile_id')->where(['profile_id' => $profileId])->column();

        $rawData = Data::find()
            ->where(['profile_id' => $profileId])
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
            $groupedData[$data->field_id][] = [
                'id' => $data->id,
                'data' => $data->data,
            ];
        }

        return $this->render('profile', [
            'profileId' => $profileId,
            'forms' => $forms,
            'fields' => $formFields,
            'userData' => $groupedData,
            'unreadRequestsCount' => $unreadRequestsCount,
            'accessGranted' => $accessGranted,
        ]);
    }

    public function actionGetFormFields($form_id)
    {
        $formFields = FormField::find()->where(['form_id' => $form_id])->all();
        return $this->asJson($formFields);
    }

    public function actionCreateFormData()
    {
        $currentUserId = Yii::$app->user->id;
        $profile = Profile::findOne($currentUserId);
        $request = Yii::$app->request;
        $recInd = Data::find()->select(['max(record_index)'])->scalar() + 1;

        if (!$request->isPost) {
            throw new BadRequestHttpException('Only POST allowed');
        }

        $formId = $request->post('form_id');
        $fieldValues = $request->post('field_values', []);

        $form = Form::findOne($formId);

        if (empty($formId) || (empty($fieldValues) && empty($_FILES['field_files']['name']))) {
            Yii::$app->session->setFlash('error', 'Форма или данные пустые');
            return $this->redirect(Yii::$app->request->referrer);
        }

        $uploadDir = Yii::getAlias('@webroot/uploads/');
        $previewDir = Yii::getAlias('@webroot/uploads/previews/');
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        if (!is_dir($previewDir)) mkdir($previewDir, 0777, true);

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
            $record->record_index = $recInd;

            $file = $files[$fieldId] ?? null;
            $value = $fieldValues[$fieldId] ?? null;

            if ($file && is_file($file->tempName)) {
                $safeName = preg_replace('/[^a-zA-Z0-9_]/', '_', $profile->login);
                $fileName = uniqid() . '_' . $safeName . '.' . $file->getExtension();
                $uploadPath = $uploadDir . $fileName;

                if ($file->saveAs($uploadPath)) {
                    $record->data = 'uploads/' . $fileName;

                    // Генерация превью, если PDF или DOCX
                    $ext = strtolower($file->getExtension());
                    if ($ext === 'pdf' || $ext === 'docx') {
                        $previewPath = $previewDir . pathinfo($fileName, PATHINFO_FILENAME) . '.jpg';

                        try {
                            if ($ext === 'docx') {
                                // Преобразовать DOCX → PDF (требует libreoffice)
                                $convertedPdf = $uploadDir . pathinfo($fileName, PATHINFO_FILENAME) . '.pdf';
                                $command = 'libreoffice --headless --convert-to pdf --outdir ' . escapeshellarg($uploadDir) . ' ' . escapeshellarg($uploadPath);
                                exec($command);

                                if (file_exists($convertedPdf)) {
                                    $imagick = new \Imagick();
                                    $imagick->setResolution(150, 150);
                                    $imagick->readImage($convertedPdf . '[0]');
                                    $imagick->setImageFormat('jpeg');
                                    $imagick->setImageCompressionQuality(90);
                                    $imagick->writeImage($previewPath);
                                    $imagick->clear();
                                    $imagick->destroy();
                                    @unlink($convertedPdf);
                                }
                            } else {
                                $imagick = new \Imagick();
                                $imagick->setResolution(150, 150);
                                $imagick->readImage($uploadPath . '[0]');
                                $imagick->setImageFormat('jpeg');
                                $imagick->setImageCompressionQuality(90);
                                $imagick->writeImage($previewPath);
                                $imagick->clear();
                                $imagick->destroy();
                            }
                        } catch (\Exception $e) {
                            Yii::error('Ошибка создания превью: ' . $e->getMessage(), 'form');
                        }
                    }
                } else {
                    Yii::$app->session->setFlash('error', 'Ошибка сохранения файла');
                    return $this->redirect(Yii::$app->request->referrer);
                }
            } elseif ($value !== null) {
                $record->data = $value;
            } else {
                continue;
            }

            if (!$record->save()) {
                Yii::error($record->getErrors(), 'form');
                Yii::$app->session->setFlash('error', 'Ошибка сохранения данных');
                return $this->redirect(Yii::$app->request->referrer);
            }
        }

        if ($form->requiresFieldVerification($fieldId)) {
            $request = new FormConfirmApplication();
            $request->record_index = $recInd;
            $request->created_by = $currentUserId;

            $verifier = FormConfirmPerson::find()->where(['form_id' => $formId])->one();

            if ($verifier) {
                $request->assigned_to = $verifier->profile_id;
                $request->status = 0;

                if ($request->save()) {
                    Yii::$app->session->setFlash('success', 'Данные отправлены на проверку');
                } else {
                    Yii::error($request->getErrors(), 'form');
                    Yii::$app->session->setFlash('error', 'Ошибка создания запроса на подтверждение');
                }
            } else {
                Yii::$app->session->setFlash('warning', 'Нет назначенного верификатора для этой формы');
            }
        } else {
            $recIndNew = Data::find()->select(['max(record_index)']);
            $recordsCheck = Data::find()->where(['record_index' => $recIndNew])->all();
            foreach ($recordsCheck as $record) {
                $record->verification_status = 1;
                $record->save();
            }

            Yii::$app->session->setFlash('success', 'Данные успешно сохранены');
        }

        return $this->redirect(Yii::$app->request->referrer);
    }


    public function actionUpdateFormData()
    {
        $currentUserId = Yii::$app->user->id;
        $profile = Profile::findOne($currentUserId);
        $request = Yii::$app->request;

        if (!$request->isPost) {
            throw new BadRequestHttpException('Only POST allowed');
        }

        $formId = $request->post('form_id');
        $fieldValues = $request->post('field_values', []);
        $recordIds = $request->post('record_ids', []);

        if (empty($formId) || (empty($fieldValues) && empty($_FILES['field_files']['name']))) {
            Yii::$app->session->setFlash('error', 'Форма или данные пустые');
            return $this->redirect(Yii::$app->request->referrer);
        }

        $uploadDir = Yii::getAlias('@webroot/uploads/');
        $previewDir = Yii::getAlias('@webroot/uploads/previews/');
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
        if (!is_dir($previewDir)) mkdir($previewDir, 0777, true);

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
            $recordId = $recordIds[$fieldId] ?? null;

            if ($recordId) {
                $record = Data::findOne(['id' => $recordId, 'profile_id' => $profile->id]);
            } else {
                $record = Data::find()
                    ->where(['field_id' => $fieldId, 'profile_id' => $profile->id])
                    ->one();
            }

            if (!$record) {
                $record = new Data();
                $record->field_id = $fieldId;
                $record->profile_id = $profile->id;
            }

            $file = $files[$fieldId] ?? null;
            $value = $fieldValues[$fieldId] ?? null;

            if ($file && is_file($file->tempName)) {
                if ($record->data && strpos($record->data, 'uploads/') === 0) {
                    $oldFilePath = Yii::getAlias('@webroot/') . $record->data;
                    if (is_file($oldFilePath)) {
                        @unlink($oldFilePath);
                    }
                }

                $safeName = preg_replace('/[^a-zA-Z0-9_]/', '_', $profile->login);
                $fileName = uniqid() . '_' . $safeName . '.' . $file->getExtension();
                $uploadPath = $uploadDir . $fileName;

                if ($file->saveAs($uploadPath)) {
                    $record->data = 'uploads/' . $fileName;

                    // Генерация превью, если PDF или DOCX
                    $ext = strtolower($file->getExtension());
                    if ($ext === 'pdf' || $ext === 'docx') {
                        $previewPath = $previewDir . pathinfo($fileName, PATHINFO_FILENAME) . '.jpg';

                        try {
                            if ($ext === 'docx') {
                                // Преобразовать DOCX → PDF (требует libreoffice)
                                $convertedPdf = $uploadDir . pathinfo($fileName, PATHINFO_FILENAME) . '.pdf';
                                $command = 'libreoffice --headless --convert-to pdf --outdir ' . escapeshellarg($uploadDir) . ' ' . escapeshellarg($uploadPath);
                                exec($command);

                                if (file_exists($convertedPdf)) {
                                    $imagick = new \Imagick();
                                    $imagick->setResolution(150, 150);
                                    $imagick->readImage($convertedPdf . '[0]');
                                    $imagick->setImageFormat('jpeg');
                                    $imagick->setImageCompressionQuality(90);
                                    $imagick->writeImage($previewPath);
                                    $imagick->clear();
                                    $imagick->destroy();
                                    @unlink($convertedPdf);
                                }
                            } else {
                                $imagick = new \Imagick();
                                $imagick->setResolution(150, 150);
                                $imagick->readImage($uploadPath . '[0]');
                                $imagick->setImageFormat('jpeg');
                                $imagick->setImageCompressionQuality(90);
                                $imagick->writeImage($previewPath);
                                $imagick->clear();
                                $imagick->destroy();
                            }
                        } catch (\Exception $e) {
                            Yii::error('Ошибка создания превью: ' . $e->getMessage(), 'form');
                        }
                    }

                } else {
                    Yii::$app->session->setFlash('error', "Ошибка загрузки файла поля $fieldId");
                    return $this->redirect(Yii::$app->request->referrer);
                }
            } elseif ($value !== null) {
                if ($record->data && strpos($record->data, 'uploads/') === 0) {
                    $oldFilePath = Yii::getAlias('@webroot/') . $record->data;
                    if (is_file($oldFilePath)) {
                        @unlink($oldFilePath);
                    }
                }
                $record->data = $value;
            } else {
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
        Yii::$app->response->format = Response::FORMAT_JSON;

        $dataIds = json_decode(Yii::$app->request->post('data_ids'), true);
        if (!is_array($dataIds)) {
            return ['success' => false, 'message' => 'Некорректный формат ID'];
        }

        foreach ($dataIds as $id) {
            $fieldData = Data::findOne($id);
            if ($fieldData) {
                // Если поле содержит путь к файлу
                if ($fieldData->data && strpos($fieldData->data, 'uploads/') === 0) {
                    $filePath = Yii::getAlias('@webroot/') . $fieldData->data;

                    // Удаляем основной файл
                    if (is_file($filePath)) {
                        if (!@unlink($filePath)) {
                            Yii::error("Не удалось удалить файл $filePath", __METHOD__);
                        }
                    }

                    // Проверяем возможный превью-файл: .pdf или .jpg, .png
                    $fileInfo = pathinfo($filePath);
                    $previewPdf = $fileInfo['dirname'] . '/previews/' . $fileInfo['filename'] . '.pdf';
                    $previewPng = $fileInfo['dirname'] . '/previews/' . $fileInfo['filename'] . '.png';
                    $previewJpg = $fileInfo['dirname'] . '/previews/' . $fileInfo['filename'] . '.jpg';

                    foreach ([$previewPdf, $previewPng, $previewJpg] as $previewFile) {
                        if (is_file($previewFile)) {
                            if (!@unlink($previewFile)) {
                                Yii::error("Не удалось удалить превью $previewFile", __METHOD__);
                            }
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
