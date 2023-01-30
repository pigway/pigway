<?php

namespace app\controller;

use app\BaseController;
use app\model\mArticleAuthorR;
use app\model\mArticleCategoryR;
use app\model\mAuthor;
use app\model\mCategory;
use think\db\BaseQuery;
use think\Request;
use app\validate\vImage;
use think\Collection;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\facade\Db;
use think\facade\Filesystem;
use think\facade\View;
use app\model\mArticle;
use think\exception\ValidateException;

class Article extends BaseController
{
    const DEFAULT_ROW_NUMBER = 10;
    const DEFAULT_PAGE_DISPLAY_HALF_LENGTH = 2;

    /***
     * @param $where
     * @param $map
     * @param $hasWhere
     * @return int
     */
    public static function countArticle($where, $map = [], $hasWhereCategory = [], $hasWhereAuthor = [])
    {
        return mArticle::where($where)
            ->where(function ($query) use ($map) {
                $query->whereOr($map);
            })
            ->hasWhere('ArticleCategoryR', $hasWhereCategory, '*', 'LEFT')
            ->hasWhere('ArticleAuthorR', $hasWhereAuthor, '*', 'LEFT')
            ->count();
    }

    /***
     * @param $where
     * @param array $map
     * @param array $hasWhere
     * @param int $page
     * @param int $row
     * @param string $order
     * @return array|Collection|BaseQuery[]
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public static function tryGetArticle($where, $map = [], $hasWhereCategory = [], $hasWhereAuthor = [], $page = 1, $row = self::DEFAULT_ROW_NUMBER, $order = 'CreateTime DESC')
    {
        return mArticle::with([
            'ArticleAuthorR' => function ($query) {
                $query->where(['AAUR_Type' => Code::ARTICLE_AUTHOR_TYPE_CREATOR, 'State' => 0])->with([
                    'Author' => function ($query) {
                        $query->where(['State' => 0]);
                    }]);
            },
            'ArticleCategoryR' => function ($query) {
                $query->where(['State' => 0])->with([
                    'Category' => function ($query) {
                        $query->where(['State' => 0]);
                    }]);
            }
        ])
            ->where($where)
            ->where(function ($query) use ($map) {
                $query->whereOr($map);
            })
            ->hasWhere('ArticleCategoryR', $hasWhereCategory, '*', 'LEFT')
            ->hasWhere('ArticleAuthorR', $hasWhereAuthor, '*', 'LEFT')
            ->page($page, $row)
            ->order($order)
            ->select();
    }

    /***
     * @param $where
     * @param string $order
     * @return mAuthor[]|array|Collection
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public static function tryLoadAuthor($where, $order = 'AU_Taxis')
    {
        return mAuthor::where($where)->order($order)->select();
    }

    /***
     * @param $where
     * @param string $order
     * @return mCategory[]|array|Collection
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public static function tryLoadCategory($where, $order = 'Cat_C_Id, C_Taxis')
    {
        return mCategory::where($where)->withCount(['ArticleCategoryR' => function ($query, &$alias) {
            $query->where(['State' => 0]);
            $alias = 'ArticleCategoryR_count';
        }])->order($order)->select();
    }

    /***
     * @param $cid
     * @param $idList
     * @param $where
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public static function buildCategoryDescendantsIdList($cid, &$idList, $where)
    {
        $categoryList = mCategory::where(['Cat_C_Id' => $cid])->where($where)->select();
        for ($i = 0; $i < count($categoryList); $i++) {
            array_push($idList, $categoryList[$i]['C_Id']);
            static::buildCategoryDescendantsIdList($categoryList[$i]['C_Id'], $idList, $where);
        }
    }

    /***
     * @param $parent
     * @param $pathArray
     * @param $node
     */
    /*private static function buildCategoryNode(&$parent, $pathArray, &$node)
    {
        $cid = array_shift($pathArray);
        if (count($pathArray) > 0) {
            static::buildCategoryNode($parent['child'][$cid], $pathArray, $node);
        } else {
            $parent['child'][$node['C_Id']] = $node;
        }
    }*/

    /***
     * @return array[]|mixed
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    /*public static function buildCategoryTree()
    {
        $categoryList = static::tryLoadCategory([
            'State' => 0
        ]);

        if ($categoryList->isEmpty()) {
            return ['child' => []]; // 返回一个模拟的初始化空树
        }

        $categoryList = $categoryList->toArray();

        //$categoryProcess = [];
        $categoryProcess = [['' => ['path' => '']]]; // 省去每一次depth是否为0比对，提高效率，需要保证depth为0的数据Cat_C_Id为null
        $categoryTree = [];
        foreach ($categoryList as $category) {
            //$category['path'] = ($depth === 0 ? '' : $categoryProcess[$category['C_Depth']][$category['Cat_C_Id']]['path'] . '/') . $category['C_Id'];
            $category['path'] = $categoryProcess[$category['C_Depth']][$category['Cat_C_Id']]['path'] . '/' . $category['C_Id'];
            $categoryProcess[$category['C_Depth'] + 1][$category['C_Id']] = $category;
            static::buildCategoryNode($categoryTree, explode('/', $category['path']), $category);
        }
        //return $categoryTree;
        return $categoryTree['child']['']; // 此处之已经剥除了$categoryProcess初始化带来的父级
    }*/

    /***
     * @param null $map
     * @param array $hasWhereAuthor
     * @return array|array[]
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public static function buildCategoryTree($map = null, $hasWhereAuthor = []): array
    {
        $categoryList = static::tryLoadCategory([
            'State' => 0
        ]);

        if ($categoryList->isEmpty()) {
            return ['child' => []]; // 返回一个模拟的初始化空树
        }

        $categoryList = $categoryList->toArray();

        $categoryProcess = [null => ['path' => '', 'depth' => -1]];
        /*$categoryTree = [];

        foreach ($categoryList as $key => $category) {
            if ($category['Cat_C_Id'] === null) {
                $category['path'] = '/' . $category['C_Id'];
                $categoryTree['child'][$category['C_Id']] = $category; // 这里有点没明白，使用引用 = &$category 反而在退出foreach后缺少了path属性，查询资料说foreach中的$category是拷贝
                $categoryProcess[$category['C_Id']] = &$categoryTree['child'][$category['C_Id']];
                unset($categoryList[$key]);
            }
        }*/

        while (count($categoryList) > 0) {
            foreach ($categoryList as $key => $category) {
                if (isset($categoryProcess[$category['Cat_C_Id']])) {
                    $parent = &$categoryProcess[$category['Cat_C_Id']];
                    $category['path'] = $parent['path'] . '/' . $category['C_Id'];
                    $category['depth'] = $parent['depth'] + 1;
                    $parent['child'][$category['C_Id']] = $category;
                    $categoryProcess[$category['C_Id']] = &$parent['child'][$category['C_Id']];

                    // 增加所有祖先级分类计数，但是因为祖先级分类计数包含了本级文章且在列表处仅显示本级文章，所以累积计数容易混淆，故不使用；若要使用，点击分类应显示本级+所有子孙级分类文章
                    // 本方法如子孙级分类文章有重复，会导致实际文章数小于分类计数
                    /*$ancestors = explode('/', $parent['path']);
                    for ($i = 1; $i < count($ancestors); $i++) {
                        $categoryProcess[$ancestors[$i]]['ArticleCategoryR_count'] += $category['ArticleCategoryR_count'];
                    }*/

                    // 对分类及所有子孙级分类文章精确计数
                    $categoryIdList = [$category['C_Id']];
                    static::buildCategoryDescendantsIdList($category['C_Id'], $categoryIdList, ['State' => 0]);
                    $hasWhereCategory = [['C_Id', 'IN', $categoryIdList]];
                    $categoryProcess[$category['C_Id']]['ArticleCategoryR_count'] = static::countArticle(['mArticle.State' => 0], $map, $hasWhereCategory, $hasWhereAuthor);

                    unset($categoryList[$key]);
                }
            }
        }

        return $categoryProcess[null];
    }

    /***
     * @param $categoryNode
     * @param $html
     * @param $checkBox
     */
    public static function buildCategoryListGroupHTML($categoryNode, &$html, $checkBox = false)
    {
        if (isset($categoryNode['child'])) {
            foreach ($categoryNode['child'] as $categoryChildNode) {

                if ($checkBox) {
                    $html .= '<label class="list-group-item d-flex"><input type="checkbox" value="' . $categoryChildNode['C_Id'] . '" class="form-check-input me-1" style="margin-left: ';
                } else {
                    $html .= '<button id="btn-category_' . $categoryChildNode['C_Id'] . '" type="button" class="list-group-item list-group-item-action d-flex align-items-center" c-alias="' . $categoryChildNode['C_Alias'] . '" c-depth="' . $categoryChildNode['depth'] . '"><span style="margin-left: ';
                }
                $html .= $categoryChildNode['depth'] . 'em';
                if ($checkBox) {
                    $html .= '">';
                } else {
                    $html .= '">';
                }
                $html .= $categoryChildNode['C_Name'];
                if ($checkBox) {
                    $html .= '<span class="badge bg-light text-silence rounded-pill ms-auto align-self-center">' . $categoryChildNode['ArticleCategoryR_count'] . '</span></label>';
                } else {
                    $html .= '</span><span class="badge bg-light text-silence rounded-pill ms-auto">' . $categoryChildNode['ArticleCategoryR_count'] . '</span></button>';
                }

                if (isset($categoryChildNode['child']) && count($categoryChildNode['child']) > 0) {
                    static::buildCategoryListGroupHTML($categoryChildNode, $html, $checkBox);
                }
            }
        }
    }

    /***
     * @param $categoryNode
     * @param $html
     * @param false $checkBox
     * @param false $isEnd
     */
    public static function buildCategoryListCollapseGroupHTML($categoryNode, &$html, $checkBox = false, $isEnd = true)
    {
        // 本方法需要页面加载如下辅助CSS，不需要设置外部ul的overflow = auto/hidden，唯一问题是css选择器.always-last-node[aria-expanded=false]因为bootstrap collapse执行动画顺序先执行aria-expanded=true，收回时异常视觉效果为左右底角提前变圆再收回
        /*button.active + a {
            z-index: 2;
            color: #fff;
            background-color: #0d6efd;
            border-color: #0d6efd;
        }

        button.active + a, button.active + a:link, button.active + a:hover, button.active + a:active, button.active + a:visited {
            color: #fff !important;
        }

        @media (min-width: 768px) {
            ul {
                max-height: calc(100vh - 56px - 37px - 38px - 43px - 16px - 16px - 16px);
            }
        }

        .always-last-node[aria-expanded=false] {
            border-bottom-width: 0;
            border-bottom-right-radius: calc(0.25rem - 1px);
            border-bottom-left-radius: calc(0.25rem - 1px);
        }
        */
        // 一个解决方案就是设置外部ul的overflow = auto/hidden，但可能引起未预料的错误，也就是使用如下辅助CSS替换.always-last-node[aria-expanded=false]定义
        /*#div-category ul {
            overflow: auto;
        }*/


        if (isset($categoryNode['child'])) {
            if ($categoryNode['depth'] >= 0) {
                $html .= '<div id="div-collapse_' . $categoryNode['C_Id'] . '" class="collapse" style="border: none; padding: 0; margin: 0">';
            }
            $count = 0;
            foreach ($categoryNode['child'] as $categoryChildNode) {
                $count++;
                if ($checkBox) {
                    $html .= '<label class="list-group-item d-flex' . (isset($categoryChildNode['child']) && count($categoryChildNode['child']) > 0 ? ' pb-0' : '') . '" style="border-left: none; border-right: none' . ((isset($categoryChildNode['child']) && count($categoryChildNode['child']) > 0) || $count === count($categoryNode['child']) ? '; border-bottom: none' : '') . ($isEnd && $count === count($categoryNode['child']) && !isset($categoryChildNode['child']) ? '; border-bottom-width: 0; border-bottom-right-radius: calc(0.25rem - 1px); border-bottom-left-radius: calc(0.25rem - 1px)' : '') . '"><input type="checkbox" value="' . $categoryChildNode['C_Id'] . '" class="form-check-input me-1" style="margin-left: ';
                } else {
                    $html .= '<button id="btn-category_' . $categoryChildNode['C_Id'] . '" type="button" class="list-group-item list-group-item-action d-flex align-items-center' . (isset($categoryChildNode['child']) && count($categoryChildNode['child']) > 0 ? ' pb-0' : '') . '" c-alias="' . $categoryChildNode['C_Alias'] . '" c-depth="' . $categoryChildNode['depth'] . '" style="border-left: none; border-right: none' . ((isset($categoryChildNode['child']) && count($categoryChildNode['child']) > 0) || $count === count($categoryNode['child']) ? '; border-bottom: none' : '') . ($isEnd && $count === count($categoryNode['child']) && !isset($categoryChildNode['child']) ? '; border-bottom-width: 0; border-bottom-right-radius: calc(0.25rem - 1px); border-bottom-left-radius: calc(0.25rem - 1px)' : '') . '"><span style="margin-left: ';
                }
                $html .= $categoryChildNode['depth'] . 'em';
                if ($checkBox) {
                    $html .= '">';
                } else {
                    $html .= '">';
                }
                $html .= $categoryChildNode['C_Name'];
                if ($checkBox) {
                    $html .= '<span class="badge bg-light text-silence rounded-pill ms-auto align-self-center">' . $categoryChildNode['ArticleCategoryR_count'] . '</span></label>';
                } else {
                    $html .= '</span><span class="badge bg-light text-silence rounded-pill ms-auto">' . $categoryChildNode['ArticleCategoryR_count'] . '</span></button>';
                }
                if (isset($categoryChildNode['child']) && count($categoryChildNode['child']) > 0) {
                    $html .= '<a class="my-0 px-3 py-0 d-block text-silence text-decoration-none' . ($isEnd && $count == count($categoryNode['child']) ? ' always-last-node' : '') . '" style="cursor: pointer" data-bs-toggle="collapse" data-bs-target="#div-collapse_' . $categoryChildNode['C_Id'] . '" aria-expanded="false"><span style="margin-left: ' . $categoryChildNode['depth'] . 'em">+</span></a>';
                    /*$html .= '<a class="my-0 px-3 py-0 d-block text-silence text-decoration-none" style="cursor: pointer" data-bs-toggle="collapse" data-bs-target="#div-collapse_' . $categoryChildNode['C_Id'] . '" aria-expanded="false"><span style="margin-left: ' . $categoryChildNode['depth'] . 'em">+</span></a>';*/
                    static::buildCategoryListCollapseGroupHTML($categoryChildNode, $html, $checkBox, $count === count($categoryNode['child']) ? $isEnd : false);
                }
            }
            if ($categoryNode['depth'] >= 0) {
                $html .= '</div>';
            }
        }
    }

    /***
     * @param $categoryNode
     * @param $html
     * @param false $checkBox
     * @param false $isEnd
     */
    public static function buildCategoryListWrapCollapseGroupHTML($categoryNode, &$html, $checkBox = false, $isEnd = true)
    {
        // 直接单独使用即可
        if (isset($categoryNode['child'])) {
            if ($categoryNode['depth'] >= 0) {
                $html .= '<div id="div-collapse_' . $categoryNode['C_Id'] . '" class="collapse" style="border: none; padding: 0; margin: 0">';
            }
            $count = 0;
            foreach ($categoryNode['child'] as $categoryChildNode) {
                $count++;
                if ($checkBox) {
                    $html .= '<label class="list-group-item d-flex" style="border-left: none; border-right: none' . ((isset($categoryChildNode['child']) && count($categoryChildNode['child']) > 0) || $count === count($categoryNode['child']) ? '; border-bottom: none' : '') . '"><input type="checkbox" value="' . $categoryChildNode['C_Id'] . '" class="form-check-input me-1" style="margin-left: ';
                } else {
                    $html .= '<button id="btn-category_' . $categoryChildNode['C_Id'] . '" type="button" class="list-group-item list-group-item-action d-flex align-items-center" c-alias="' . $categoryChildNode['C_Alias'] . '" c-depth="' . $categoryChildNode['depth'] . '" style="border-left: none; border-right: none' . ((isset($categoryChildNode['child']) && count($categoryChildNode['child']) > 0) || $count === count($categoryNode['child']) ? '; border-bottom: none' : '') . '"><span style="margin-left: ';
                }
                $html .= $categoryChildNode['depth'] . 'em';
                if ($checkBox) {
                    $html .= '">';
                } else {
                    $html .= '">';
                }
                $html .= $categoryChildNode['C_Name'];
                if ($checkBox) {
                    $html .= '<span class="badge bg-light text-silence rounded-pill ms-auto align-self-center">' . $categoryChildNode['ArticleCategoryR_count'] . '</span></label>';
                } else {
                    $html .= '</span><span class="badge bg-light text-silence rounded-pill ms-auto">' . $categoryChildNode['ArticleCategoryR_count'] . '</span></button>';
                }

                if (isset($categoryChildNode['child']) && count($categoryChildNode['child']) > 0) {
                    static::buildCategoryListWrapCollapseGroupHTML($categoryChildNode, $html, $checkBox, $count === count($categoryNode['child']) ? $isEnd : false);
                }
            }
            if ($categoryNode['depth'] >= 0) {
                $html .= '</div>';
            }
            if ($categoryNode['depth'] >= 0 && isset($categoryNode['child'])) {
                $html .= '<a class="my-0 px-3 py-0 d-block text-silence text-decoration-none" style="cursor: pointer;" data-bs-toggle="collapse" data-bs-target="#div-collapse_' . $categoryNode['C_Id'] . '" aria-expanded="false"><span style="margin-left: ' . $categoryNode['depth'] . 'em">+</span><small class="ms-1 text-silence">' . $categoryNode['C_Name'] . '</small></a>';
            }
        }
    }

    /***
     * @param $aid
     * @param $uid
     * @return bool
     */
    public static function checkUserArticleRight($aid, $uid)
    {
        if (mAuthor::where(['U_Id' => $uid, 'mAuthor.State' => 0])->hasWhere('ArticleAuthorR', ['A_Id' => $aid, 'State' => 0])->count() > 0) {
            return true;
        }
        return false;
    }

    /***
     * @param $auid
     * @param $uid
     * @return int
     */
    public static function checkUserAuthorRight($auid, $uid)
    {
        if (mAuthor::where(['AU_Id' => $auid, 'U_Id' => session('uid'), 'State' => 0])->count() === 1) {
            return true;
        }
        return false;
    }

    public function postMarkdown(Request $request)
    {
        if (User::checkUserRight(Code::USER_RIGHT_CODE_ARTICLE)) {
            $authorList = static::tryLoadAuthor([
                'U_Id' => session('uid'),
                'State' => 0
            ]);

            if ($authorList->isEmpty()) {
                return view('/message/report', [
                    'title' => getExceptionTitle(Code::RIGHT_ERROR),
                    'content' => lang('article.author.available.none')
                ]);
            }

            $categoryTree = static::buildCategoryTree();

            $categoryCheckboxListGroupHTML = '';
            static::buildCategoryListGroupHTML($categoryTree, $categoryCheckboxListGroupHTML, true);

            View::assign([
                'authorList' => $authorList,
                'categoryCheckboxListGroupHTML' => $categoryCheckboxListGroupHTML
            ]);
        }
        return User::getVerifiedUserRightView(Code::USER_RIGHT_CODE_ARTICLE, $request->url());
    }

    public function uploadImage(Request $request)
    {
        // 校验Token，防止重复提交和长时间滞留页面，需要开启Session，在\app\middleware.php中取消Session初始化注释
        /*if ($request->checkToken('__token__') !== true) {
            return json([
                'result' => Code::TOKEN_ERROR
            ]);
        }*/

        if (User::checkUserRight(Code::USER_RIGHT_CODE_ARTICLE)) {
            if (request()->isPost()) {

                $file = request()->file('editormd-image-file');

                validate(vImage::class)->check(['image' => $file]);

                if (!checkHex($file)) { // 简单检查木马
                    return json(['result' => Code::INPUT_ERROR, 'err' => ['code' => Code::UPLOAD_FILE_ILLEGAL, 'msg' => lang('illegal.file')]]);
                }

                $save_name = Filesystem::disk('public')->putFile(session('display_number') . '/article', $file);

                if ($save_name) {
                    $url = config('filesystem.disks.public.url') . '/' . $save_name;
                    return json(['result' => Code::DONE, 'msg' => lang('upload.image.success'), 'url' => $url]);
                }

                return json(['result' => Code::FAILED, 'msg' => lang('upload.image.fail')]);
            }

            return json(['result' => Code::REQUEST_ERROR]);
        }

        return json(['result' => Code::RIGHT_ERROR]);
    }

    public function createArticle(Request $request)
    {
        // 校验Token，防止重复提交和长时间滞留页面，需要开启Session，在\app\middleware.php中取消Session初始化注释
        if ($request->checkToken('__token__') !== true) {
            return json([
                'result' => Code::TOKEN_ERROR
            ]);
        }

        if (!User::checkUserRight(Code::USER_RIGHT_CODE_ARTICLE)) {
            return json([
                'result' => Code::RIGHT_ERROR,
                '__token__' => $request->buildToken()
            ]);
        }

        if (isset($_POST['auid']) && isNanoid($_POST['auid']) && static::checkUserAuthorRight($_POST['auid'], session('uid'))) {
            $auid = $_POST['auid'];
        } else {
            return json([
                'result' => Code::INPUT_ERROR,
                '__token__' => $request->buildToken(),
                'err' => [
                    'msg' => lang('article.author.available.none')
                ]
            ]);
        }
        if (isset($_POST['title']) && mb_strlen($_POST['title'], 'UTF-8') > 0) {
            $title = $_POST['title'];
        } else {
            $title = null;
        }
        if (isset($_POST['subtitle']) && mb_strlen($_POST['subtitle'], 'UTF-8') > 0) {
            $subtitle = $_POST['subtitle'];
        } else {
            $subtitle = null;
        }
        if (isset($_POST['contentFormat']) && $_POST['contentFormat'] >= 0) {
            $content_format = $_POST['contentFormat'];
        } else {
            $content_format = null;
        }
        if (isset($_POST['content']) && mb_strlen($_POST['content'], 'UTF-8') > 0) {
            $content = $_POST['content'];
        } else {
            $content = null;
        }
        if (isset($_POST['contentMarkdown']) && mb_strlen($_POST['contentMarkdown'], 'UTF-8') > 0) {
            $content_markdown = $_POST['contentMarkdown'];
        } else {
            $content_markdown = null;
        }

        $aid = nanoid();

        Db::startTrans();

        mArticle::create([
            'A_Id' => $aid,

            'A_Type' => 0,
            'A_Title' => $title,
            'A_Subtitle' => $subtitle,
            'A_ContentFormat' => $content_format,
            'A_Content' => $content,
            'A_ContentMarkdown' => $content_markdown,
            'A_PostTime' => date('Y-m-d H:i:s'),

            'State' => 0,
            'ExclusiveKey' => 0,
            'IsLocked' => 0
        ]);

        mArticleAuthorR::create([
            'A_Id' => $aid,
            'AU_Id' => $auid,
            'AAUR_Type' => Code::ARTICLE_AUTHOR_TYPE_CREATOR,

            'State' => 0,
            'ExclusiveKey' => 0,
            'IsLocked' => 0
        ]);

        if (isset($_POST['cidList'])) {
            foreach ($_POST['cidList'] as $cid) {
                mArticleCategoryR::create([
                    'A_Id' => $aid,
                    'C_Id' => $cid,

                    'State' => 0,
                    'ExclusiveKey' => 0,
                    'IsLocked' => 0
                ]);
            }
        }

        Db::commit();

        return json([
            'result' => Code::DONE,
            'aid' => $aid
        ]);
    }

    public function editMarkdown(Request $request)
    {
        if (User::checkUserRight(Code::USER_RIGHT_CODE_ARTICLE)) {
            if (!isset($_GET['aid']) || !isNanoid($_GET['aid']) || !static::checkUserArticleRight($_GET['aid'], session('uid'))) {
                return getExceptionMessageReportView([
                    'title' => lang('article.right.error'),
                    'content' => lang('article.right.error.help')
                ]);
            }

            $authorList = static::tryLoadAuthor([
                'U_Id' => session('uid'),
                'State' => 0
            ]);

            if ($authorList->isEmpty()) {
                return view('/message/report', [
                    'title' => getExceptionTitle(Code::RIGHT_ERROR),
                    'content' => lang('article.author.available.none')
                ]);
            }

            $categoryTree = static::buildCategoryTree();

            $categoryCheckboxListGroupHTML = '';
            static::buildCategoryListGroupHTML($categoryTree, $categoryCheckboxListGroupHTML, true);

            $article = static::tryGetArticle(['mArticle.A_Id' => $_GET['aid'], 'mArticle.State' => 0]);

            if ($article->isEmpty()) {
                return getExceptionMessageReportView(Code::DATABASE_ERROR);
            }

            View::assign([
                'authorList' => $authorList,
                'categoryCheckboxListGroupHTML' => $categoryCheckboxListGroupHTML,
                'article' => $article[0]
            ]);
        }
        return User::getVerifiedUserRightView(Code::USER_RIGHT_CODE_ARTICLE, $request->url());
    }

    public function updateArticle(Request $request)
    {
        // 校验Token，防止重复提交和长时间滞留页面，需要开启Session，在\app\middleware.php中取消Session初始化注释
        if ($request->checkToken('__token__') !== true) {
            return json([
                'result' => Code::TOKEN_ERROR
            ]);
        }

        if (!User::checkUserRight(Code::USER_RIGHT_CODE_ARTICLE)) {
            return json([
                'result' => Code::RIGHT_ERROR,
                '__token__' => $request->buildToken()
            ]);
        }

        if (isset($_POST['aid']) && isNanoid($_POST['aid'])) {
            $aid = $_POST['aid'];
        } else {
            return json([
                'result' => Code::REQUEST_ERROR,
                '__token__' => $request->buildToken()
            ]);
        }
        if (isset($_POST['auid']) && isNanoid($_POST['auid']) && static::checkUserAuthorRight($_POST['auid'], session('uid'))) {
            $auid = $_POST['auid'];
        } else {
            return json([
                'result' => Code::INPUT_ERROR,
                '__token__' => $request->buildToken(),
                'err' => [
                    'msg' => lang('article.author.available.none')
                ]
            ]);
        }
        if (isset($_POST['title']) && mb_strlen($_POST['title'], 'UTF-8') > 0) {
            $title = $_POST['title'];
        } else {
            $title = null;
        }
        if (isset($_POST['subtitle']) && mb_strlen($_POST['subtitle'], 'UTF-8') > 0) {
            $subtitle = $_POST['subtitle'];
        } else {
            $subtitle = null;
        }
        if (isset($_POST['contentFormat']) && $_POST['contentFormat'] >= 0) {
            $content_format = $_POST['contentFormat'];
        } else {
            $content_format = null;
        }
        if (isset($_POST['content']) && mb_strlen($_POST['content'], 'UTF-8') > 0) {
            $content = $_POST['content'];
        } else {
            $content = null;
        }
        if (isset($_POST['contentMarkdown']) && mb_strlen($_POST['contentMarkdown'], 'UTF-8') > 0) {
            $content_markdown = $_POST['contentMarkdown'];
        } else {
            $content_markdown = null;
        }

        Db::startTrans();

        $result = mArticle::where([
            'A_Id' => $aid,

            'State' => 0,
            'IsLocked' => false
        ])->update([
            'A_Title' => $title,
            'A_Subtitle' => $subtitle,
            'A_ContentFormat' => $content_format,
            'A_Content' => $content,
            'A_ContentMarkdown' => $content_markdown,

            'LastUpdateTime' => date('Y-m-d H:i:s'),
            'ExclusiveKey' => Db::raw('ExclusiveKey + 1')
        ]);

        if ($result !== 1) {
            Db::rollback();

            return json([
                'result' => Code::DATABASE_ERROR,
                '__token__' => $request->buildToken()
            ]);
        }

        /*$articleAuthorR = mArticleAuthorR::where([
            'A_Id' => $aid,
            'AAUR_Type' => Code::ARTICLE_AUTHOR_TYPE_CREATOR,

            'State' => 0
        ])->findOrEmpty();

        if ($articleAuthorR->isEmpty()) { // 有可能原创建人被移除
            mArticleAuthorR::create([
                'A_Id' => $aid,
                'AU_Id' => $auid,
                'AAUR_Type' => Code::ARTICLE_AUTHOR_TYPE_CREATOR,

                'State' => 0,
                'ExclusiveKey' => 0,
                'IsLocked' => 0
            ]);
        } else if ($articleAuthorR['AU_Id'] !== $auid) {
            $result = mArticleAuthorR::where([
                'A_Id' => $aid,
                'AU_Id' => $articleAuthorR['AU_Id'],

                'State' => 0,
                'IsLocked' => false
            ])->update([
                'AU_Id' => $auid,

                'LastUpdateTime' => date('Y-m-d H:i:s'),
                'ExclusiveKey' => Db::raw('ExclusiveKey + 1')
            ]);

            if ($result !== 1) {
                Db::rollback();

                return json([
                    'result' => Code::DATABASE_ERROR
                ]);
            }
        }*/

        $result = mArticleAuthorR::where([
            'A_Id' => $aid,
            'AU_Id' => Db::raw('<> "' . $auid . '"'),
            'AAUR_Type' => Code::ARTICLE_AUTHOR_TYPE_CREATOR,

            'State' => 0,
            'IsLocked' => false
        ])->update([
            'AU_Id' => $auid,

            'LastUpdateTime' => date('Y-m-d H:i:s'),
            'ExclusiveKey' => Db::raw('ExclusiveKey + 1')
        ]);

        if ($result < 0) {
            Db::rollback();

            return json([
                'result' => Code::DATABASE_ERROR,
                '__token__' => $request->buildToken()
            ]);
        }

        if (isset($_POST['cidList'])) {
            // 先将未在集合内的Category删除
            mArticleCategoryR::where([
                'A_Id' => $aid,

                'State' => 0,
                'IsLocked' => false
            ])->whereNotIn('C_Id', $_POST['cidList'])->delete();
            // 查询已有Category
            $categoryIdList = mArticleCategoryR::where([
                'A_Id' => $aid,

                'State' => 0,
                'IsLocked' => false
            ])->field('C_Id')->select()->toArray();
            // 进行批量更新
            foreach ($_POST['cidList'] as $cid) {
                if (!in_array(['C_Id' => $cid], $categoryIdList)) {
                    mArticleCategoryR::create([
                        'A_Id' => $aid,
                        'C_Id' => $cid,

                        'State' => 0,
                        'ExclusiveKey' => 0,
                        'IsLocked' => 0
                    ]);
                }
            }
        } else {
            mArticleCategoryR::where([
                'A_Id' => $aid,

                'State' => 0,
                'IsLocked' => false
            ])->delete();
        }

        Db::commit();

        return json([
            'result' => Code::DONE
        ]);
    }

    public function detail(Request $request)
    {
        if (isset($_GET['a'])) {
            $article = static::tryGetArticle(['mArticle.A_Alias' => $_GET['a'], 'mArticle.State' => 0]);
        }
        if (isset($_GET['aid']) && isNanoid($_GET['aid'])) {
            $article = static::tryGetArticle(['mArticle.A_Id' => $_GET['aid'], 'mArticle.State' => 0]);
        }

        if (!isset($article) || $article->isEmpty()) {
            return getExceptionMessageReportView(Code::REQUEST_ERROR);
        }

        View::assign([
            'article' => $article[0]
        ]);

        return view();
    }

    /***
     * @param $categoryNode
     * @param $html
     * @param $checkBox
     */
    public static function buildCategoryTableHTML($categoryNode, &$html, $checkBox = false)
    {
        if (isset($categoryNode['child'])) {
            foreach ($categoryNode['child'] as $categoryChildNode) {

                $html .= '<tr>';
                $html .= '<th scope="row" class="text-start">';
                if ($checkBox) {
                    $html .= '<label class="d-flex"><input id="chk-category_' . $categoryChildNode['C_Id'] . '" class="form-check-input me-1" type="checkbox" value="' . $categoryChildNode['C_Id'] . '" style="margin-left: ';
                } else {
                    $html .= '<span style="margin-left: ';
                }
                $html .= $categoryChildNode['depth'] . 'em';
                if ($checkBox) {
                    $html .= '">' . $categoryChildNode['C_Name'] . '</label>';
                } else {
                    $html .= '">' . $categoryChildNode['C_Name'] . '</span>';
                }
                $html .= '</th>';
                $html .= '<td>' . $categoryChildNode['C_Alias'] . '</td>';
                $html .= '<td>' . $categoryChildNode['C_Taxis'] . '</td>';
                //$html .= '<td class="text-start"><span style="margin-left: ' . $categoryChildNode['depth'] . 'em">' . $categoryChildNode['C_Taxis'] . '</span></td>';
                $html .= '<td class="text-silence">' . $categoryChildNode['ArticleCategoryR_count'] . '</td>';
                $html .= '<td>' . $categoryChildNode['C_Remark'] . '</td>';
                $html .= '<td class="text-nowrap"><a href="javascript:;" id="lnk-edit_' . $categoryChildNode['C_Id'] . '" c-operation="edit" c-parent="' . $categoryChildNode['Cat_C_Id'] . '" c-name="' . $categoryChildNode['C_Name'] . '" c-alias="' . $categoryChildNode['C_Alias'] . '" c-taxis="' . $categoryChildNode['C_Taxis'] . '" c-remark="' . $categoryChildNode['C_Remark'] . '">' . lang('sys.edit') . '</a><span class="text-faintly mx-1">|</span><a href="javascript:;" id="lnk-remove_' . $categoryChildNode['C_Id'] . '" c-operation="remove">' . lang('sys.remove') . '</a></td>';
                $html .= '</tr>';

                if (isset($categoryChildNode['child']) && count($categoryChildNode['child']) > 0) {
                    static::buildCategoryTableHTML($categoryChildNode, $html, $checkBox);
                }
            }
        }
    }

    public function category(Request $request)
    {
        if (User::checkUserRight(Code::USER_RIGHT_CODE_CATEGORY)) {
            $categoryTree = static::buildCategoryTree();

            $categoryListGroupHTML = '';
            static::buildCategoryListGroupHTML($categoryTree, $categoryListGroupHTML, false);

            $categoryCheckboxTableHTML = '';
            static::buildCategoryTableHTML($categoryTree, $categoryCheckboxTableHTML, false);

            View::assign([
                'categoryListGroupHTML' => $categoryListGroupHTML,
                'categoryCheckboxTableHTML' => $categoryCheckboxTableHTML
            ]);
        }
        return User::getVerifiedUserRightView(Code::USER_RIGHT_CODE_CATEGORY, $request->url());
    }

    public function createCategory(Request $request)
    {
        // 校验Token，防止重复提交和长时间滞留页面，需要开启Session，在\app\middleware.php中取消Session初始化注释
        if ($request->checkToken('__token__') !== true) {
            return json([
                'result' => Code::TOKEN_ERROR
            ]);
        }

        if (!User::checkUserRight(Code::USER_RIGHT_CODE_CATEGORY)) {
            return json([
                'result' => Code::RIGHT_ERROR,
                '__token__' => $request->buildToken()
            ]);
        }

        if (isset($_POST['parentId']) && isNanoid($_POST['parentId'])) {
            $parentId = $_POST['parentId'];
        } else {
            $parentId = null;
        }
        if (isset($_POST['name']) && mb_strlen($_POST['name'], 'UTF-8') > 0) {
            $name = $_POST['name'];
        } else {
            $name = null;
        }
        if (isset($_POST['alias']) && mb_strlen($_POST['alias'], 'UTF-8') > 0) {
            $alias = $_POST['alias'];
        } else {
            $alias = null;
        }
        $taxis = mCategory::where([
            'Cat_C_Id' => $parentId,

            'State' => 0
        ])->count();
        if (isset($_POST['taxis']) && is_numeric($_POST['taxis']) && intval($_POST['taxis']) >= 0 && intval($_POST['taxis']) <= $taxis) {
            $taxis = intval($_POST['taxis']);
        }
        if (isset($_POST['remark']) && mb_strlen($_POST['remark'], 'UTF-8') > 0) {
            $remark = $_POST['remark'];
        } else {
            $remark = null;
        }

        if (mCategory::where(['C_Alias' => $alias, 'State' => 0])->count() > 0) {
            return json([
                'result' => Code::INPUT_ERROR,
                'err' => [
                    'msg' => lang('article.category.alias.unique.limited')
                ],
                '__token__' => $request->buildToken()
            ]);
        }

        $cid = nanoid();

        Db::startTrans();

        $result = mCategory::where([
            'Cat_C_Id' => $parentId,
            'C_Taxis' => Db::raw('>= ' . $taxis),

            'State' => 0,
            'IsLocked' => false
        ])->update([
            'C_Taxis' => Db::raw('C_Taxis + 1'),

            'LastUpdateTime' => date('Y-m-d H:i:s'),
            'ExclusiveKey' => Db::raw('ExclusiveKey + 1')
        ]);

        if ($result < 0) {
            Db::rollback();

            return json([
                'result' => Code::DATABASE_ERROR,
                '__token__' => $request->buildToken()
            ]);
        }

        mCategory::create([
            'C_Id' => $cid,
            'Cat_C_Id' => $parentId,

            'C_Name' => $name,
            'C_Alias' => $alias,
            'C_Taxis' => $taxis,
            'C_Remark' => $remark,

            'State' => 0,
            'ExclusiveKey' => 0,
            'IsLocked' => 0
        ]);

        Db::commit();

        return json([
            'result' => Code::DONE
        ]);
    }

    public function updateCategory(Request $request)
    {
        // 校验Token，防止重复提交和长时间滞留页面，需要开启Session，在\app\middleware.php中取消Session初始化注释
        if ($request->checkToken('__token__') !== true) {
            return json([
                'result' => Code::TOKEN_ERROR
            ]);
        }

        if (!User::checkUserRight(Code::USER_RIGHT_CODE_CATEGORY)) {
            return json([
                'result' => Code::RIGHT_ERROR,
                '__token__' => $request->buildToken()
            ]);
        }

        if (isset($_POST['cid']) && isNanoid($_POST['cid'])) {
            $cid = $_POST['cid'];
        } else {
            return json([
                'result' => Code::REQUEST_ERROR,
                '__token__' => $request->buildToken()
            ]);
        }
        if (isset($_POST['parentId']) && isNanoid($_POST['parentId'])) {
            $parentId = $_POST['parentId'];
        } else {
            $parentId = null;
        }
        if (isset($_POST['name']) && mb_strlen($_POST['name'], 'UTF-8') > 0) {
            $name = $_POST['name'];
        } else {
            $name = null;
        }
        if (isset($_POST['alias']) && mb_strlen($_POST['alias'], 'UTF-8') > 0) {
            $alias = $_POST['alias'];
        } else {
            $alias = null;
        }
        $taxis = mCategory::where([
            'Cat_C_Id' => $parentId,

            'State' => 0
        ])->count();
        if (isset($_POST['taxis']) && is_numeric($_POST['taxis']) && intval($_POST['taxis']) >= 0 && intval($_POST['taxis']) <= $taxis) {
            $taxis = intval($_POST['taxis']);
        }
        if (isset($_POST['remark']) && mb_strlen($_POST['remark'], 'UTF-8') > 0) {
            $remark = $_POST['remark'];
        } else {
            $remark = null;
        }

        if (mCategory::where(['C_Id' => Db::raw('<> "' . $cid . '"'), 'C_Alias' => $alias, 'State' => 0])->count() > 0) {
            return json([
                'result' => Code::INPUT_ERROR,
                'err' => [
                    'msg' => lang('article.category.alias.unique.limited')
                ],
                '__token__' => $request->buildToken()
            ]);
        }

        Db::startTrans();

        $category = mCategory::where([
            'C_Id' => $cid,

            'State' => 0
        ])->findOrEmpty();

        if ($category->isEmpty()) {
            Db::rollback();

            return json([
                'result' => Code::DATABASE_ERROR,
                '__token__' => $request->buildToken()
            ]);
        }

        if ($category['Cat_C_Id'] !== $parentId || $category['C_Taxis'] !== $taxis) {
            if ($category['Cat_C_Id'] === $parentId) {
                // 如父级未变化，则对源排序后、目标排序前的数据排序前移或后移
                $min = $category['C_Taxis'] < $taxis ? $category['C_Taxis'] : $taxis;
                $max = $category['C_Taxis'] > $taxis ? $category['C_Taxis'] : $taxis;
                $result = mCategory::where([
                    'Cat_C_Id' => $parentId,

                    'State' => 0,
                    'IsLocked' => false
                ])->whereBetween('C_Taxis', [$min, $max])->update([
                    'C_Taxis' => Db::raw('C_Taxis ' . ($category['C_Taxis'] < $taxis ? '-' : '+') . ' 1'),

                    'LastUpdateTime' => date('Y-m-d H:i:s'),
                    'ExclusiveKey' => Db::raw('ExclusiveKey + 1')
                ]);

                if ($result < 0) {
                    Db::rollback();

                    return json([
                        'result' => Code::DATABASE_ERROR,
                        '__token__' => $request->buildToken()
                    ]);
                }
            } else {
                // 如父级变化，则对源分支自源排序后的数据排序前移、目标分支自目标排序后的数据排序后移
                $result = mCategory::where([
                    'Cat_C_Id' => $category['Cat_C_Id'],
                    'C_Taxis' => Db::raw('>= ' . $category['C_Taxis']),

                    'State' => 0,
                    'IsLocked' => false
                ])->update([
                    'C_Taxis' => Db::raw('C_Taxis - 1'),

                    'LastUpdateTime' => date('Y-m-d H:i:s'),
                    'ExclusiveKey' => Db::raw('ExclusiveKey + 1')
                ]);

                if ($result < 0) {
                    Db::rollback();

                    return json([
                        'result' => Code::DATABASE_ERROR,
                        '__token__' => $request->buildToken()
                    ]);
                }

                $result = mCategory::where([
                    'Cat_C_Id' => $parentId,
                    'C_Taxis' => Db::raw('>= ' . $taxis),

                    'State' => 0,
                    'IsLocked' => false
                ])->update([
                    'C_Taxis' => Db::raw('C_Taxis + 1'),

                    'LastUpdateTime' => date('Y-m-d H:i:s'),
                    'ExclusiveKey' => Db::raw('ExclusiveKey + 1')
                ]);

                if ($result < 0) {
                    Db::rollback();

                    return json([
                        'result' => Code::DATABASE_ERROR,
                        '__token__' => $request->buildToken()
                    ]);
                }
            }
        }

        $result = mCategory::where([
            'C_Id' => $cid,

            'State' => 0,
            'IsLocked' => false
        ])->update([
            'Cat_C_Id' => $parentId,

            'C_Name' => $name,
            'C_Alias' => $alias,
            'C_Taxis' => $taxis,
            'C_Remark' => $remark,

            'LastUpdateTime' => date('Y-m-d H:i:s'),
            'ExclusiveKey' => Db::raw('ExclusiveKey + 1')
        ]);

        if ($result === 1) {
            Db::commit();

            return json([
                'result' => Code::DONE
            ]);
        }

        Db::rollback();

        return json([
            'result' => Code::DATABASE_ERROR,
            '__token__' => $request->buildToken()
        ]);
    }
}
