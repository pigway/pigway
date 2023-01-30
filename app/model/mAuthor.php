<?php

namespace app\model;

use think\Model;

class mAuthor extends Model
{
    protected $table = 'AuthorT';
    protected $pk = 'AU_Id';
    protected $fk = 'U_Id'; // 自定义属性，非继承自Model类
    protected $autoWriteTimestamp = true;
    protected $createTime = 'CreateTime';
    protected $updateTime = 'LastUpdateTime';

    public function User()
    {
        return $this->belongsTo(mUser::class, $this->fk, $this->fk);
    }

    public function ArticleAuthorR()
    {
        return $this->hasMany(mArticleAuthorR::class, $this->pk, $this->pk);
    }
}