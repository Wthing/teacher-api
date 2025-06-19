<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "form_confirm_person".
 *
 * @property int $id
 * @property int $user_id
 * @property int $form_id
 *
 * @property User $profile
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
            [['user_id', 'form_id'], 'required'],
            [['user_id', 'form_id'], 'integer'],
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['user_id' => 'id']],
            [['form_id'], 'exist', 'skipOnError' => true, 'targetClass' => Form::class, 'targetAttribute' => ['form_id' => 'id']],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'Подтверждающий пользователь',
            'form_id' => 'Форма',
        ];
    }

    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }

    public function getForm()
    {
        return $this->hasOne(Form::class, ['id' => 'form_id']);
    }
}
