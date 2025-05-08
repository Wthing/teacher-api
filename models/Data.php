<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "data".
 *
 * @property int $id
 * @property int $profile_id
 * @property int $field_id
 * @property string $data
 *
 * @property Form $forms
 * @property Profile $profiles
 */
class Data extends \yii\db\ActiveRecord
{


    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'data';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['profile_id', 'field_id', 'data'], 'required'],
            [['profile_id', 'field_id'], 'integer'],
            [['data'], 'string'],
            [['field_id'], 'exist', 'skipOnError' => true, 'targetClass' => FormField::class, 'targetAttribute' => ['field_id' => 'id']],
            [['profile_id'], 'exist', 'skipOnError' => true, 'targetClass' => Profile::class, 'targetAttribute' => ['profile_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'profile_id' => Yii::t('app', 'Profile ID'),
            'field_id' => Yii::t('app', 'Field ID'),
            'data' => Yii::t('app', 'Data'),
        ];
    }

    /**
     * Gets query for [[Forms]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getForms()
    {
        return $this->hasOne(Form::class, ['id' => 'field_id']);
    }

    /**
     * Gets query for [[Profiles]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getProfiles()
    {
        return $this->hasOne(Profile::class, ['id' => 'profile_id']);
    }

    public function getFormField()
    {
        return $this->hasOne(FormField::class, ['id' => 'field_id']);
    }

    // Связь с моделью Profile
    public function getProfile()
    {
        return $this->hasOne(Profile::class, ['id' => 'profile_id']);
    }


}
