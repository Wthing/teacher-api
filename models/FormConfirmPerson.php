<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "form_confirm_person".
 *
 * @property int $id
 * @property int $profile_id
 * @property int $form_id
 *
 * @property Profile $profile
 * @property Form $form
 */
class FormConfirmPerson extends ActiveRecord
{
    public static function tableName()
    {
        return 'form_confirm_person';
    }

    public function rules()
    {
        return [
            [['profile_id', 'form_id'], 'required'],
            [['profile_id', 'form_id'], 'integer'],
            [['profile_id'], 'exist', 'skipOnError' => true, 'targetClass' => Profile::class, 'targetAttribute' => ['profile_id' => 'id']],
            [['form_id'], 'exist', 'skipOnError' => true, 'targetClass' => Form::class, 'targetAttribute' => ['form_id' => 'id']],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'profile_id' => 'Подтверждающий пользователь',
            'form_id' => 'Форма',
        ];
    }

    public function getProfile()
    {
        return $this->hasOne(Profile::class, ['id' => 'profile_id']);
    }

    public function getForm()
    {
        return $this->hasOne(Form::class, ['id' => 'form_id']);
    }
}
