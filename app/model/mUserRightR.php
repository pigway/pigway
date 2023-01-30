<?php

namespace app\model;

use think\model\Pivot;

class mUserRightR extends Pivot
{
    protected $table = 'URRT';
    protected $pk = array('U_Id', 'R_Id');
    protected $autoWriteTimestamp = true;
    protected $createTime = 'CreateTime';
    protected $updateTime = 'LastUpdateTime';

    public function Right()
    {
        return $this->belongsTo(mRight::class, $this->pk[1], $this->pk[1]);
    }
}