<?php

namespace app\model;

use think\model\Pivot;

class mArticleAuthorR extends Pivot
{
    protected $table = 'AAURT';
    protected $pk = array('A_Id', 'AU_Id');
    protected $autoWriteTimestamp = true;
    protected $createTime = 'CreateTime';
    protected $updateTime = 'LastUpdateTime';

    public function Author()
    {
        return $this->belongsTo(mAuthor::class, $this->pk[1], $this->pk[1]);
    }
}