<?php
// 应用公共文件

function nanoid()
{
    $client = new Hidehalo\Nanoid\Client();
    return $client->generateId();
}

function isNanoid($id)
{
    if (isset($id) && mb_strlen($id, 'UTF-8') === 21) {
        return true;
    }
    return false;
}

function postURL($url, $data)
{
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_POST, 1);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
    $output = curl_exec($curl);
    curl_close($curl);
    return $output;
}

/**
 * @param string $str
 * @param int $type 0: normal | 1: email | 2: mobile phone
 * @return string
 */
function hideWithStar(string $str, int $type = 0)
{
    if (!empty($str)) {
        switch ($type) {
            case 1:
                {
                    $arr = explode('@', $str);
                    if (sizeof($arr) === 2) {
                        $rest_start = mb_substr($arr[0], 0, 1, 'UTF-8');
                        if (mb_strlen($arr[0], 'UTF-8') === 1) {
                            $rest_start = '*';
                            $middle = '';
                            $rest_end = '';
                        } else if (mb_strlen($arr[0], 'UTF-8') === 2) {
                            $middle = '';
                            $rest_end = '*';
                        } else {
                            $middle = str_repeat('*', mb_strlen($arr[0]) - 2);
                            $rest_end = mb_substr($arr[0], -1, 1, 'UTF-8');
                        }
                        $result = $rest_start . $middle . $rest_end . '@' . $arr[1];
                    } else {
                        return false;
                    }
                }
                break;
            case 2:
                {
                    $pattern = '/^(\d{3})(\d{4})(\d{4})$/';
                    if (preg_match($pattern, $str)) {
                        $replacement = '$1****$3';
                        $result = preg_replace($pattern, $replacement, $str);
                    } else {
                        return false;
                    }
                }
                break;
            default:
                {
                    $rest_start = mb_substr($str, 0, 1, 'UTF-8');
                    if (mb_strlen($str, 'UTF-8') == 1) {
                        $rest_start = '*';
                        $middle = '';
                        $rest_end = '';
                    } else if (mb_strlen($str, 'UTF-8') == 2) {
                        $middle = '';
                        $rest_end = '*';
                    } else {
                        $middle = str_repeat('*', mb_strlen($str, 'UTF-8') - 2);
                        $rest_end = mb_substr($str, -1, 1, 'UTF-8');
                    }
                    $result = $rest_start . $middle . $rest_end;
                }
                break;
        }
    } else {
        return false;
    }

    return $result;
}

/**
 * @param string $address
 * @param string $name
 * @param string $subject
 * @param string $bodyHtml
 * @param string|null $altBody
 * @return bool
 */
function sendEmail(string $address, string $name, string $subject, string $bodyHtml, string $altBody = null)
{
    //Create an instance; passing `true` enables exceptions
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    try {
        // Server settings
        //$mail->SMTPDebug = PHPMailer\PHPMailer\SMTP::DEBUG_SERVER; // Enable verbose debug output
        $mail->isSMTP(); // Send using SMTP
        $mail->Host = config('app.email_host'); // Set the SMTP server to send through
        $mail->SMTPAuth = true; // Enable SMTP authentication
        $mail->Username = config('app.email_username'); // SMTP username
        $mail->Password = config('app.email_password'); // SMTP password
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS; // Enable implicit TLS encryption
        $mail->Port = 465; // TCP port to connect to; use 587 if you have set `SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS`

        // Recipients
        $mail->setFrom(config('app.email_from_address'), lang('app.author'));
        $mail->addAddress($address, $name); // Add a recipient

        // Content
        $mail->isHTML(true); // Set email format to HTML
        $mail->Subject = $subject;
        $mail->Body = $bodyHtml;
        $mail->AltBody = $altBody;

        return $mail->send();
    } catch (PHPMailer\PHPMailer\Exception $e) {
        return false;
    }
}

/***
 * @param string $template_code
 * @param string $mobile_phone_number
 * @param string $template_param
 * @return AlibabaCloud\SDK\Dysmsapi\V20170525\Models\SendSmsResponse
 */
function sendMobilePhoneTextMessage(string $template_code, string $mobile_phone_number, string $template_param)
{
    $config = new Darabonba\OpenApi\Models\Config([
        'accessKeyId' => config('app.aliyun_access_key_id'),
        'accessKeySecret' => config('app.aliyun_access_key_secret')
    ]);
    $config->endpoint = 'dysmsapi.aliyuncs.com';
    $client = new AlibabaCloud\SDK\Dysmsapi\V20170525\Dysmsapi($config);
    $sendSmsRequest = new AlibabaCloud\SDK\Dysmsapi\V20170525\Models\SendSmsRequest([
        'signName' => config('app.aliyun_send_sms_sign_name'),
        'templateCode' => $template_code,
        'phoneNumbers' => $mobile_phone_number,
        'templateParam' => $template_param
    ]);
    return $client->sendSms($sendSmsRequest);
}

/***
 * @param $code
 * @return mixed
 */
function getExceptionTitle($code)
{
    switch ($code) {
        case app\controller\Code::TOKEN_ERROR:
            return lang('warning.token');
        case app\controller\Code::REQUEST_ERROR:
            return lang('warning.request');
        case app\controller\Code::SESSION_ERROR:
            return lang('warning.session');
        case app\controller\Code::DATA_REMOVED:
            return lang('warning.data.removed');
        case app\controller\Code::DATA_NONE:
            return lang('warning.data.none');
        case app\controller\Code::DATABASE_ERROR:
            return lang('error.database');
        case app\controller\Code::SYSTEM_ERROR:
            return lang('error.system');
        case app\controller\Code::RIGHT_ERROR:
            return lang('error.right');
        case app\controller\Code::EMAIL_SERVICE_ERROR:
            return lang('error.email.service');
        case app\controller\Code::MOBILE_PHONE_SERVICE_ERROR:
            return lang('error.mobile.phone.service');
        default:
            return lang('error.unknown');
    }
}

/***
 * @param $code
 * @return mixed
 */
function getExceptionContent($code)
{
    switch ($code) {
        case app\controller\Code::TOKEN_ERROR:
            return lang('warning.token.help');
        case app\controller\Code::REQUEST_ERROR:
            return lang('warning.request.help');
        case app\controller\Code::SESSION_ERROR:
            return lang('warning.session.help');
        case app\controller\Code::DATA_REMOVED:
            return lang('warning.data.removed.help');
        case app\controller\Code::DATA_NONE:
            return lang('warning.data.none.help');
        case app\controller\Code::DATABASE_ERROR:
            return lang('error.database.help');
        case app\controller\Code::SYSTEM_ERROR:
            return lang('error.system.help');
        case app\controller\Code::RIGHT_ERROR:
            return lang('error.right.help');
        case app\controller\Code::EMAIL_SERVICE_ERROR:
            return lang('error.email.service.help');
        case app\controller\Code::MOBILE_PHONE_SERVICE_ERROR:
            return lang('error.mobile.phone.service.help');
        default:
            return lang('error.unknown.help');
    }
}

/***
 * @param mixed $args int - 错误码
 *                    Array - title&content
 * @return think\response\View
 */
function getExceptionMessageReportView(mixed $args)
{
    if (is_int($args)) {
        return view('/message/report', [
            'title' => getExceptionTitle($args),
            'content' => getExceptionContent($args)
        ]);
    }
    return view('/message/report', [
        'title' => $args['title'],
        'content' => $args['content']
    ]);
}

/***
 * Pretty print a size from bytes
 * @param float $size The number to pretty print
 * @param bool $noSpace [noSpace=false] Don't print a space
 * @param bool $lang [lang=false] Print local language unit
 * @param bool $one [one=false] Unit only print one character when lang is false
 * @param int $places [places=1] Number of decimal places to return
 * @param bool $numOnly [numOnly=false] Return only the converted number and not size string
 * @return float|string
 */
function prettySize(float $size, bool $noSpace = false, bool $lang = false, bool $one = false, int $places = 1, bool $numOnly = false)
{
    $sizeUnit = [
        'Byte', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'
    ];
    $sizeUnitLang = array(
        lang('unit.byte'), lang('unit.KB'), lang('unit.MB'), lang('unit.GB'), lang('unit.TB'), lang('unit.PB'), lang('unit.EB'), lang('unit.ZB'), lang('unit.YB')
    );

    for ($id = 0; $id < count($sizeUnit); ++$id) {
        $unit = $sizeUnit[$id];
        $unitLang = $sizeUnitLang[$id];

        if ($one) {
            $unit = substr($unit, 0, 1);
        }

        $s = pow(1024, $id);
        if ($size >= $s) {
            $fixed = (string)round(($size / $s), $places);
            if (strpos($fixed, '.0') === strlen($fixed) - 2) {
                $fixed = substr($fixed, 0, -2);
            }
            $pretty = $fixed . ($noSpace ? '' : ' ') . ($lang ? $unitLang : $unit);
        }
    }

    if (!isset($pretty)) {
        $_unit = $lang ? $sizeUnitLang[0] : ($one ? substr($sizeUnit[0], 0, 1) : $sizeUnit[0]);
        $pretty = '0' . ($noSpace ? '' : ' ') . $_unit;
    }

    if ($numOnly) {
        $pretty = floatval($pretty);
    }

    return $pretty;
}

/***
 * 16进制检测
 * @param $file
 * @return bool
 */
function checkHex($file): bool
{
    if (file_exists($file)) {
        $resource = fopen($file, 'rb');
        $file_size = filesize($file);
        fseek($resource, 0);
        //把文件指针移到文件的开头
        if ($file_size > 512) { // 若文件大于521B文件取头和尾
            $hex_code = bin2hex(fread($resource, 512));
            fseek($resource, $file_size - 512);
            //把文件指针移到文件尾部
            $hex_code .= bin2hex(fread($resource, 512));
        } else { // 取全部
            $hex_code = bin2hex(fread($resource, $file_size));
        }
        fclose($resource);

        /* 匹配16进制中的 <% ( ) %> */
        /* 匹配16进制中的 <? ( ) ?> */
        /* 匹配16进制中的 <script | /script> 大小写亦可 */
        /* 核心 通过匹配十六进制代码检测是否存在木马脚本 */
        if (preg_match("/(3c25.*?28.*?29.*?253e)|(3c3f.*?28.*?29.*?3f3e)|(3C534352495054)|(2F5343524950543E)|(3C736372697074)|(2F7363726970743E)/is", $hex_code))
            return false;
        else
            return true;
    } else {
        return false;
    }
}

function extraMenu()
{
    $extraMenu = '';
    if (isNanoid(session('uid'))) {
        $rightList = app\model\mRight::where([
            'mRight.State' => 0
        ])->hasWhere('UserRightR', ['U_Id' => session('uid'), 'State' => 0], '*', 'RIGHT')->order('R_Taxis')->select();

        foreach ($rightList as $right) {
            switch ($right['R_Code']) {
                case 'ARTICLE':
                    //$extraMenu .= '<li class="nav-item"><a id="lnk-post" class="nav-link" aria-current="page" href="/Article/post">' . lang('nav.post') . '</a></li>';
                    $extraMenu .= '<li class="nav-item dropdown"><a id="lnk-post" class="nav-link dropdown-toggle" href="javascript:;" role="button" data-bs-toggle="dropdown" aria-expanded="false">' . lang('nav.post') . '</a><ul class="dropdown-menu dropdown-menu-dark" aria-labelledby="lnk-post"><li><a id="lnk-postMarkdown" class="dropdown-item" href="/Article/postMarkdown">Markdown</a></li></ul></li>';
                    break;
                case 'CATEGORY':
                    $extraMenu .= '<li class="nav-item"><a id="lnk-category" class="nav-link" aria-current="page" href="/Article/category">' . lang('nav.category.management') . '</a></li>';
                    break;
            }
        }
    }
    return $extraMenu;
}

function diffSec($date, $nowDate = null)
{
    $nowDate = $nowDate ? strtotime($nowDate) : time();
    return $nowDate - strtotime($date);
}

function timeAgo($date, $nowDate = null)
{
    $unitArray = [lang('unit.second'), lang('unit.minute'), lang('unit.hour'), lang('unit.day'), lang('unit.week'), lang('unit.month'), lang('unit.year')];
    $unitsArray = [lang('unit.seconds'), lang('unit.minutes'), lang('unit.hours'), lang('unit.days'), lang('unit.weeks'), lang('unit.months'), lang('unit.years')];

    // second, minute, hour, day, week, month, year(365 days)
    $secArray = [60, 60, 24, 7, 365 / 7 / 12, 12];

    $diff = diffSec($date, $nowDate);

    $agoIn = $diff < 0 ? 1 : 0; // time ago or time in

    $diff = abs($diff);

    for ($i = 0; $i < count($secArray) && $diff >= $secArray[$i]; $i++) {
        $diff /= $secArray[$i];
    }
    $diff = intval($diff);

    if ($i === 0) {
        return [lang('sys.just.now'), lang('sys.right.now')][$agoIn];
    }

    $time = $diff . ($diff > 1 ? $unitsArray[$i] : $unitArray[$i]);
    return [lang('sys.time.ago', ['time' => $time]), lang('sys.time.in', ['time' => $time])][$agoIn];
}