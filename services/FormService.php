<?php

namespace app\services;

use app\models\Form;
use app\models\FormField;
use app\models\Data;
use app\models\FormFieldAutocomplete;
use app\models\Profile;
use Yii;
use yii\web\UploadedFile;

class FormService
{
    public function createForm($formName, array $fieldsName, $typeId)
    {
        $form = new Form();
        $form->form_name = $formName;
        $form->save();

        $formField = new FormField();
        foreach ($fieldsName as $field) {
            $formField->form_id = $form->id;
            $formField->type_id = $typeId;
            $formField->field_name = $field;
        }

        $formField->save();
    }

    public function createAutoComplete(array $contents, $fieldId)
    {
        $autoComplete = new FormFieldAutocomplete();

        foreach ($contents as $content) {
            $autoComplete->field_id = $fieldId;
            $autoComplete->content = $content;
        }

        $autoComplete->save();
    }

    public function processForm($formId, array $postData, $profileId, $file)
    {
        $form = Form::findOne(['id' => $formId]);
        $profile = Profile::findOne(['id' => $profileId]);

        $formFields = FormField::find()->where(['form_id' => $form->id])->all();

        $uploadPath = null;
        if ($file instanceof UploadedFile) {
            $fileName = uniqid((string)$profileId) . '_' . $profile->surname . '_' . $profile->firstname . '_' . $profile->patronymic . '.' . $file->extension;
            $uploadPath = 'uploads/' . $fileName;
            $file->saveAs(Yii::getAlias('@webroot/') . $uploadPath);
        }

        foreach ($formFields as $formField) {
            $fieldName = $formField->field_name;
            $inputValue = $postData[$fieldName] ?? null;

            $autoCompletes = FormFieldAutocomplete::find()
                ->where(['field_id' => $formField->id])
                ->all();

            $isAutoComplete = false;
            foreach ($autoCompletes as $autoComplete) {
                if ($autoComplete->content === $inputValue) {
                    $isAutoComplete = true;
                    break;
                }
            }

            $dataModel = new Data();
            $dataModel->profile_id = $profileId;
            $dataModel->form_id = $form->id;

            if ($isAutoComplete) {
                $dataModel->data = $inputValue;
            } else {
                $dataModel->data = $inputValue;
            }

            $dataModel->save();
        }

        if ($uploadPath !== null) {
            $fileData = new Data();
            $fileData->profile_id = $profileId;
            $fileData->form_id = $form->id;
            $fileData->data = $uploadPath;
            $fileData->save();
        }
    }

}