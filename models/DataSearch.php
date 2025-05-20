<?php

namespace app\models;

use yii\data\ActiveDataProvider;

class DataSearch extends Data
{
    public $field_name; // имя поля формы
    public $value;      // значение, которое ищем

    public function rules()
    {
        return [
            [['field_name', 'value'], 'safe'],
        ];
    }

    public function search($params)
    {
        $query = Data::find()->joinWith('formField');

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

        // Фильтруем по имени поля формы (form_fields.field_name)
        if (!empty($this->field_name)) {
            $query->andWhere(['form_fields.field_name' => $this->field_name]);
        }

        // Фильтруем по значению
        if (!empty($this->value)) {
            $query->andWhere(['like', 'data.data', $this->value]);
        }

        return $dataProvider;
    }
}
