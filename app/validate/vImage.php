<?php
declare (strict_types=1);

namespace app\validate;

use app\controller\Code;
use think\Validate;

class vImage extends Validate
{
    const MAX_SIZE = 52428800;
    const FILE_EXT = 'jpg,jpeg,gif,png,bmp,webp';
    const FILE_MIME = 'image/jpeg,image/gif,image/png,image/bmp,image/webp';

    /**
     * 定义验证规则
     * 格式：'字段名' =>  ['规则1','规则2'...]
     *
     * @var array
     */
    protected $rule = [
        'image' => [
            'fileSize' => self::MAX_SIZE,
            'fileExt' => self::FILE_EXT,
            'fileMime' => self::FILE_MIME
        ]
    ];

    /**
     * 定义错误信息
     * 格式：'字段名.规则名' =>  '错误信息'
     *
     * @var array
     */
    protected $message = [
        'image.fileSize' => ['code' => Code::UPLOAD_FILE_SIZE_LIMIT, 'msg' => 'upload.image.size.limited.help'],
        'image.fileExt' => ['code' => Code::UPLOAD_FILE_EXTENSION_LIMIT, 'msg' => 'upload.image.extension.limited.help'],
        'image.fileMime' => ['code' => Code::UPLOAD_FILE_MIME_TYPE_LIMIT, 'msg' => 'upload.image.mime.type.limited.help']
    ];

    protected $scene = [
    ];
}
