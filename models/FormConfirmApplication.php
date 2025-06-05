<?php

namespace app\models;

use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "form_confirm_application".
 *
 * @property int $id
 * @property int $record_index
 * @property int $created_by
 * @property int $assigned_to
 * @property int|null $confirmed_at
 * @property int $status
 * @property int $created_at
 *
 * @property Profile $creator
 * @property Profile $assignee
 */
class FormConfirmApplication extends ActiveRecord
{
    const STATUS_PENDING = 0;
    const STATUS_CONFIRMED = 1;
    const STATUS_REJECTED = 2;

    public static function tableName()
    {
        return 'form_confirm_application';
    }

    public function rules()
    {
        return [
            [['record_index', 'created_by', 'assigned_to'], 'required'],
            [['record_index', 'created_by', 'assigned_to', 'confirmed_at', 'status', 'created_at'], 'integer'],
            [['status'], 'default', 'value' => self::STATUS_PENDING],
            [['created_by'], 'exist', 'skipOnError' => true, 'targetClass' => Profile::class, 'targetAttribute' => ['created_by' => 'id']],
            [['assigned_to'], 'exist', 'skipOnError' => true, 'targetClass' => Profile::class, 'targetAttribute' => ['assigned_to' => 'id']],
        ];
    }

    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::class,
                'createdAtAttribute' => 'created_at',
                'updatedAtAttribute' => false,
            ],
        ];
    }


    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'record_index' => 'Record Index',
            'created_by' => 'Created By',
            'assigned_to' => 'Assigned To',
            'confirmed_at' => 'Confirmed At',
            'status' => 'Status',
            'created_at' => 'Created At',
        ];
    }

    public function getCreator()
    {
        return $this->hasOne(Profile::class, ['id' => 'created_by']);
    }

    public function getAssignee()
    {
        return $this->hasOne(Profile::class, ['id' => 'assigned_to']);
    }

    public static function getStatusList()
    {
        return [
            self::STATUS_PENDING => 'Ожидает подтверждения',
            self::STATUS_CONFIRMED => 'Подтверждено',
            self::STATUS_REJECTED => 'Отклонено',
        ];
    }

    public function getStatusLabel()
    {
        return self::getStatusList()[$this->status] ?? 'Неизвестно';
    }

    public function confirm()
    {
        $this->status = self::STATUS_CONFIRMED;
        $this->confirmed_at = time();
        return $this->save(false);
    }

    public function reject()
    {
        $this->status = self::STATUS_REJECTED;
        $this->confirmed_at = time();
        return $this->save(false);
    }
}
