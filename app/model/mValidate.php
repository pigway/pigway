<?php

namespace app\model;

use think\Model;

class mValidate extends Model
{
    protected $table = 'ValidateT';
    protected $pk = 'V_Id';
    protected $autoWriteTimestamp = true;
    protected $createTime = 'CreateTime';
    protected $updateTime = 'LastUpdateTime';

    public function user()
    {
        return $this->belongsTo(mUser::class, 'U_Id')->withoutField('U_Password');
    }
}