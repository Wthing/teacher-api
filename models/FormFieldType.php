<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "form_fields_type".
 *
 * @property int $id
 * @property string $type_name
 * @property int $status
 *
 * @property FormField[] $formFields
 */
class FormFieldType extends \yii\db\ActiveRecord
{


    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'form_fields_type';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['status'], 'default', 'value' => 0],
            [['type_name'], 'required'],
            [['status'], 'integer'],
            [['type_name'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'type_name' => Yii::t('app', 'Type Name'),
            'status' => Yii::t('app', 'Status'),
        ];
    }

    /**
     * Gets query for [[FormFields]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getFormFields()
    {
        return $this->hasMany(FormField::class, ['type_id' => 'id']);
    }

}
