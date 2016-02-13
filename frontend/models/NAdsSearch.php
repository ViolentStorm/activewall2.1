<?php
/**
 * Created by PhpStorm.
 * User: Violent
 * Date: 30.06.15
 * Time: 13:09
 */

namespace frontend\models;


use common\models\activerecords\Client;
use common\models\activerecords\Message;
use common\models\activerecords\Payment;
use common\models\activerecords\Phone;
use common\models\activerecords\Region;
use yii\base\Model;
use yii\data\ActiveDataProvider;

class NAdsSearch extends Model
{

    const TYPE_PHONE = 'phone';
    const TYPE_NUM = 'number';
    const TYPE_CLIENT = 'client';

    const TIME_ALL = 'all';
    const TIME_CURRENT = 'current';
    const TIME_FUTURE = 'future';
    const TIME_PREVIOUS = 'previous';
    const TIME_REMOVED = 'removed';


    public $timeMark = self::TIME_ALL;
    public $subject = '';
    public $type = self::TYPE_PHONE;

    public function timeMarkLabels()
    {
        return [
            self::TIME_ALL => 'Все',
            self::TIME_CURRENT => 'Текущие',
            self::TIME_FUTURE => 'Будущие',
            self::TIME_PREVIOUS => 'Прошлые',
            self::TIME_REMOVED => 'Корзина',
        ];
    }

    public function attributeLabels()
    {
        return [
            'timeMark' => 'Временная отметка',
            'type' => 'Тип',
            'subject' => 'Поиск',
        ];
    }

    public function rules()
    {
        return [
            ['timeMark', 'in', 'range' => array_keys($this->timeMarkLabels())],
            ['timeMark', 'default', 'value' => self::TIME_ALL],
            ['subject', 'default', 'value' => ''],
            ['type', 'default', 'value' => self::TYPE_PHONE],
            [['timeMark', 'type', 'subject'], 'string'],
        ];
    }

    public function search($params)
    {

        $query = Message::find()
            ->innerJoinWith(['region' => function($q){/* @var \yii\db\ActiveQuery $q */
                $q->onCondition([Region::tableName() . '.id' => \Yii::$app->user->identity->currentRegion]);
            }], false)
            ->innerJoinWith(['payments', 'client', 'category'])
            ->orderBy([Message::tableName() . '.create_dt' => SORT_DESC])->distinct();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => ['pageSize' => 50, 'pageSizeParam' => 'inpage']
        ]);

        if (!($this->load($params) && $this->validate()))
            return $dataProvider;

        switch ($this->timeMark) {
            case self::TIME_ALL:
                $query->active();
                break;
            case self::TIME_CURRENT:
                $query->active()->andWhere([Payment::tableName() . '.num' => Payment::currentNumber()]);
                break;
            case self::TIME_FUTURE:
                $query->active()->andWhere(['>', Payment::tableName() . '.num', Payment::currentNumber()]);
                break;
            case self::TIME_PREVIOUS:
                $query->active()->andWhere(['<', Payment::tableName() . '.num', Payment::currentNumber()]);
                break;
            case self::TIME_REMOVED:
                $query->deleted();
                break;
        }

        if (!empty($this->subject)) {
            switch ($this->type) {
                case self::TYPE_PHONE:
                    $query->andWhere(['LIKE', Phone::tableName() . '.number', $this->subject])
                        ->orWhere(['LIKE', Message::tableName() . '.phone', $this->subject]);
                    break;
                case self::TYPE_CLIENT:
                    $query->andWhere(['LIKE', Client::tableName() . '.name', $this->subject]);
                    break;
                case self::TYPE_NUM:
                    $query->andWhere([Payment::tableName() . '.num' => $this->subject]);
                    break;
            }
        }


        return $dataProvider;

    }
}