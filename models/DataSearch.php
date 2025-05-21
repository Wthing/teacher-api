<?php

namespace app\models;

use yii\base\Model;
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

    public function scenarios()
    {
        return Model::scenarios();
    }

    public function search($params)
    {
        $query = Data::find()->joinWith(['formField.form']);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        if (!empty($this->form_id)) {
            $query->andWhere(['form_fields.form_id' => $this->form_id]);
        }

        if (!empty($this->value)) {
            $query->andWhere(['like', 'data.data', $this->value]);
        }

        return $dataProvider;
    }
}
