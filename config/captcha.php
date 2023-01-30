<?php
// +----------------------------------------------------------------------
// | Captcha配置文件
// +----------------------------------------------------------------------

return [
    //验证码位数
    'length' => 5,
    // 验证码字符集合
    'codeSet' => '2345678abcdefhijkmnpqrstuvwxyzABCDEFGHJKLMNPQRTUVWXY',
    // 验证码过期时间
    'expire' => 1800,
    // 是否使用中文验证码
    'useZh' => false,
    // 是否使用算术验证码
    'math' => false,
    // 是否使用背景图
    'useImgBg' => false,
    //验证码字符大小
    'fontSize' => 25,
    // 是否使用混淆曲线
    'useCurve' => true,
    //是否添加杂点
    'useNoise' => true,
    // 验证码字体 不设置则随机
    'fontttf' => '',
    //背景颜色
    'bg' => [243, 251, 254],
    // 验证码图片高度
    'imageH' => 0,
    // 验证码图片宽度
    'imageW' => 0,

    // 添加额外的验证码设置
    'user' => [
        'length' => 4,
        'fontSize' => 25,
        'useCurve' => false,
        'useNoise' => true,
        //$y = $this->fontSize + mt_rand(10, 20); // 原始计算验证码字符y轴公式
        //$this->imageH = $this->fontSize * 2.5; // 原始计算图片高度公式，存在显示不全可能
        'imageH' => 25 + 20 + 25 / 2, // 替换为计算最大y轴可能值
        //$x = $this->fontSize * ($index + 1) * mt_rand(12, 16) / 10 * ($this->math ? 1 : 1.5); // 原始计算验证码字符x轴公式
        //$this->imageW = $this->length * $this->fontSize * 1.5 + $this->length * $this->fontSize / 2; // 原始计算图片宽度公式，存在显示不全可能
        'imageW' => 25 * (3 + 1) * 16 / 10 * 1.5 + 25 // 替换为计算最大x轴可能值
        // 说实话，感觉这个验证码生成方法原档写得不太合理，会有显示不全情况，鉴于尊重原著，不改了，就是会导致验证码出来很宽（涉及到字符的基线问题，大部分字体的基线都在左中、左下部分，但源码左边直接空开一个字符左右大小），字符排列也不太合理，会出现重叠情况，不美观
    ],
];
