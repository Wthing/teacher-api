<?php

namespace app\models;

use Yii;
use yii\data\ActiveDataProvider;
use yii\db\Query;

class DataSearch extends Data
{
    public $field_name;
    public $value;
    public $form_id;

    public function rules()
    {
        return [
            [['field_name', 'value'], 'safe'],
            [['form_id'], 'integer'],
        ];
    }
    public function search($params)
    {
        $this->load($params);

        if (!$this->validate()) {
            return new ActiveDataProvider([
                'query' => Data::find()->where('0=1')
            ]);
        }

        $query = Data::find()
            ->alias('d')
            ->joinWith('formField ff');

        if (!empty($this->form_id)) {
            $query->andWhere(['ff.form_id' => $this->form_id]);
        }

        if (!empty($this->value)) {
            $matchingRecordIndex = Data::find()
                ->select('record_index')
                ->where(['like', 'data', $this->value])
                ->column();

            if (!empty($matchingRecordIndex)) {
                $query->andWhere(['d.record_index' => $matchingRecordIndex]);
            } else {
                $query->andWhere('0=1');
            }
        }

        return new ActiveDataProvider([
            'query' => $query
        ]);
    }

}
