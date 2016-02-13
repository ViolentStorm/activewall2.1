<?php
/**
 * Created by PhpStorm.
 * User: Violent
 * Date: 25.03.15
 * Time: 10:50
 */

namespace frontend\models\activerecords\aw\activequeries;


use frontend\models\activerecords\aw\CategoryProperty;
use yii\db\ActiveQuery;

class CategoryProperyQuery extends ActiveQuery
{

    public function active()
    {
        return $this->where(['status' => CategoryProperty::STATUS_ACTIVE]);
    }

    public function remoteAccess()
    {
        return $this->ownerRemote()->andWhere(['<>', 'status', CategoryProperty::STATUS_DELETE_BY_LOCAL]);
    }

    public function ownerRemote()
    {
        return $this->where(['owner' => CategoryProperty::OWNER_REMOTE]);
    }

    public function edition($editionId)
    {
        return $this->where(['edition_id' => $editionId]);
    }

    public function deleted()
    {
        return $this->where(['status' => [CategoryProperty::STATUS_DELETE_BY_REMOTE, CategoryProperty::STATUS_DELETE_BY_LOCAL]]);
    }

} 