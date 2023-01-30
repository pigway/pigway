<?php

namespace app\model;

use think\Model;

class mArticle extends Model
{
    protected $table = 'ArticleT';
    protected $pk = 'A_Id';
    protected $autoWriteTimestamp = true;
    protected $createTime = 'CreateTime';
    protected $updateTime = 'LastUpdateTime';

    public function Author()
    {
        return $this->belongsToMany(mAuthor::class, mArticleAuthorR::class, 'AU_Id', $this->pk);
    }

    public function ArticleAuthorR()
    {
        return $this->hasMany(mArticleAuthorR::class, $this->pk, $this->pk);
    }

    public function ArticleCategoryR()
    {
        return $this->hasMany(mArticleCategoryR::class, $this->pk, $this->pk);
    }
}