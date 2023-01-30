<?php
// +----------------------------------------------------------------------
// | 应用设置
// +----------------------------------------------------------------------

use app\controller\Article;
use app\validate\vImage;
use app\validate\vUser;

return [
    // 应用地址
    'app_host' => env('app.host', ''),
    // 应用的命名空间
    'app_namespace' => '',
    // 是否启用路由
    'with_route' => true,
    // 默认应用
    'default_app' => 'index',
    // 默认时区
    'default_timezone' => 'Asia/Shanghai',

    // 应用映射（自动多应用模式有效）
    'app_map' => [],
    // 域名绑定（自动多应用模式有效）
    'domain_bind' => [],
    // 禁止URL访问的应用列表（自动多应用模式有效）
    'deny_app_list' => [],

    // 异常页面的模板文件
    //'exception_tmpl' => app()->getThinkPath() . 'tpl/think_exception.tpl',
    'exception_tmpl' => env('app_debug', true) ? app()->getThinkPath() . 'tpl/think_exception.tpl' : app()->getAppPath() . 'view/exception.html',

    // 错误显示信息,非调试模式有效
    'error_message' => '页面错误！请稍后再试～',
    // 显示错误信息
    'show_error_msg' => false,

    'ajax_time_out' => 6000,
    'upload_file_max_size' => 102400,

    'upload_image_max_size' => vImage::MAX_SIZE,
    'upload_image_file_ext' => vImage::FILE_EXT,
    'upload_image_file_mime' => vImage::FILE_MIME,

    'default_row_number' => Article::DEFAULT_ROW_NUMBER,
    'default_page_display_half_length' => Article::DEFAULT_PAGE_DISPLAY_HALF_LENGTH,

    'username_min_length' => vUser::USERNAME_MIN_LENGTH,
    'username_max_length' => vUser::USERNAME_MAX_LENGTH,
    'password_min_length' => vUser::PASSWORD_MIN_LENGTH,
    'password_max_length' => vUser::PASSWORD_MAX_LENGTH,

    'validate_time' => strtotime('+30 minute'),

    'email_host' => 'smtp.qq.com',
    'email_username' => 'pigway@qq.com',
    'email_password' => 'caiemnswgzphcbea',
    'email_from_address' => 'pigway@qq.com',
    'email_reset_password_code_length' => 6,
    'email_reset_password_code_type' => 1,

    'wx_token' => 'KimWanye61WxMPtokenPigWay',
    'wx_app_id' => 'wx13afa72552859c89',
    'wx_secret' => '93a10617236e6de9f5779575fa11d8b7',

    'evernote_token' => 'S=s9:U=14930b:E=17f695af64d:C=17f454e70f8:P=1cd:A=en-devtoken:V=2:H=e28df457ea64eb3ccf5a1353265ec754',

    'tian_api_url' => 'http://api.tianapi.com/lzmy/index',
    'tian_api_key' => '76f8b8a4f1a94190c04134da7d87abb4',

    'aliyun_access_key_id' => 'LTAI5tCYWkkYe1cAPn5YGpdv',
    'aliyun_access_key_secret' => 'iJTwAQN230AVKtqgNr6qsXYI59yplQ',
    'aliyun_send_sms_sign_name' => 'PigWay',
    'aliyun_sms_verification_code_resend_max_time_limit' => 5,
    'aliyun_send_sms_count_down' => 120,
    'aliyun_sms_reset_password_template_id' => 'SMS_237217309',
    'aliyun_sms_reset_password_code_length' => 6,
    'aliyun_sms_reset_password_code_type' => 1
];
