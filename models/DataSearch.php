<?php

namespace app\models;

use yii\data\ActiveDataProvider;

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
                'query' => Data::find()->where('0=1'),
                'pagination' => false,
            ]);
        }

        $query = Data::find()
            ->alias('d')
            ->joinWith('formField ff');

        if (empty($this->value) && empty($this->form_id)) {
            return new ActiveDataProvider([
                'query' => $query,
                'pagination' => false,
            ]);
        }

        if (!empty($this->form_id)) {
            $query->andWhere(['ff.form_id' => $this->form_id]);
        }

        if (!empty($this->value)) {
            $matchingRecordIndex = Data::find()
                ->select('record_index')
                ->where(['like', 'data', $this->value]);

            if (!empty($matchingRecordIndex)) {
                $query->andWhere(['d.record_index' => $matchingRecordIndex]);
            } else {
                $query->andWhere('0=1');
            }
        }

        return new ActiveDataProvider([
            'query' => $query,
            'pagination' => false,  // Отключаем пагинацию для вывода всех данных
        ]);
    }


}
