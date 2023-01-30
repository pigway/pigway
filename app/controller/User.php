<?php

namespace app\controller;

use app\BaseController;
use app\model\mUser;
use app\model\mUserRightR;
use app\model\mValidate;
use app\validate\vUser;
use think\db\BaseQuery;
use think\db\exception\DataNotFoundException;
use think\db\exception\DbException;
use think\db\exception\ModelNotFoundException;
use think\exception\ValidateException;
use think\facade\Db;
use think\helper\Str;
use think\Model;
use think\Request;
use think\facade\View;
use think\Response;
use think\response\Redirect;

class User extends BaseController
{
    const VALIDATE_IDENTIFICATION_CODE_LENGTH = 3;
    const VALIDATE_IDENTIFICATION_CODE_TYPE = 2;

    /***
     * @param $encrypted
     * @return mixed
     */
    private function decrypt($encrypted)
    {
        $private_key = file_get_contents('../app/rsa_1024_priv.pem');
        $pi_key = openssl_pkey_get_private($private_key);
        openssl_private_decrypt(base64_decode($encrypted), $decrypted, $pi_key);
        return $decrypted;
    }

    /***
     * @param $where
     * @return mUser|array|mixed|Model|null
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    private static function tryFindUser($where)
    {
        return mUser::where($where)->withoutField('U_Password')->find();
    }

    /***
     * @param $uid
     * @param $password_encrypted
     * @return bool
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    private function tryVerifyPassword($uid, $password_encrypted)
    {
        $password = $this->decrypt($password_encrypted);

        $user = mUser::where([
            'U_Id' => $uid
        ])->field([
            'U_Password'
        ])->find();

        if ($user !== null && isset($user['U_Password'])) {
            return password_verify($password, $user['U_Password']);
        }
        return false;
    }

    /***
     * @param $code
     * @return string|null
     */
    private function buildMethodValidateString($code)
    {
        switch ($code) {
            case Code::METHOD_EMAIL:
                return 'email_address';
            case Code::METHOD_MOBILE_PHONE:
                return 'mobile_phone_number';
            default:
                return null;
        }
    }

    /***
     * @param $uid
     * @param $scene
     * @return string
     */
    private function buildValidateIdentificationCode($uid, $scene)
    {
        $idCode = Str::random(self::VALIDATE_IDENTIFICATION_CODE_LENGTH, self::VALIDATE_IDENTIFICATION_CODE_TYPE);
        while (mValidate::where(['U_Id' => $uid, 'V_Scene' => $scene, 'V_IdentificationCode' => $idCode, 'State' => 0])->count() !== 0) { // *需要定期移除已过期数据
            $idCode = Str::random(self::VALIDATE_IDENTIFICATION_CODE_LENGTH, self::VALIDATE_IDENTIFICATION_CODE_TYPE);
        }
        return $idCode;
    }

    /***
     * @param $uid
     * @param $scene
     * @param int $code_length
     * @param int $code_type
     * @return mValidate|Model
     */
    private function createValidate($uid, $scene, $code_length = 6, $code_type = 1)
    {
        return mValidate::create([
            'V_Id' => nanoid(),
            'U_Id' => $uid,
            'V_Scene' => $scene,
            'V_IdentificationCode' => $this->buildValidateIdentificationCode($uid, $scene), // 有极低概率出现除V_Id外相同数据，除非使用存储过程
            'V_Code' => Str::random($code_length, $code_type),
            'V_InvalidTime' => date('Y-m-d H:i:s', config('app.validate_time')),
            'State' => 0,
            'ExclusiveKey' => 0,
            'IsLocked' => false
        ]);
    }

    /***
     * @param $display_number
     * @param $scene
     * @param $idCode
     * @return array|mixed|BaseQuery|Model|null
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    private function tryFindValidate($display_number, $scene, $idCode)
    {
        return mValidate::where([
            'V_Scene' => $scene,
            'V_IdentificationCode' => $idCode,
        ])->hasWhere('User', [
            'U_DisplayNumber' => $display_number
        ])->find();
    }

    /***
     * @param $display_number
     * @param $scene
     * @param $idCode
     * @return array|mixed|BaseQuery|Model|null
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    private function tryFindValidateWithUser($display_number, $scene, $idCode)
    {
        return mValidate::where([
            'V_Scene' => $scene,
            'V_IdentificationCode' => $idCode,
            'mValidate.State' => 0,
            'mValidate.IsLocked' => false
        ])->hasWhere('User', [
            'U_DisplayNumber' => $display_number
        ])->with('User')->find();
    }

    /***
     * @param $vid
     * @param $uid
     * @param $scene
     * @param $max_time
     * @param int $code_length
     * @param int $code_type
     * @return mValidate
     */
    private function recodeValidate($vid, $uid, $scene, $max_time, $code_length = 6, $code_type = 1)
    {
        $identificationCode = $this->buildValidateIdentificationCode($uid, $scene);
        $code = Str::random($code_length, $code_type);

        // 将where作为update第二参数而非::where()静态方法，返回模型实例，但无法检测是否成功，因为即使失败模型实例也非空（->isEmpty()返回false，返回实例的data中有更新字段）
        /*return mValidate::update([
            'V_IdentificationCode' => $idCode,
            'V_Code' => Str::random($code_length, $code_type),

            'LastUpdateTime' => date('Y-m-d H:i:s'),
            'ExclusiveKey' => Db::raw('ExclusiveKey + 1')
        ], [
            'V_Id' => $vid,

            'State' => 0,
            'IsLocked' => false,
            'ExclusiveKey' => Db::raw(' < ' . $max_time)
        ]);*/

        $result = mValidate::where([
            'V_Id' => $vid,

            'State' => 0,
            'IsLocked' => false,
            'ExclusiveKey' => Db::raw(' < ' . $max_time)
        ])->update([
            'V_IdentificationCode' => $identificationCode, // 有极低概率出现除V_Id外相同数据，除非使用存储过程
            'V_Code' => $code,

            'LastUpdateTime' => date('Y-m-d H:i:s'),
            'ExclusiveKey' => Db::raw('ExclusiveKey + 1')
        ]);

        if ($result === 1) {
            return ['V_IdentificationCode' => $identificationCode, 'V_Code' => $code];
        }

        return Code::DATABASE_ERROR;
    }

    public static function clearSignSession()
    {
        session('uid', null);
        session('display_number', null);
        session('display_name', null);
    }

    public static function forceSignIn($url)
    {
        static::clearSignSession();
        cookie('urlNow', $url);
        return redirect('/User/signIn');
    }

    /***
     * @return bool
     */
    public static function checkUserSignInStateSoft()
    {
        if (session('?uid')) {
            return true;
        } else {
            return false;
        }
    }

    /***
     * @return bool
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public static function checkUserSignInStateHard()
    {
        if (session('?uid') && isNanoid(session('uid'))) {
            $user = static::tryFindUser(['U_Id' => session('uid')]);
            if ($user !== null && $user['State'] === 0) {
                return true;
            }
        }
        return false;
    }

    /***
     * @param $code
     * @return bool
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public static function checkUserRight($code)
    {
        if (static::checkUserSignInStateHard()) {
            $count = mUserRightR::where([
                'U_Id' => session('uid'),
                'mUserRightR.State' => 0
            ])->hasWhere('Right', ['R_Code' => $code, 'State' => 0], '*', 'LEFT')->count();

            if ($count === 1) {
                return true;
            }
        }
        return false;
    }

    /***
     * @param $url
     * @return Redirect|\think\response\View
     */
    public static function getVerifiedUserSignInStateViewSoft($url)
    {
        if (!session('?uid')) {
            return static::forceSignIn($url);
        }
        return view();
    }

    /***
     * 关键操作前验证用户登录状态
     * @param $url
     * @return Redirect|\think\response\View
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public static function getVerifiedUserSignInStateViewHard($url)
    {
        if (session('?uid') && isNanoid(session('uid'))) {
            $user = static::tryFindUser(['U_Id' => session('uid')]);

            if ($user !== null && $user['State'] === 0) {
                return view();
            }

            static::clearSignSession();
            cookie('urlNow', $url);

            if ($user === null) {
                return view('/user/logout', [
                    'title' => lang('user.none'),
                    'content' => lang('user.logout.force.help')
                ]);
            }
            if (is_object($user) && $user['State'] === 1) {
                return view('/user/logout', [
                    'title' => lang('user.removed'),
                    'content' => lang('user.logout.force.help')
                ]);
            }
        }
        return static::forceSignIn($url);
    }

    /***
     * @param $code
     * @param $url
     * @return Redirect|\think\response\View
     * @throws DataNotFoundException
     * @throws DbException
     * @throws ModelNotFoundException
     */
    public static function getVerifiedUserRightView($code, $url)
    {
        if (static::checkUserRight($code)) {
            return static::getVerifiedUserSignInStateViewHard($url);
        }
        return getExceptionMessageReportView(Code::RIGHT_ERROR);
    }

    /***
     * @return Response
     */
    public function captcha()
    {
        return captcha('user');
    }

    public function signIn()
    {
        $data = postURL(config('app.tian_api_url'), ['key' => config('app.tian_api_key')]);
        $json = json_decode($data, true);
        if ($json['code'] == 200) {
            View::assign('welcome', '<span data-bs-toggle="tooltip" data-bs-placement="top" title="' . $json['newslist'][0]['transl'] . '">' . $json['newslist'][0]['saying'] . '</span>' . '<small class="fst-italic ms-1">-' . $json['newslist'][0]['source'] . '</small>');
        } else {
            View::assign('welcome', lang('app.welcome'));
        }
        return view();
    }

    public function verifyUser(Request $request)
    {
        // 校验除密码外的输入，即仅用户名
        validate(vUser::class)->only(['username'])->check($request->param());

        // 使用私钥对密码进行解密并校验输入 *有暴露风险
        $password = $this->decrypt($_POST['password']);

        validate(vUser::class)->only(['password'])->check(['password' => $password]);

        // 校验Token，防止重复提交和长时间滞留页面，需要开启Session，在\app\middleware.php中取消Session初始化注释
        if ($request->checkToken('__token__') !== true) {
            return json([
                'result' => Code::TOKEN_ERROR
            ]);
        }

        // 验证用户名是否存在
        $user = static::tryFindUser(['U_Username' => $_POST['username']]);
        if ($user === null) {
            return json([
                'result' => Code::DATA_NONE,
                'err' => [
                    'msg' => lang('user.username.non-exist')
                ],
                '__token__' => $request->buildToken()
            ]);
        }
        if ($user['State'] === 1) {
            return json([
                'result' => Code::DATA_REMOVED,
                'err' => [
                    'msg' => lang('user.removed')
                ],
                '__token__' => $request->buildToken()
            ]);
        }

        // 验证密码
        $result = $this->tryVerifyPassword($user['U_Id'], $_POST['password']);
        if (!$result) {
            return json([
                'result' => Code::FAILED,
                'err' => [
                    'code' => Code::PASSWORD_MISMATCH,
                    'msg' => lang('user.password.mismatch')
                ],
                '__token__' => $request->buildToken()
            ]);
        }

        // 验证成功
        session('uid', $user['U_Id']);
        session('display_number', $user['U_DisplayNumber']);
        session('display_name', urldecode($user['U_DisplayName']));
        return json([
            'result' => Code::DONE
        ]);
    }

    public function resetApply()
    {
        return view();
    }

    public function verifyUsername(Request $request)
    {
        // 校验用户名
        validate(vUser::class)->only(['username'])->check($request->param());

        // 校验Token，防止重复提交和长时间滞留页面，需要开启Session，在\app\middleware.php中取消Session初始化注释
        if ($request->checkToken('__token__') !== true) {
            return json([
                'result' => Code::TOKEN_ERROR
            ]);
        }

        // 验证用户名是否存在
        $user = static::tryFindUser(['U_Username' => $_POST['username']]);
        if ($user === null) {
            return json([
                'result' => Code::DATA_NONE,
                'err' => [
                    'msg' => lang('user.username.non-exist')
                ],
                '__token__' => $request->buildToken()
            ]);
        }
        if ($user['State'] === 1) {
            return json([
                'result' => Code::DATA_REMOVED,
                'err' => [
                    'msg' => lang('user.removed')
                ],
                '__token__' => $request->buildToken()
            ]);
        }

        session('reset_username', $user['U_Username']);
        return json([
            'result' => Code::DONE
        ]);
    }

    public function resetVerify()
    {
        // 校验用户名
        if (!session('?reset_username')) {
            return getExceptionMessageReportView(Code::REQUEST_ERROR);
        }

        $user = static::tryFindUser(['U_Username' => session('reset_username')]);
        if ($user === null) {
            return getExceptionMessageReportView([
                'title' => getExceptionTitle(Code::DATA_NONE),
                'message' => lang('user.username.non-exist')
            ]);
        }
        if ($user['State'] === 1) {
            return getExceptionMessageReportView([
                'title' => getExceptionTitle(Code::DATA_REMOVED),
                'message' => lang('user.removed')
            ]);
        }

        // 指派验证方式
        $verify_method = [];
        if ($user['U_EmailAddress'] !== null) {
            array_push($verify_method, [
                'code' => Code::METHOD_EMAIL,
                'hidden' => hideWithStar($user['U_EmailAddress'], Code::METHOD_EMAIL),
                'available' => true
            ]);
        }
        if ($user['U_MobilePhoneNumber'] !== null) {
            array_push($verify_method, [
                'code' => Code::METHOD_MOBILE_PHONE,
                'hidden' => hideWithStar($user['U_MobilePhoneNumber'], Code::METHOD_MOBILE_PHONE),
                'available' => true
            ]);
        }
        if (count($verify_method) == 0) {
            array_push($verify_method, [
                'code' => Code::DATA_NONE,
                'hidden' => lang('user.verify.method.none'),
                'available' => false
            ]);
        }
        View::assign('verifyMethod', $verify_method);
        return view();
    }

    public function verifyResetMethod(Request $request)
    {
        // 校验用户名
        if (!session('?reset_username')) {
            return json([
                'result' => Code::SYSTEM_ERROR
            ]);
        }

        // 校验验证码
        if (!session('?captcha')) {
            return json([
                'result' => Code::REQUEST_ERROR,
                'err' => [
                    'msg' => lang('captcha.expired.refresh.help')
                ]
            ]);
        }
        if (!isset($_POST['captcha'])) {
            return json([
                'result' => Code::INPUT_ERROR,
                'err' => [
                    'code' => Code::CAPTCHA_REQUIRE,
                    'msg' => lang('captcha.required')
                ]
            ]);
        }
        if (mb_strlen($_POST['captcha'], 'UTF-8') != config('captcha.user.length')) {
            return json([
                'result' => Code::INPUT_ERROR,
                'err' => [
                    'code' => Code::CAPTCHA_LENGTH,
                    'msg' => lang('captcha.length.error', ['length' => config('captcha.user.length')])
                ]
            ]);
        }

        // 校验验证方式
        if (!isset($_POST['method'])) {
            return json([
                'result' => Code::INPUT_ERROR,
                'err' => [
                    'code' => Code::METHOD_REQUIRE,
                    'msg' => lang('user.verify.method.required')
                ]
            ]);
        }
        if (!in_array($_POST['method'], [Code::METHOD_EMAIL, Code::METHOD_MOBILE_PHONE])) {
            return json([
                'result' => Code::INPUT_ERROR,
                'err' => [
                    'code' => Code::METHOD_MISMATCH,
                    'msg' => lang('user.verify.method.mismatch')
                ]
            ]);
        }
        $methodString = $this->buildMethodValidateString($_POST['method']);

        // 校验验证信息
        validate(vUser::class)->only([$methodString])->check([$methodString => $_POST['info']]);

        // 校验Token，防止重复提交和长时间滞留页面，需要开启Session，在\app\middleware.php中取消Session初始化注释
        if ($request->checkToken('__token__') !== true) {
            return json([
                'result' => Code::TOKEN_ERROR
            ]);
        }

        // 验证图形验证码
        if (!captcha_check($_POST['captcha'])) {
            return json([
                'result' => Code::FAILED,
                'err' => [
                    'code' => Code::CAPTCHA_MISMATCH,
                    'msg' => lang('captcha.mismatch')
                ],
                '__token__' => $request->buildToken()
            ]);
        }

        // 验证用户验证方式并反馈
        $user = static::tryFindUser(['U_Username' => session('reset_username')]);
        if ($user === null) {
            return json([
                'result' => Code::DATA_NONE,
                'err' => [
                    'msg' => lang('user.username.non-exist')
                ],
                '__token__' => $request->buildToken()
            ]);
        }
        if ($user['State'] === 1) {
            return json([
                'result' => Code::DATA_REMOVED,
                'err' => [
                    'msg' => lang('user.removed')
                ],
                '__token__' => $request->buildToken()
            ]);
        }

        if ($_POST['method'] == Code::METHOD_EMAIL) {
            if ($_POST['info'] !== $user['U_EmailAddress']) {
                return json([
                    'result' => Code::FAILED,
                    'err' => [
                        'code' => Code::EMAIL_ADDRESS_MISMATCH,
                        'msg' => lang('user.email.address.mismatch')
                    ],
                    '__token__' => $request->buildToken()
                ]);
            }

            Db::startTrans();

            // 获取验证参数
            $validate = $this->createValidate($user['U_Id'], Code::SCENE_RESET_PASSWORD_BY_EMAIL, config('app.email_reset_password_code_length'), config('app.email_reset_password_code_type')); // method: Reset 2(password) by 7(email)

            // 链接参数中s为ThinkPHP保留参数，勿用
            $url = 'http://' . config('app.app_host') . '/User/reset?u=' . $user['U_DisplayNumber'] . '&sc=' . $validate['V_Scene'] . '&i=' . $validate['V_IdentificationCode'] . '&c=' . $validate['V_Code'];
            $bodyHtml = '<div><a href="' . $url . '">' . lang('user.password.reset.now.click') . '</a></div>';
            $bodyHtml .= '<br/>';
            $bodyHtml .= '<div><span>' . lang('user.password.reset.now.click.link.help') . '</span></div>';
            $bodyHtml .= '<div><a href="' . $url . '">' . $url . '</a></div>';
            $bodyHtml .= '<hr style="margin: 5px 0" />';
            $bodyHtml .= '<div style="color: gray">' . lang('user.password.reset.email.help') . '</div>';
            if (sendEmail($user['U_EmailAddress'], isset($user['U_DisplayName']) ? $user['U_DisplayName'] : $user['U_EmailAddress'], lang('user.password.reset'), $bodyHtml)) {
                Db::commit();

                session('reset_username', null);

                return json([
                    'result' => Code::DONE,
                    'method' => Code::METHOD_EMAIL
                ]);
            } else {
                Db::rollback();

                return json([
                    'result' => Code::EMAIL_SERVICE_ERROR,
                    '__token__' => $request->buildToken()
                ]);
            }
        }

        if ($_POST['method'] == Code::METHOD_MOBILE_PHONE) {
            if ($_POST['info'] !== $user['U_MobilePhoneNumber']) {
                return json([
                    'result' => Code::FAILED,
                    'err' => [
                        'code' => Code::MOBILE_PHONE_NUMBER_MISMATCH,
                        'msg' => lang('user.mobile.phone.number.mismatch')
                    ],
                    '__token__' => $request->buildToken()
                ]);
            }

            Db::startTrans();

            // 获取验证参数
            $validate = $this->createValidate($user['U_Id'], Code::SCENE_RESET_PASSWORD_BY_MOBILE_PHONE, config('app.aliyun_sms_reset_password_code_length'), config('app.aliyun_sms_reset_password_code_type')); // method: Reset 2(password) by 8(mobile phone)

            $result = sendMobilePhoneTextMessage(config('app.aliyun_sms_reset_password_template_id'), $user['U_MobilePhoneNumber'], '{"code":"' . $validate['V_IdentificationCode'] . $validate['V_Code'] . '"}');

            if ($result->body->code === 'OK') {
                Db::commit();

                session('reset_username', null);

                return json([
                    'result' => Code::DONE,
                    'method' => Code::METHOD_MOBILE_PHONE,
                    'user' => $user['U_DisplayNumber'],
                    'scene' => $validate['V_Scene'],
                    'idCode' => $validate['V_IdentificationCode']
                ]);
            } else {
                Db::rollback();

                return json([
                    'result' => Code::MOBILE_PHONE_SERVICE_ERROR,
                    'err' => [
                        'msg' => $result->body->message
                    ],
                    '__token__' => $request->buildToken()
                ]);
            }
        }

        return json([
            'result' => Code::SYSTEM_ERROR,
            '__token__' => $request->buildToken()
        ]);
    }

    public function resetEmailSent()
    {
        return view();
    }

    public function resetMobilePhoneVerify()
    {
        return view();
    }

    public function resendResetMobilePhoneVerificationCode(Request $request)
    {
        if (!isset($_POST['user']) || !is_numeric($_POST['user']) || !isset($_POST['scene']) || !is_numeric($_POST['scene']) || !isset($_POST['idCode']) || !is_string($_POST['idCode'])) {
            return json([
                'result' => Code::SYSTEM_ERROR
            ]);
        }

        // 校验Token，防止重复提交和长时间滞留页面，需要开启Session，在\app\middleware.php中取消Session初始化注释
        if ($request->checkToken('__token__') !== true) {
            return json([
                'result' => Code::TOKEN_ERROR
            ]);
        }

        $validate = $this->tryFindValidateWithUser($_POST['user'], $_POST['scene'], $_POST['idCode']);
        if ($validate === null) {
            return json([
                'result' => Code::DATA_NONE,
                '__token__' => $request->buildToken()
            ]);
        }
        if ($validate['State'] === 1) {
            return json([
                'result' => Code::DATA_REMOVED,
                '__token__' => $request->buildToken()
            ]);
        }
        if ($validate['ExclusiveKey'] >= config('app.aliyun_sms_verification_code_resend_max_time_limit')) {
            return json([
                'result' => Code::REQUEST_ERROR,
                'err' => [
                    'msg' => lang('sms.resend.time.max.limit', ['length' => config('app.aliyun_sms_verification_code_resend_max_time_limit')])
                ],
                '__token__' => $request->buildToken()
            ]);
        }

        Db::startTrans();

        $newValidate = $this->recodeValidate($validate['V_Id'], $validate['U_Id'], $validate['V_Scene'], config('app.aliyun_sms_verification_code_resend_max_time_limit'), config('app.aliyun_sms_reset_password_code_length'), config('app.aliyun_sms_reset_password_code_type'));

        if (is_int($newValidate)) {
            return json([
                'result' => $newValidate,
                '__token__' => $request->buildToken()
            ]);
        }

        $result = sendMobilePhoneTextMessage(config('app.aliyun_sms_reset_password_template_id'), $validate['User']['U_MobilePhoneNumber'], '{"code":"' . $newValidate['V_IdentificationCode'] . $newValidate['V_Code'] . '"}');

        if ($result->body->code === 'OK') {
            Db::commit();

            return json([
                'result' => Code::DONE,
                'idCode' => $newValidate['V_IdentificationCode'],
                '__token__' => $request->buildToken()
            ]);
        } else {
            Db::rollback();

            return json([
                'result' => Code::MOBILE_PHONE_SERVICE_ERROR,
                'err' => [
                    'msg' => $result->body->message
                ],
                '__token__' => $request->buildToken()
            ]);
        }
    }

    public function verifyResetMobilePhoneVerificationCode(Request $request)
    {
        if (!isset($_POST['user']) || !is_numeric($_POST['user']) || !isset($_POST['scene']) || !is_numeric($_POST['scene']) || !isset($_POST['idCode']) || !is_string($_POST['idCode'])) {
            return json([
                'result' => Code::SYSTEM_ERROR
            ]);
        }

        // 校验短信验证码
        if (!isset($_POST['code'])) {
            return json([
                'result' => Code::INPUT_ERROR,
                'err' => [
                    'code' => Code::SMS_VERIFICATION_CODE_REQUIRE,
                    'msg' => lang('sms.verification.code.required')
                ]
            ]);
        }
        if (mb_strlen($_POST['code'], 'UTF-8') != config('app.aliyun_sms_reset_password_code_length')) {
            return json([
                'result' => Code::INPUT_ERROR,
                'err' => [
                    'code' => Code::SMS_VERIFICATION_CODE_LENGTH,
                    'msg' => lang('sms.verification.code.length.error', ['length' => config('app.aliyun_sms_reset_password_code_length')])
                ]
            ]);
        }
        if (!is_numeric($_POST['code'])) {
            return json([
                'result' => Code::INPUT_ERROR,
                'err' => [
                    'code' => Code::SMS_VERIFICATION_CODE_FORMAT,
                    'msg' => lang('sms.verification.code.format.error')
                ]
            ]);
        }

        // 校验Token，防止重复提交和长时间滞留页面，需要开启Session，在\app\middleware.php中取消Session初始化注释
        if ($request->checkToken('__token__') !== true) {
            return json([
                'result' => Code::TOKEN_ERROR
            ]);
        }

        // 验证短信验证码
        $validate = $this->tryFindValidateWithUser($_POST['user'], $_POST['scene'], $_POST['idCode']);
        if ($validate === null) {
            return json([
                'result' => Code::DATA_NONE,
                '__token__' => $request->buildToken()
            ]);
        }
        if ($validate['State'] === 1) {
            return json([
                'result' => Code::DATA_REMOVED,
                '__token__' => $request->buildToken()
            ]);
        }
        if (strtotime($validate['V_InvalidTime']) < time()) {
            return json([
                'result' => Code::REQUEST_ERROR,
                'err' => [
                    'msg' => lang('sms.verification.code.expired')
                ],
                '__token__' => $request->buildToken()
            ]);
        }

        if ($validate['V_Code'] === $_POST['code']) {
            return json([
                'result' => Code::DONE
            ]);
        }

        return json([
            'result' => Code::FAILED,
            'err' => [
                'code' => Code::SMS_VERIFICATION_CODE_MISMATCH,
                'msg' => lang('sms.verification.code.mismatch')
            ],
            '__token__' => $request->buildToken()
        ]);
    }

    public function reset()
    {
        if (!isset($_GET['u']) || !is_numeric($_GET['u']) || !isset($_GET['sc']) || !is_numeric($_GET['sc']) || !isset($_GET['i']) || !is_string($_GET['i']) || !isset($_GET['c']) || !is_numeric($_GET['c'])) {
            return view('/message/report', [
                'title' => getExceptionTitle(Code::SYSTEM_ERROR),
                'content' => lang('user.password.reset.link.invalid')
            ]);
        }

        $validate = $this->tryFindValidate($_GET['u'], $_GET['sc'], $_GET['i'], $_GET['c']);
        if ($validate === null) {
            return getExceptionMessageReportView(['title' => getExceptionTitle(Code::DATA_NONE), 'content' => lang('user.password.reset.link.invalid')]);
        }
        if ($validate['State'] === 1) {
            return getExceptionMessageReportView(['title' => getExceptionTitle(Code::DATA_REMOVED), 'content' => lang('user.password.reset.link.invalid')]);
        }
        if (strtotime($validate['V_InvalidTime']) < time()) {
            return getExceptionMessageReportView(['title' => getExceptionTitle(Code::REQUEST_ERROR), 'content' => lang('user.password.reset.link.expired')]);
        }
        if ($validate['V_Code'] !== $_GET['c']) {
            return getExceptionMessageReportView(Code::REQUEST_ERROR);
        }

        return view();
    }

    public function resetPassword(Request $request)
    {
        if (!isset($_POST['user']) || !is_numeric($_POST['user']) || !isset($_POST['scene']) || !is_numeric($_POST['scene']) || !isset($_POST['idCode']) || !is_string($_POST['idCode']) || !isset($_POST['code']) || !is_numeric($_POST['code'])) {
            return json([
                'result' => Code::SYSTEM_ERROR
            ]);
        }

        // 使用私钥对密码进行解密并检测 *有暴露风险
        $password = $this->decrypt($_POST['password']);

        validate(vUser::class)->only(['password'])->check(['password' => $password]);

        // 校验Token，防止重复提交和长时间滞留页面，需要开启Session，在\app\middleware.php中取消Session初始化注释
        if ($request->checkToken('__token__') !== true) {
            return json([
                'result' => Code::TOKEN_ERROR
            ]);
        }

        // 验证验证信息
        $validate = $this->tryFindValidate($_POST['user'], $_POST['scene'], $_POST['idCode'], $_POST['code']);
        if ($validate === null) {
            return json([
                'result' => Code::DATA_NONE,
                'err' => [
                    'msg' => lang('user.password.reset.link.invalid')
                ],
                '__token__' => $request->buildToken()
            ]);
        }
        if ($validate['State'] === 1) {
            return json([
                'result' => Code::DATA_REMOVED,
                'err' => [
                    'msg' => lang('user.password.reset.link.invalid')
                ],
                '__token__' => $request->buildToken()
            ]);
        }
        if (strtotime($validate['V_InvalidTime']) < time()) {
            return json([
                'result' => Code::REQUEST_ERROR,
                'err' => [
                    'msg' => lang('user.password.reset.link.expire')
                ],
                '__token__' => $request->buildToken()
            ]);
        }

        Db::startTrans();

        $result = mValidate::where([
            'V_Id' => $validate['V_Id'],

            'State' => 0,
            'IsLocked' => false
        ])->update([
            'State' => 1,

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

        $result = mUser::where([
            'U_Id' => $validate['U_Id'],

            'State' => 0,
            'IsLocked' => false
        ])->update([
            'U_Password' => password_hash($password, PASSWORD_BCRYPT),

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

    public function resetSuccess()
    {
        return view();
    }

    public function center(Request $request)
    {
        if (self::checkUserSignInStateSoft()) {
            $user = static::tryFindUser(['U_Id' => session('uid')]);
            if ($user !== null && $user['State'] === 0) {
                View::assign('displayName', urldecode($user['U_DisplayName']));
            }
        }
        return static::getVerifiedUserSignInStateViewSoft($request->url());
    }

    public function logout()
    {
        static::clearSignSession();
        View::assign([
            'title' => lang('user.logout.done'),
            'content' => lang('user.return.look.forward')
        ]);
        return view();
    }

    public function setDisplayName(Request $request)
    {
        if (!static::checkUserSignInStateSoft()) {
            return json([
                'result' => Code::SESSION_ERROR
            ]);
        }

        // 校验Token，防止重复提交和长时间滞留页面，需要开启Session，在\app\middleware.php中取消Session初始化注释
        if ($request->checkToken('__token__') !== true) {
            return json([
                'result' => Code::TOKEN_ERROR
            ]);
        }

        $result = mUser::where([
            'U_Id' => session('uid'),

            'State' => 0,
            'IsLocked' => false
        ])->update([
            'U_DisplayName' => urlencode($_POST['displayName']),

            'LastUpdateTime' => date('Y-m-d H:i:s'),
            'ExclusiveKey' => Db::raw('ExclusiveKey + 1')
        ]);

        if ($result === 1) {
            session('display_name', $_POST['displayName']);

            return json([
                'result' => Code::DONE,
                '__token__' => $request->buildToken()
            ]);
        }

        return json([
            'result' => Code::DATABASE_ERROR,
            '__token__' => $request->buildToken()
        ]);
    }

    public function setPassword(Request $request)
    {
        if (!static::checkUserSignInStateHard()) {
            return json([
                'result' => Code::SESSION_ERROR
            ]);
        }

        // 使用私钥对密码进行解密并检测 *有暴露风险
        $old_password = $this->decrypt($_POST['oldPassword']);

        try {
            validate(vUser::class)
                ->only(['password'])->check(['password' => $old_password]);
        } catch (ValidateException $e) {
            return json([
                'result' => Code::INPUT_ERROR,
                'err' => $e->getError(),
                'isOld' => true
            ]);
        }

        $password = $this->decrypt($_POST['password']);

        try {
            validate(vUser::class)
                ->only(['password'])->check(['password' => $password]);
        } catch (ValidateException $e) {
            return json([
                'result' => Code::INPUT_ERROR,
                'err' => $e->getError(),
                'isOld' => false
            ]);
        }

        // 校验Token，防止重复提交和长时间滞留页面，需要开启Session，在\app\middleware.php中取消Session初始化注释
        if ($request->checkToken('__token__') !== true) {
            return json([
                'result' => Code::TOKEN_ERROR
            ]);
        }

        $result = mUser::where([
            'U_Id' => session('uid'),

            'State' => 0,
            'IsLocked' => false
        ])->update([
            'U_Password' => password_hash($password, PASSWORD_BCRYPT),

            'LastUpdateTime' => date('Y-m-d H:i:s'),
            'ExclusiveKey' => Db::raw('ExclusiveKey + 1')
        ]);

        if ($result === 1) {
            return json([
                'result' => Code::DONE,
                '__token__' => $request->buildToken()
            ]);
        }

        return json([
            'result' => Code::DATABASE_ERROR,
            '__token__' => $request->buildToken()
        ]);
    }
}
