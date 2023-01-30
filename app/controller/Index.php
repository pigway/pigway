<?php

namespace app\controller;

use app\BaseController;
use app\Request;
use Evernote\Client;
use think\facade\View;

class Index extends BaseController
{
    public function nano()
    {
        echo nanoid();
    }

    // 微信服务器配置认证，需要提前关闭调试模式
    /*public function checkSignature()
    {
        $signature = $_GET["signature"];
        $timestamp = $_GET["timestamp"];
        $nonce = $_GET["nonce"];

        $token = config('app.wx_token');
        $tmp_arr = array($token, $timestamp, $nonce);
        sort($tmp_arr, SORT_STRING);
        $tmp_str = implode($tmp_arr);
        $tmp_str = sha1($tmp_str);

        if ($tmp_str == $signature) {
            echo $_GET["echostr"];
        } else {
            return false;
        }
    }*/

    public function index(Request $request)
    {
        //$this->checkSignature();
        $where = ['mArticle.State' => 0];

        $map = [];
        if (isset($_GET['sc']) && mb_strlen($_GET['sc'], 'UTF-8') > 0) {
            $search = explode(' ', rawurldecode($_GET['sc']));
            if (is_array($search)) {
                foreach ($search as $key => $val) {
                    if (mb_strlen($val, 'UTF-8') > 0) {
                        array_push($map, ['A_Title|A_Content', 'like', '%' . $val . '%']);
                        //array_push($map, ['A_Title|A_Content', 'like', '%' . urlencode($val) . '%']);
                    }
                }
            }
            /*if (count($map) === 0) {
                array_push($map, true);
            }*/
        }

        $hasWhereCategory = [];
        if (isset($_GET['ca']) && mb_strlen($_GET['ca'], 'UTF-8') > 0) {
            $category = Article::tryLoadCategory(['C_Alias' => $_GET['ca'], 'State' => 0]);

            if (!$category->isEmpty()) {
                // 只显示本级分类
                /*$hasWhere = ['C_Id' => $category[0]['C_Id'], 'State' => 0];*/
                // 显示本级及所有子孙分类
                $categoryIdList = [$category[0]['C_Id']];
                Article::buildCategoryDescendantsIdList($category[0]['C_Id'], $categoryIdList, ['State' => 0]);
                $hasWhereCategory = [['C_Id', 'IN', $categoryIdList]];
            }
        }

        $hasWhereAuthor = [];
        $author = null;
        if (isset($_GET['auid']) && isNanoid($_GET['auid'])) {
            $hasWhereAuthor = ['AU_Id' => $_GET['auid']];

            $author = Article::tryLoadAuthor(['AU_Id' => $_GET['auid']]);
        }

        $total = Article::countArticle($where, $map, $hasWhereCategory, $hasWhereAuthor);

        if ($total < 0) {
            return getExceptionMessageReportView(Code::DATABASE_ERROR);
        }

        $articleList = Article::tryGetArticle($where, $map, $hasWhereCategory, $hasWhereAuthor, isset($_GET['p']) ? intval($_GET['p']) : 1, isset($_GET['r']) ? intval($_GET['r']) : Article::DEFAULT_ROW_NUMBER, isset($_GET['o']) ? $_GET['o'] : 'CreateTime DESC');

        $categoryTree = Article::buildCategoryTree($map, $hasWhereAuthor);

        if (is_int($categoryTree)) {
            return getExceptionMessageReportView($categoryTree);
        }

        $categoryListGroupHTML = '';
        //Article::buildCategoryListWrapCollapseGroupHTML($categoryTree, $categoryListGroupHTML);
        Article::buildCategoryListCollapseGroupHTML($categoryTree, $categoryListGroupHTML);

        View::assign([
            'total' => $total,
            'articleList' => $articleList,
            'categoryListGroupHTML' => $categoryListGroupHTML,
            'author' => $author
        ]);

        return view();
    }

    public function getArticle(Request $request)
    {
        if (isset($_POST['page']) && is_numeric($_POST['page'])) {
            $page = intval($_POST['page']);
        } else {
            $page = 1;
        }
        if (isset($_POST['row']) && is_numeric($_POST['row'])) {
            $row = intval($_POST['row']);
        } else {
            $row = config('app.default_row_number');
        }

        $map = [];
        if (isset($_POST['search']) && mb_strlen($_POST['search'], 'UTF-8') > 0) {
            $search = explode(' ', $_POST['search']);
            if (is_array($search)) {
                foreach ($search as $key => $val) {
                    if (mb_strlen($val, 'UTF-8') > 0) {
                        array_push($map, ['A_Title|A_Content', 'like', '%' . $val . '%']);
                        //array_push($map, ['A_Title|A_Content', 'like', '%' . urlencode($val) . '%']);
                    }
                }
            }
            if (count($map) === 0) {
                array_push($map, true);
            }
        }

        $hasWhere = [];
        if (isset($_POST['categoryAlias']) && mb_strlen($_POST['categoryAlias'], 'UTF-8') > 0) {
            $category = Article::tryLoadCategory(['C_Alias' => $_POST['categoryAlias'], 'State' => 0]);

            if (!$category->isEmpty()) {
                $hasWhere = ['C_Id' => $category[0]['C_Id'], 'State' => 0];
            }
        }

        if (isset($_POST['order'])) {
            $order = $_POST['order'];
        } else {
            $order = 'CreateTime DESC';
        }

        $article = Article::tryGetArticle(['mArticle.State' => 0], $map, $hasWhere, $page, $row, $order);

        return json([
            'result' => Code::DONE,
            'article' => $article
        ]);
    }

    public function notebook()
    {
        $token = config('app.evernote_token');
        $sandbox = false;
        $china = true;

        $client = new Client($token, $sandbox, null, null, $china);

        $notebooks = [];

        $notebooks = $client->listNotebooks();

        echo '<table style="border: 1px solid #000; text-align: center">';

        foreach ($notebooks as $notebook) {

            echo '<tr>';

            echo '<td style="border: 1px solid #000">' . $notebook->name . '</td>';

            echo '<td style="border: 1px solid #000">' . $notebook->guid . '</td>';

            echo $notebook->isDefaultNotebook() ? '<td style="border: 1px solid #000">Yes</td>' : '<td style="border: 1px solid #000">No</td>';
            echo $notebook->isBusinessNotebook() ? '<td style="border: 1px solid #000">Yes</td>' : '<td style="border: 1px solid #000">No</td>';
            echo $notebook->isLinkedNotebook() ? '<td style="border: 1px solid #000">Yes</td>' : '<td style="border: 1px solid #000">No</td>';

            echo '</tr>';
        }

        echo '</table>';
    }

    public function about()
    {
        return view();
    }
}
