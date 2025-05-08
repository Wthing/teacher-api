<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "profiles".
 *
 * @property int $id
 * @property string $surename
 * @property string $firstname
 * @property string $patronimyc
 *
 * @property Data[] $datas
 */
class Profile extends ActiveRecord
{


    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'profiles';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['surename', 'firstname', 'patronimyc'], 'required'],
            [['surename', 'firstname', 'patronimyc'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'surename' => Yii::t('app', 'Surename'),
            'firstname' => Yii::t('app', 'Firstname'),
            'patronimyc' => Yii::t('app', 'Patronimyc'),
        ];
    }


}
