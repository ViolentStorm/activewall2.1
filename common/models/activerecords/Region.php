<?php

namespace common\models\activerecords;

use common\models\activerecords\activequeries\RegionQuery;
use Yii;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "aw_regions".
 *
 * @property integer $id
 * @property string $name
 * @property string $name_e
 * @property string $name_y
 * @property string $name_ya
 * @property integer $status
 * @property City[] $cities
 * @property Client[] $clients
 */
class Region extends ActiveRecord
{
    const STATUS_ACTIVE = 1;


    /**
     * @inheritdoc
     */
    public static function find()
    {
        return new RegionQuery(get_called_class());
    }

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'aw_regions';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['name', 'name_e', 'name_y', 'name_ya', 'status'], 'required'],
            [['status'], 'integer'],
            [['name'], 'string', 'max' => 100],
            [['name_e', 'name_y', 'name_ya'], 'string', 'max' => 50]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Name',
            'name_e' => 'Name E',
            'name_y' => 'Name Y',
            'name_ya' => 'Name Ya',
            'status' => 'Status',
        ];
    }

    public function getCities()
    {
        return $this->hasMany(City::className(), ['region_id' => 'id']);
    }

    public function getClients()
    {
        return $this->hasMany(Client::className(), ['region_id' => 'id']);
    }

    public static function asBootstrapMenu($regions, $curId = false)
    {

        if (!$curId)
            $curId = Yii::$app->user->identity ? Yii::$app->user->identity->cookies->currentRegionId : false;

        $curName = 'Выберите регион';
        $acv = null;

        $menu = [];

        foreach ($regions as $reg) {
            $acv = $curId == $reg['id'];
            if ($acv)
                $curName = $reg['name'];
            $menu[] = [
                'label' => $reg['name'],
                'url' => '#',
                'options' => [
                    'regid' => $reg['id'],
                ],
                'active' => $acv,
            ];
        }

        return [
            'label' => $curName,
            'items' => $menu,
            'options' => [
                'id' => 'region',
                'style' => 'width: 165px;',
                'class' => 'text-center',
            ],
        ];

    }

}
