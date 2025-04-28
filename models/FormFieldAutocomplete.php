<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "form_field_autocomplete".
 *
 * @property int $id
 * @property int $field_id
 * @property string $content
 *
 * @property FormField $formFields
 */
class FormFieldAutocomplete extends \yii\db\ActiveRecord
{


    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'form_field_autocomplete';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['field_id', 'content'], 'required'],
            [['field_id'], 'integer'],
            [['content'], 'string', 'max' => 255],
            [['field_id'], 'exist', 'skipOnError' => true, 'targetClass' => FormField::class, 'targetAttribute' => ['field_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'field_id' => Yii::t('app', 'Field ID'),
            'content' => Yii::t('app', 'Content'),
        ];
    }

    /**
     * Gets query for [[FormFields]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getFormFields()
    {
        return $this->hasOne(FormField::class, ['id' => 'field_id']);
    }

}
