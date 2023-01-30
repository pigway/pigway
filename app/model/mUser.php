<?php

namespace app\model;

use think\Model;

class mUser extends Model
{
    protected $table = 'UserT';
    protected $pk = 'U_Id';
    protected $autoWriteTimestamp = true;
    protected $createTime = 'CreateTime';
    protected $updateTime = 'LastUpdateTime';
}