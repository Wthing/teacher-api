<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "form_fields".
 *
 * @property int $id
 * @property int $form_id
 * @property int $type_id
 * @property string $field_name
 * @property int $status
 *
 * @property FormFieldAutocomplete[] $formFieldAutocompletes
 * @property FormFieldType $formFieldsType
 * @property Form $forms
 */
class FormField extends ActiveRecord
{


    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'form_fields';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['status'], 'default', 'value' => 0],
            [['form_id', 'type_id', 'field_name'], 'required'],
            [['form_id', 'type_id', 'status'], 'integer'],
            [['field_name'], 'string', 'max' => 255],
            [['form_id'], 'exist', 'skipOnError' => true, 'targetClass' => Form::class, 'targetAttribute' => ['form_id' => 'id']],
            [['type_id'], 'exist', 'skipOnError' => true, 'targetClass' => FormFieldType::class, 'targetAttribute' => ['type_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'form_id' => Yii::t('app', 'Form ID'),
            'type_id' => Yii::t('app', 'Type ID'),
            'field_name' => Yii::t('app', 'Field Name'),
            'status' => Yii::t('app', 'Status'),
        ];
    }

    /**
     * Gets query for [[FormFieldAutocompletes]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getFormFieldAutocompletes()
    {
        return $this->hasMany(FormFieldAutocomplete::class, ['field_id' => 'id']);
    }

    /**
     * Gets query for [[FormFieldsType]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getFormFieldsType()
    {
        return $this->hasOne(FormFieldType::class, ['id' => 'type_id']);
    }

    /**
     * Gets query for [[Forms]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getForms()
    {
        return $this->hasOne(Form::class, ['id' => 'form_id']);
    }
    public function getForm()
    {
        return $this->hasOne(Form::class, ['id' => 'form_id']);
    }

    public function getData()
    {
        return $this->hasOne(Data::class, ['field_id' => 'id']);
    }


    public function getType()
    {
        return $this->hasOne(FormFieldType::class, ['id' => 'type_id']);
    }


    public function getAutocompleteOptions()
    {
        return $this->hasMany(FormFieldAutocomplete::class, ['field_id' => 'id']);
    }

}
