<?php

namespace app\model;

use think\Model;

class mCategory extends Model
{
    protected $table = 'CategoryT';
    protected $pk = 'C_Id';
    protected $fk = 'Cat_C_Id'; // 自定义属性，非继承自Model类
    protected $autoWriteTimestamp = true;
    protected $createTime = 'CreateTime';
    protected $updateTime = 'LastUpdateTime';

    public function Category() // 自联关系
    {
        return $this->belongsTo(mCategory::class, $this->pk, $this->fk);
    }

    public function ArticleCategoryR()
    {
        return $this->hasMany(mArticleCategoryR::class, $this->pk, $this->pk);
    }
}