<?php

namespace app\models;

use Yii;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "data".
 *
 * @property int $id
 * @property int $profile_id
 * @property int $field_id
 * @property string $data
 * @property int $record_index
 * @property int $verification_status
 *
 * @property Form $forms
 * @property Profile $profiles
 */
class Data extends ActiveRecord
{

    const STATUS_PENDING = 0;
    const STATUS_CONFIRMED = 1;
    const STATUS_REJECTED = 2;


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
            [['record_index'], 'integer'],
            [['verification_status'], 'default', 'value' => self::STATUS_PENDING],
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
            'verification_status' => Yii::t('app', 'Verification Status'),
        ];
    }

    /**
     * Gets query for [[Forms]].
     *
     * @return ActiveQuery
     */
    public function getForms()
    {
        return $this->hasOne(Form::class, ['id' => 'field_id']);
    }

    /**
     * Gets query for [[Profiles]].
     *
     * @return ActiveQuery
     */
    public function getProfiles()
    {
        return $this->hasOne(Profile::class, ['id' => 'profile_id']);
    }

    public function getFormField()
    {
        return $this->hasOne(FormField::class, ['id' => 'field_id']);
    }

    public function getProfile()
    {
        return $this->hasOne(Profile::class, ['id' => 'profile_id']);
    }

    public function getFullName()
    {
        return $this->profile
            ? $this->profile->surename . ' ' . $this->profile->firstname . ' ' . $this->profile->patronimyc
            : null;
    }

    public function validateVerificationRequirement($attribute)
    {
        if (!$this->hasErrors()) {
            $form = $this->formField->form;
            if ($form && $form->requires_verification && $this->$attribute === null) {
                $this->addError($attribute, 'Поле требует верификации.');
            }
        }
    }




}
