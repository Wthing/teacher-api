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

    const STATUS_UNVERIFIED = 0;
    const STATUS_VERIFIED = 1;
    const STATUS_REJECTED = 2;
    const STATUS_PENDING = 3;


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
            [['profile_id', 'field_id', 'record_index', 'verification_status'], 'integer'],
            [['data'], 'string'],
            [['field_id'], 'exist', 'skipOnError' => true, 'targetClass' => FormField::class, 'targetAttribute' => ['field_id' => 'id']],
            [['profile_id'], 'exist', 'skipOnError' => true, 'targetClass' => Profile::class, 'targetAttribute' => ['profile_id' => 'id']],
            [['verification_status'], 'default', 'value' => self::STATUS_UNVERIFIED],
            ['verification_status', 'in', 'range' => [self::STATUS_UNVERIFIED, self::STATUS_VERIFIED, self::STATUS_REJECTED, self::STATUS_PENDING]],
            ['verification_status', 'validateVerificationRequirement'],

        ];
    }

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

    public function beforeSave($insert)
    {
        if (parent::beforeSave($insert)) {

            // Автоматически сбрасываем verification_status на 0 при изменении данных
            if (!$this->isNewRecord && $this->isAttributeChanged('data')) {
                $this->verification_status = 0;
            }

            return true;
        }

        return false;
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


    public static function getVerificationStatusList()
    {
        return [
            self::STATUS_UNVERIFIED => 'Непроверено',
            self::STATUS_VERIFIED => 'Подтверждено',
            self::STATUS_REJECTED => 'Отклонено',
        ];
    }

    public function getVerificationStatusName()
    {
        return self::getVerificationStatusList()[$this->verification_status] ?? 'Неизвестно';
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



}
