<?php
// +----------------------------------------------------------------------
// | 模板设置
// +----------------------------------------------------------------------

return [
    // 模板引擎类型使用Think
    'type' => 'Think',
    // 默认模板渲染规则 1 解析为小写+下划线 2 全部转换小写 3 保持操作方法
    'auto_rule' => 1,
    // 模板目录名
    'view_dir_name' => 'view',
    // 模板后缀
    'view_suffix' => 'html',
    // 模板文件名分隔符
    'view_depr' => DIRECTORY_SEPARATOR,
    // 模板引擎普通标签开始标记
    'tpl_begin' => '{',
    // 模板引擎普通标签结束标记
    'tpl_end' => '}',
    // 标签库标签开始标记
    'taglib_begin' => '{',
    // 标签库标签结束标记
    'taglib_end' => '}',

    //'tpl_cache' => false, //用于强制刷新缓存
    'tpl_replace_string' => [
        '__STATIC__' => '/static',
        '__JS__' => '/static/js',
        '__CSS__' => '/static/css',
        '__BS__' => '/static/twbs/bootstrap',
        '__ED__' => '/static/editormd',
        '__KATEX__' => '/static/katex',
        '__MES__' => '/static/mescroll',
        '__M__' => '/static/moment',
        '__TD__' => '/static/tempus-dominus',
        '__FA__' => '/static/fontawesome'
    ],
];
