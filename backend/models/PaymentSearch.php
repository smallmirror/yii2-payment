<?php

namespace yuncms\payment\backend\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use yuncms\payment\models\Payment;

/**
 * PaymentSearch represents the model behind the search form about `yuncms\payment\models\Payment`.
 */
class PaymentSearch extends Payment
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'model_id', 'pay_id', 'gateway', 'currency', 'ip', 'note'], 'safe'],
            [['user_id', 'name', 'trade_type', 'trade_state'], 'integer'],
            [['money'], 'number'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function search($params)
    {
        $query = Payment::find();

        $query->orderBy(['id' => SORT_DESC]);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        // grid filtering conditions
        $query->andFilterWhere([
            'user_id' => $this->user_id,
            'name' => $this->name,
            'money' => $this->money,
            'trade_type' => $this->trade_type,
            'trade_state' => $this->trade_state,
        ]);

        $query->andFilterWhere(['like', 'id', $this->id])
            ->andFilterWhere(['like', 'model_id', $this->model_id])
            ->andFilterWhere(['like', 'pay_id', $this->pay_id])
            ->andFilterWhere(['like', 'gateway', $this->gateway])
            ->andFilterWhere(['like', 'currency', $this->currency])
            ->andFilterWhere(['like', 'ip', $this->ip]);

        return $dataProvider;
    }
}
