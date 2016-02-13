<?php

namespace frontend\models\activerecords\aw;

use common\models\activerecords\CategoriesExport;
use frontend\models\activerecords\aw\activequeries\CategoryQuery;
use Yii;

/**
 * This is the model class for table "aw_categories".
 *
 * @property integer $id
 * @property integer $source_id
 * @property integer $parent_sid
 * @property integer $edition_id
 * @property string $name
 * @property integer $status
 * @property string $syncdate
 * @property integer $owner
 * @property integer $cexport_sid
 *
 * @property Category $parent
 * @property CategoryProperty[] $properties
 * @property CategoryProperty[] $propertiess
 * @property CategoriesExport $export
 * @property \common\models\activerecords\ExportTemplate $template
 */
class Category extends SyncRecord
{

    const TREE_DEFAULT = 1;
    const TREE_SELECT = 2;
    const TREE_SELECT_BYSID = 3;

    const STATUS_ACTIVE = 1;
    const STATUS_DELETED = 2;
    const STATUS_DELETED_BYREMOTE = 3;
    const STATUS_DELETED_BYLOCAL = 4;

    const OWNER_LOCAL = 1;
    const OWNER_REMOTE = 2;

    private static $_current;
    private static $_currentCategories;


    public static function find()
    {
        return new CategoryQuery(get_called_class());
    }

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'aw_categories';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['parent_sid', 'edition_id', 'name'], 'required'],
            [['source_id', 'parent_sid', 'edition_id', 'status', 'owner'], 'integer'],
            [['syncdate'], 'safe'],
            [['name'], 'string', 'max' => 100],
            ['owner', 'default', 'value' => self::OWNER_LOCAL],
            ['status', 'default', 'value' => self::STATUS_ACTIVE],
            ['source_id', 'default', 'value' => -1]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'source_id' => 'Source ID',
            'parent_sid' => 'Parent Sid',
            'edition_id' => 'Edition ID',
            'name' => 'Name',
            'status' => 'Status',
            'syncdate' => 'Syncdate',
        ];
    }

    public static function getAllChildren($cat_id, $list = false)
    {
        if(!$list)
            $list = [];
        $model = '';

        $model = self::find()->where(['parent_sid' => $cat_id])->all();

        if($model){
            foreach($model as $m){

                $list []= self::getAllChildren($m->source_id, $list);
            }

        }
        else
            $list []= $cat_id;

        return $list;

    }
    /***
     * @param bool|Category[] $cats
     * @param int $pid
     * @param int $type
     * @return array
     */
    public static function treeBuilder($cats = false, $pid = 0, $type = self::TREE_DEFAULT)
    {
        if (!is_array($cats))
            $cats = self::current();

        switch ($type){
            case self::TREE_SELECT:
                return self::treeBuilerSelect($cats, $pid);
            case self::TREE_SELECT_BYSID:
                return self::treeBuilerSelectBySid($cats, $pid);
            case self::TREE_DEFAULT:
            default:
                return self::treeBuilerDefault($cats, $pid);
        }
    }

    public static function treeBuilerDefault($cats, $pid = 0)
    {
        $list = [];
        foreach ($cats as $cat) {
            if($cat->parent_sid != $pid)
                continue;
            $list[$cat->id] = [
                'name' => $cat->name,
                'status' => $cat->status,
                'owner' => $cat->owner,
                'cexport_sid' => $cat->cexport_sid,
                'source_id' => $cat->source_id,
                'childs' => self::treeBuilerDefault($cats, $cat->source_id)
            ];
        }

        return $list;
    }

    public static function treeBuilerSelect($cats, $pid = 0, $pPath = '')
    {
        $list = [];
        foreach ($cats as $cat) {
            if($cat->parent_sid != $pid)
                continue;
            $list[$cat->id] = $pPath . '/' . $cat->name;
            $list = $list + self::treeBuilerSelect($cats, $cat->source_id, $list[$cat->id]);
        }

        return $list;
    }

    public static function treeBuilerSelectBySid($cats, $pid = 0, $pPath = '')
    {
        $list = [];
        foreach ($cats as $cat) {
            if($cat->parent_sid != $pid)
                continue;
            $list[$cat->source_id] = $pPath . '/' . $cat->name;
            $list = $list + self::treeBuilerSelectBySid($cats, $cat->source_id, $list[$cat->source_id]);
        }

        return $list;
    }

    public static function currentTree()
    {
        return self::treeBuilder(self::current());
    }



    /***
     * @return Category[]
     */
    public static function current()
    {
        if (!self::$_current)
            self::$_current = self::find()->current()->active()->all();
        return self::$_current;
    }

    public static function currentByParent()
    {
        $all = self::current();
        $itms = [];
        foreach($all as $one){
            if(!isset($itms[$one->parent_sid]))
                $itms[$one->parent_sid] = [];
            $itms[$one->parent_sid][] = $one;
        }
        return $itms;
    }

    public static function allChildIds($all = false, $pid)
    {
        $ids = [];
        if (!$all)
            $all = self::currentByParent();

        if (isset($all[$pid]))
            foreach ($all[$pid] as $one) {
                $ids[] = $one->id;
                $ids = array_merge($ids, self::allChildIds($all, $one->source_id));
            }
        //$ids[] = $pid;

        return $ids;

    }

    public function send()
    {
        // TODO: Implement send() method.
    }

    public function receive()
    {
        // TODO: Implement receive() method.
    }

    public function getParent()
    {
        return $this->hasOne(self::className(), ['source_id' => 'parent_sid']);
    }

    public function getProperties()
    {
        return $this->hasMany(CategoryProperty::className(), ['category_sid' => 'source_id'])->where([
            'region_sid' => [Region::getCurrent()->source_id, 0]
        ])->orderBy('order');
    }

    // костыль
    public function getPropertiess()
    {
        return $this->hasMany(CategoryProperty::className(), ['category_sid' => 'source_id', 'edition_id' => 'edition_id']);
    }

    public function getExport()
    {
        return $this->hasOne(CategoriesExport::className(), ['source_id' => 'cexport_sid', 'edition_id' => 'edition_id']);
    }


    public static function currentCategories()
    {

        if (!self::$_currentCategories)
            self::$_currentCategories = self::find()
                ->where(['edition_id' => Edition::getCurrentEdId()])
                ->select(['id','source_id', 'parent_sid', 'name'])->asArray()->indexBy('source_id')->all();

        return self::$_currentCategories;
    }

    public function getTemplate()
    {
        return $this->hasOne(\common\models\activerecords\ExportTemplate::className(), ['category_id' => 'id']);
    }

    public function getMessages()
    {
        return $this->hasMany(Message::className(), ['category_id' => 'id']);
    }

}
