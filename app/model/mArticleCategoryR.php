<?php

namespace app\model;

use think\model\Pivot;

class mArticleCategoryR extends Pivot
{
    protected $table = 'ACRT';
    protected $pk = array('A_Id', 'C_Id');
    protected $autoWriteTimestamp = true;
    protected $createTime = 'CreateTime';
    protected $updateTime = 'LastUpdateTime';

    public function Category()
    {
        return $this->belongsTo(mCategory::class, $this->pk[1], $this->pk[1]);
    }
}