<?php

namespace app\services;

use app\models\Form;
use app\models\FormConfirmApplication;
use app\models\FormConfirmPerson;
use app\models\FormField;
use app\models\Data;
use app\models\FormFieldAutocomplete;
use app\models\Profile;
use app\models\User;
use Yii;
use yii\web\UploadedFile;

class FormService
{
    public function saveFormData(array $fieldValues, array $fieldFiles, int $formId, int $userId): bool
    {
        $s3 = Yii::$app->s3;
        $profile = User::findOne($userId);
        $recInd = Data::find()->select(['max(record_index)'])->scalar() + 1;
        $form = Form::findOne($formId);

        $files = $this->normalizeFiles($fieldFiles);
        $allFieldIds = array_unique(array_merge(array_keys($fieldValues), array_keys($files)));

        foreach ($allFieldIds as $fieldId) {
            $record = new Data([
                'field_id' => $fieldId,
                'user_id' => $userId,
                'record_index' => $recInd,
            ]);

            $file = $files[$fieldId] ?? null;
            $value = $fieldValues[$fieldId] ?? null;

            if ($file && is_file($file->tempName)) {
                $safeName = preg_replace('/[^a-zA-Z0-9_]/', '_', $profile->username);
                $fileName = uniqid() . '_' . $safeName . '.' . $file->getExtension();
                $s3Path = 'uploads/' . $fileName;

                $s3->commands()->upload($s3Path, $file->tempName)->execute();
                $record->data = $s3Path;

//                $this->generatePreview($file, $fileName, $s3);
            } elseif ($value !== null) {
                $record->data = $value;
            } else {
                continue;
            }

            if (!$record->save()) {
                Yii::error($record->getErrors(), 'form');
                return false;
            }
        }

        if ($form->requiresFieldVerification($fieldId)) {
            $this->createVerificationRequest($recInd, $formId, $userId);
        } else {
            Data::updateAll(['verification_status' => 1], ['record_index' => $recInd]);
        }

        return true;
    }

    public function updateFormData(array $fieldValues, array $fieldFiles, array $recordIds, int $formId, int $userId): bool
    {
        $s3 = Yii::$app->s3;
        $profile = User::findOne($userId);
        $files = $this->normalizeFiles($fieldFiles);
        $allFieldIds = array_unique(array_merge(array_keys($fieldValues), array_keys($files)));

        foreach ($allFieldIds as $fieldId) {
            $recordId = $recordIds[$fieldId] ?? null;

            if ($recordId) {
                $record = Data::findOne(['id' => $recordId, 'user_id' => $profile->id]);
            } else {
                $record = Data::find()->where(['field_id' => $fieldId, 'user_id' => $profile->id])->one();
            }

            if (!$record) {
                $record = new Data([
                    'field_id' => $fieldId,
                    'user_id' => $userId,
                ]);
            }

            $file = $files[$fieldId] ?? null;
            $value = $fieldValues[$fieldId] ?? null;

            if ($file && is_file($file->tempName)) {
                $safeName = preg_replace('/[^a-zA-Z0-9_]/', '_', $profile->username);
                $fileName = uniqid() . '_' . $safeName . '.' . $file->getExtension();
                $s3Path = 'uploads/' . $fileName;

                try {
                    $s3->commands()->upload($s3Path, $file->tempName)->execute();
                    $record->data = $s3Path;

//                    $this->generatePreview($file, $fileName, $s3);
                } catch (\Exception $e) {
                    Yii::error('Ошибка загрузки в S3: ' . $e->getMessage(), 'form');
                    Yii::$app->session->setFlash('error', 'Ошибка загрузки файла в хранилище');
                    return false;
                }
            } elseif ($value !== null) {
                $record->data = $value;
            } else {
                continue;
            }

            if (!$record->save()) {
                Yii::error($record->getErrors(), 'form');
                Yii::$app->session->setFlash('error', "Ошибка при сохранении поля $fieldId");
                return false;
            }
        }

        return true;
    }


    private function normalizeFiles(array $rawFiles): array
    {
        $files = [];
        foreach ($rawFiles['name'] ?? [] as $fieldId => $name) {
            if ($rawFiles['error'][$fieldId] === UPLOAD_ERR_OK) {
                $files[$fieldId] = new UploadedFile([
                    'name' => $name,
                    'tempName' => $rawFiles['tmp_name'][$fieldId],
                    'type' => $rawFiles['type'][$fieldId],
                    'size' => $rawFiles['size'][$fieldId],
                    'error' => $rawFiles['error'][$fieldId],
                ]);
            }
        }
        return $files;
    }

    private function generatePreview(UploadedFile $file, string $fileName, $s3): void
    {
        $ext = strtolower($file->getExtension());
        $previewFile = tempnam(sys_get_temp_dir(), 'preview_') . '.jpg';

        if ($ext === 'docx') {
            $convertedPdf = tempnam(sys_get_temp_dir(), 'doc_') . '.pdf';
            exec('libreoffice --headless --convert-to pdf --outdir ' . escapeshellarg(dirname($convertedPdf)) . ' ' . escapeshellarg($file->tempName));

            if (file_exists($convertedPdf)) {
                $imagick = new \Imagick();
                $imagick->setResolution(150, 150);
                $imagick->readImage($convertedPdf . '[0]');
                $imagick->setImageFormat('jpeg');
                $imagick->writeImage($previewFile);
                $imagick->clear(); $imagick->destroy();
                @unlink($convertedPdf);
            }
        } elseif ($ext === 'pdf') {
            $imagick = new \Imagick();
            $imagick->setResolution(150, 150);
            $imagick->readImage($file->tempName . '[0]');
            $imagick->setImageFormat('jpeg');
            $imagick->writeImage($previewFile);
            $imagick->clear(); $imagick->destroy();
        }

        if (file_exists($previewFile)) {
            $previewPath = 'uploads/previews/' . pathinfo($fileName, PATHINFO_FILENAME) . '.jpg';
            $s3->commands()->upload($previewPath, $previewFile)->execute();
            @unlink($previewFile);
        }
    }

    private function createVerificationRequest(int $recInd, int $formId, int $userId): void
    {
        $verifier = FormConfirmPerson::find()->where(['form_id' => $formId])->one();
        $request = new FormConfirmApplication([
            'record_index' => $recInd,
            'created_by' => $userId,
        ]);

        if ($verifier) {
            $request->assigned_to = $verifier->user_id;
            $request->status = 0;

            if (!$request->save()) {
                Yii::error($request->getErrors(), 'form');
            }
        } else {
            Yii::$app->session->setFlash('warning', 'Нет назначенного верификатора для этой формы');
        }
    }

}