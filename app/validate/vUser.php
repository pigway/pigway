<?php
declare (strict_types=1);

namespace app\validate;

use app\controller\Code;
use think\Validate;

class vUser extends Validate
{
    const USERNAME_MIN_LENGTH = 3;
    const USERNAME_MAX_LENGTH = 100;
    const PASSWORD_MIN_LENGTH = 6;
    const PASSWORD_MAX_LENGTH = 100;

    /**
     * 定义验证规则
     * 格式：'字段名' =>  ['规则1','规则2'...]
     *
     * @var array
     */
    protected $rule = [
        'username' => 'require|length:' . self::USERNAME_MIN_LENGTH . ',' . self::USERNAME_MAX_LENGTH . '|alphaDash',
        'password' => 'require|length:' . self::PASSWORD_MIN_LENGTH . ',' . self::PASSWORD_MAX_LENGTH,
        'email_address' => 'require|email',
        'mobile_phone_number' => 'require|regex:/^1(\d{10})$/'
    ];

    /**
     * 定义错误信息
     * 格式：'字段名.规则名' =>  '错误信息'
     *
     * @var array
     */
    protected $message = [
        'username.require' => ['code' => Code::USERNAME_REQUIRE, 'msg' => 'user.username.required'],
        'username.length' => ['code' => Code::USERNAME_LENGTH, 'msg' => 'user.username.length.error'],
        'username.alphaDash' => ['code' => Code::USERNAME_FORMAT, 'msg' => 'user.username.format.error'],
        'password.require' => ['code' => Code::PASSWORD_REQUIRE, 'msg' => 'user.password.required'],
        'password.length' => ['code' => Code::PASSWORD_LENGTH, 'msg' => 'user.password.length.error'],
        'email_address.require' => ['code' => Code::EMAIL_ADDRESS_REQUIRE, 'msg' => 'user.email.address.required'],
        'email_address.email' => ['code' => Code::EMAIL_ADDRESS_FORMAT, 'msg' => 'user.email.address.format.error'],
        'mobile_phone_number.require' => ['code' => Code::MOBILE_PHONE_NUMBER_REQUIRE, 'msg' => 'user.mobile.phone.number.required'],
        'mobile_phone_number.regex' => ['code' => Code::MOBILE_PHONE_NUMBER_FORMAT, 'msg' => 'user.mobile.phone.number.format.error'],
    ];

    protected $scene = [
    ];
}
