<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "forms".
 *
 * @property int $id
 * @property string $form_name
 * @property boolean $status
 * @property boolean $requires_verification
 *
 * @property Data[] $datas
 * @property FormField[] $formFields
 */
class Form extends ActiveRecord
{


    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'forms';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['form_name'], 'required'],
            [['form_name'], 'string', 'max' => 255],
            [['status', 'requires_verification'], 'boolean'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'form_name' => Yii::t('app', 'Form Name'),
            'status' => Yii::t('app', 'Status'),
            'requires_verification' => Yii::t('app', 'Requires Verification'),
        ];
    }

    public function getFormFields()
    {
        return $this->hasMany(FormField::class, ['form_id' => 'id']);
    }

    public function getData()
    {
        return $this->hasMany(Data::class, ['field_id' => 'id']);
    }

    public function requiresFieldVerification($fieldId)
    {
        $field = $this->getFormFields()->where(['id' => $fieldId])->one();
        return $this->requires_verification && $field !== null && $field->status === 1;
    }


}
