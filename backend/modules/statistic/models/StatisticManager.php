<?php
/**
 * Created by PhpStorm.
 * User: Violent
 * Date: 16.03.15
 * Time: 14:45
 */

namespace backend\modules\statistic\models;

use common\models\activerecords\Category;
use common\models\activerecords\City;
use common\models\activerecords\Message;
use common\models\activerecords\Payment;
use common\models\activerecords\Price;
use common\models\activerecords\Region;
use PHPExcel_Style_Alignment;
use Yii;
use yii\base\Model;
use yii\helpers\ArrayHelper;

/***
 * Class StatisticManager
 * @package backend\modules\statistic\models
 * @property StatisticSet $data
 * @property $endCategories CategorySet[]
 */
class StatisticManager extends Model
{

    const SUBMITTER_ALL = 'all';
    const SUBMITTER_MANAGER = 'adsManager';
    const SUBMITTER_CC = 'callCenter';
    const SUBMITTER_STREET = 'streetManger';

    protected $_filename;

    public $submitter;
    public $region;
    public $number;
    //public $periodBegin;
    //public $periodEnd;

    private $_catsForEnd;

    public function attributeLabels()
    {
        return [
            'region' => 'Регион',
            'periodBegin' => 'От',
            'periodEnd' => 'До',
        ];
    }

    public function rules()
    {
        return [
            [['number', 'region', 'submitter'], 'required'],
            [['region'], 'integer'],
            ['submitter', 'default', 'value' => self::SUBMITTER_ALL],
            ['number', 'default', 'value' => Payment::currentNumber()]
        ];
    }

    /***
     * @inheritdoc Использовать аккуратно, каждый вызов собирает структуру по новой
     * @param $cats array
     * @param int $owner
     * @return array|CategorySet[]
     */
    public function getEndCategories($owner = 0, $cats = null)
    {

        if ($cats)
            $this->_catsForEnd = $cats;
        elseif (!$this->_catsForEnd)
            $this->_catsForEnd = Category::find()->active()->select(['id', 'parent_id', 'name'])->indexBy('id')->asArray()->all();

        $result = [];

        // ! Не дублировние кода, а оптимизация по скорости !
        if ($owner == 0) {
            foreach ($this->_catsForEnd as $id => $data) {
                if ($data['parent_id'] != $owner)
                    continue;
                $result[$data['id']] = new CategorySet([
                    'id' => $data['id'],
                    'source_id' => $id,
                    'name' => $data['name'],
                    'categoryIds' => $this->getEndCategories($id)
                ]);
            }
        } else {
            foreach ($this->_catsForEnd as $id => $data) {
                if ($data['parent_id'] != $owner)
                    continue;
                $result = array_merge($result, $this->getEndCategories($id));
            }
        }

        return count($result) ? $result : [$this->_catsForEnd[$owner]['id']];

    }

    /***
     * @param bool $validate
     * @return bool|\frontend\models\activerecords\aw\Message[]
     */
    public function getTargetMessages($validate = true)
    {

        if ($validate && !$this->validate())
            return false;

        $editBy = $this->submitter == self::SUBMITTER_ALL ? false : ArrayHelper::getColumn(Yii::$app->db->createCommand('
                    select user_id
                    from auth_assignment aa
                    where aa.item_name like "' . $this->submitter .'"')->queryAll(), 'user_id');

        $q = Message::find()
            ->innerJoinWith([
                'payments' => function($query){
                    /* @var $query \yii\db\ActiveQuery */
                    $query->onCondition([Payment::tableName() . '.num' => $this->number]);
                },
            ])
            ->innerJoinWith(['city' => function($query){
                /* @var $query \yii\db\ActiveQuery */
                $query->onCondition([City::tableName() . '.region_id' => $this->region]);
            }], false)
            ->where(['<>', Message::tableName() . '.status', Message::STATUS_DELETE]);
        if ($editBy)
            $q->andWhere(['edit_by' => $editBy]);

        return $q->all();
    }

    public function getData($validate = true)
    {
        if ($validate && !$this->validate())
            return false;

        $statistic = new StatisticSet(['categorySets' => $this->endCategories]);
        $msgs = $this->getTargetMessages(false);
        $old = ArrayHelper::getColumn(
            Payment::find()->where(['num' => $this->number - 1, 'message_id' => ArrayHelper::getColumn($msgs, 'id')])
                ->select(['id', 'message_id'])->asArray()->all(),
            'message_id'
        );

        $set = null;
        $isNew = false;
        $pm = null;

        foreach ($msgs as $data) {

            $set = $statistic->getCategorySet($data->category_id);
            if (!$set)
                continue;

            $paid = null;

            $isNew = !in_array($data->id, $old);
            $pm = $data->payments[0];

            $paid = $pm->amount != 0;
            //$set->incrementStat('paymentsCount');
            $set->incrementStat('Принято');
            switch ($pm->status) {
                case  Payment::STATUS_PENDING:
                    if ($paid)
                        $set->incrementStat('paymentsPaidWait');
                    break;
                case Payment::STATUS_FAIL:
                    if($paid)
                        $set->incrementStat('paymentsPaidFail');
                    break;
                case Payment::STATUS_SUCCESS:
                    //$set->incrementStat($paid ? 'paymentsPaidAccept' : 'paymentsFreeAccept');
                    $set->incrementStat('Оплачено');
                    break;
            }
            switch ($pm->price_type) {
                case Price::TYPE_FRAME:
                    //$set->incrementStat('priceInFrame');
                    $set->incrementStat('в рамке');
                    break;
                case Price::TYPE_BOLD:
                    //$set->incrementStat('priceHighlighted');
                    $set->incrementStat('жирный шрифт');
                    break;
                case Price::TYPE_BOLDED_FRAME:
                    //$set->incrementStat('priceHighlightedInFrame');
                    $set->incrementStat('выделенное в рамке');
                    break;
                case Price::TYPE_COLORED_BACKGROUND:
                    //$set->incrementStat('priceColoredBackground');
                    $set->incrementStat('на цветном фоне');
                    break;
                case Price::TYPE_DIAMOND:
                    //$set->incrementStat('priceMarkedDiamond');
                    $set->incrementStat('выделенное маркером');
                    break;
                case Price::TYPE_SHAPED_FRAME:
                    //$set->incrementStat('priceShapedFrame');
                    $set->incrementStat('фигурная рамка');
                    break;
                case Price::TYPE_STANDART:
                    //$set->incrementStat('priceStandart');
                    $set->incrementStat('обычное');
                    break;
            }

            if ($pm->site == 1)
                $set->incrementStat('размещение на сайт');

            $set->incrementStat('Топ ' . $pm->position);
            $set->incrementStat($paid ? 'paymentsPaidCount' : 'paymentsFreeCount');

            $set->incrementStat($isNew ? 'messageNew' : 'messageProlong');

        }


        return $statistic;

    }


    public function getFilename()
    {
        if (!$this->_filename) {
            $this->_filename = 'statistic_'
                . Region::find()->where(['id' => $this->region])->select(['id', 'name'])->one()->name
                . '_номер_' . $this->number. '_' .  $this->submitter . '.xls';
        }
        return $this->_filename;
    }

    public function getFilePath()
    {
        return \Yii::getAlias('@backend') . '/uploads/statistic/' . $this->filename;
    }

    public function toExcel()
    {

        $xls = new \PHPExcel();
        $xls->setActiveSheetIndex(0);
        $sheet = $xls->getActiveSheet();
        $sheet->setTitle('Статистика');
        $sheet->getRowDimension(1)->setRowHeight(-1);
        $sheet->getColumnDimension('A')->setAutoSize(true);
        $s = $sheet->getStyle('1');
        $s->getAlignment()
            ->setWrapText(true)
            ->setVertical(PHPExcel_Style_Alignment::VERTICAL_CENTER)
            ->setHorizontal(PHPExcel_Style_Alignment::HORIZONTAL_CENTER);
        $sheet->fromArray($this->data->toArray2([
            'Принято', 'Оплачено', 'обычное', 'выделенное маркером', 'жирный шрифт', 'в рамке', 'выделенное в рамке', 'фигурная рамка',
            'на цветном фоне', 'размещение на сайт', 'Топ 1', 'Топ 2', 'Топ 3', 'Топ 4'
        ]));


        $w = \PHPExcel_IOFactory::createWriter($xls, 'Excel5');
        $w->save($this->getFilePath());

    }

} 