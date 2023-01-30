<?php

namespace app\model;

use think\Model;

class mRight extends Model
{
    protected $table = 'RightT';
    protected $pk = 'R_Id';
    protected $autoWriteTimestamp = true;
    protected $createTime = 'CreateTime';
    protected $updateTime = 'LastUpdateTime';

    public function UserRightR()
    {
        return $this->hasMany(mUserRightR::class, $this->pk, $this->pk);
    }
}