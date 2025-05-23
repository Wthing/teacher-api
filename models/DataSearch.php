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

    public function search1($params)
    {
        $recIndex = Data::find()
            ->joinWith('formField')
            ->select(['record_index'])
            ->where(['data' => $this->value])
            ->andWhere(['form_fields.form_id' => $this->form_id])
            ->column();

        Yii::info($recIndex);

        $query = Data::find()->select('*')->from('data')->groupBy($recIndex);

        $this->load($params);

        if (!$this->validate()) {
            $query->where('0=1');
            return $query;
        }

        if ($this->form_id) {
            $query->joinWith(['formField'])
                ->andWhere(['form_fields.form_id' => $this->form_id]);
        }

        if ($this->value) {
            $query->andWhere(['like', 'data.data', $this->value]);
        }

        return $query;
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
            ->joinWith('formField ff')
            ->where(['ff.form_id' => $this->form_id]);

        if ($this->value) {
            $matchingRecordIndex = Data::find()
                ->select('record_index')
                ->where(['like', 'data', $this->value])
                ->column();

            if ($matchingRecordIndex !== null) {
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
