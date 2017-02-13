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
            [['id', 'order_id', 'pay_id', 'gateway', 'currency', 'ip', 'note'], 'safe'],
            [['user_id', 'name', 'pay_type', 'pay_state', 'created_at', 'updated_at'], 'integer'],
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

        // add conditions that should always apply here

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
            'pay_type' => $this->pay_type,
            'pay_state' => $this->pay_state,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ]);

        $query->andFilterWhere(['like', 'id', $this->id])
            ->andFilterWhere(['like', 'order_id', $this->order_id])
            ->andFilterWhere(['like', 'pay_id', $this->pay_id])
            ->andFilterWhere(['like', 'gateway', $this->gateway])
            ->andFilterWhere(['like', 'currency', $this->currency])
            ->andFilterWhere(['like', 'ip', $this->ip])
            ->andFilterWhere(['like', 'note', $this->note]);

        return $dataProvider;
    }
}
