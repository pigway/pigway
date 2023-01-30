<?php

namespace app;

use app\controller\Code;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\db\exception\PDOException;
use think\exception\Handle;
use think\exception\HttpException;
use think\exception\HttpResponseException;
use think\exception\ValidateException;
use think\Response;
use Throwable;

/**
 * 应用异常处理类
 */
class ExceptionHandle extends Handle
{
    /**
     * 不需要记录信息（日志）的异常类列表
     * @var array
     */
    protected $ignoreReport = [
        HttpException::class,
        HttpResponseException::class,
        ModelNotFoundException::class,
        DataNotFoundException::class,
        ValidateException::class,
    ];

    /**
     * 记录异常信息（包括日志或者其它方式记录）
     *
     * @access public
     * @param Throwable $exception
     * @return void
     */
    public function report(Throwable $exception): void
    {
        // 使用内置的方式记录异常日志
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @access public
     * @param \think\Request $request
     * @param Throwable $e
     * @return Response
     */
    public function render($request, Throwable $e): Response
    {
        // 添加自定义异常处理机制
        if ($e instanceof HttpException) {
            if ($request->isAjax()) {
                return json(['result' => Code::REQUEST_ERROR, 'err' => ['msg' => $e->getStatusCode() . ' ' . $e->getMessage()],]);
            }
            return getExceptionMessageReportView(['title' => $e->getStatusCode(), 'content' => $e->getMessage()]);
        }
        if ($e instanceof ModelNotFoundException || $e instanceof DataNotFoundException) {
            if ($request->isAjax()) {
                return json(['result' => Code::DATA_NONE]);
            }
            return getExceptionMessageReportView(Code::DATA_NONE);
        }
        if ($e instanceof DbException || $e instanceof PDOException) {
            if ($request->isAjax()) {
                return json(['result' => Code::DATABASE_ERROR]);
            }
            return getExceptionMessageReportView(Code::DATABASE_ERROR);
        }
        if ($e instanceof ValidateException) {
            if ($request->isAjax()) {
                return json(['result' => Code::INPUT_ERROR, 'err' => $e->getError()]);
            }
            return getExceptionMessageReportView(['title' => $e->getError()['code'], 'content' => $e->getError()['msg']]);
        }

        // 其他错误交给系统处理
        return parent::render($request, $e);
    }
}
