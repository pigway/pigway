<?php

use app\validate\vImage;
use \app\validate\vUser;

return array(
    'app' => array( // APP相关
        'ICP' => '沪ICP备09052554号',
        'author' => '渡口浪人',
        'author.email' => 'pigway@qq.com',
        'copyright.year' => '2009',
        'brand' => 'PIG Way',
        'keywords' => '渡口浪人,PigWay,个人信息分类方法,personal information group way',
        'description' => '个人信息分类方法,Personal Information Group Way',
        'welcome' => '欢迎回来！',
        'about' => '<blockquote class="blockquote">PIG Way</blockquote><figcaption class="blockquote-footer">Personal Information Group Way</figcaption>即个人信息分类方法。开发本平台的初衷是为凌乱、冗余的个人信息建立一种有效的管理方法，也提醒自己不要像猪那样好吃懒做，应勤而好学、不断提升并保持良好的心境。',
        'thank' => '<small>感谢：<a href="https://www.thinkphp.cn" class="text-silence" target="_blank">ThinkPHP</a>、<a href="https://pandao.github.io/editor.md" class="text-silence" target="_blank">Editor.md</a>、<a href="https://github.com/hustcc/timeago.js" class="text-silence" target="_blank">timeago.js</a> 等无私奉献的组织、个人等。</small>'
    ),
    'sys' => array( // 系统级操作
        'close' => '关闭',
        'search' => '搜索',
        'confirm' => '确认',
        'create' => '创建',
        'unavailable' => '不可用',
        'apply' => '申请',
        'verify' => '验证',
        'reset' => '重置',
        'set' => '设置',
        'refresh.pull.down' => '下拉刷新',
        'update.release' => '释放更新',
        'load.help' => '更新中',
        'resend' => '再次发送',
        'sent' => '已发送',
        'back' => '返回',
        'feedback' => '反馈',
        'time' => '时间',
        'ip.address' => 'IP地址',
        'url.address.wrong' => '错误链接地址',
        'report' => '报告',
        'report.content' => '报告内容',
        'operate' => '操作',
        'edit' => '编辑',
        'remove' => '移除',
        'clear' => '清空',
        'just.now' => '刚刚',
        'right.now' => '片刻后',
        'time.ago' => '{:time}前', // '{:time} ago'
        'time.in' => '{:time}后' // 'in {:time}'
    ),
    'punc' => array( // Punctuation
        'colon' => '：',
        'comma' => '，',
        'caesura' => '、',
        'bracket.left' => '（',
        'bracket.right' => '）'
    ),
    'unit' => array( // 计量单位
        'second' => '秒',
        'minute' => '分钟',
        'hour' => '小时',
        'day' => '天',
        'week' => '周',
        'month' => '月',
        'year' => '年',
        'seconds' => '秒',
        'minutes' => '分钟',
        'hours' => '小时',
        'days' => '天',
        'weeks' => '周',
        'months' => '月',
        'years' => '年',
        'byte' => '字节',
        'KB' => '千字节',
        'MB' => '兆字节',
        'GB' => '吉字节',
        'TB' => '太字节',
        'PB' => '拍字节',
        'EB' => '艾字节',
        'ZB' => '皆字节',
        'YB' => '尧它字节'
    ),
    'fail' => array( // 系统级失败

    ),
    'success' => array( // 系统级成功
        'set' => '设置成功！'
    ),
    'warning' => array( // 系统级警告
        'input' => '输入异常',
        'input.help' => '输入异常，请检查！',
        'token' => 'TOKEN异常',
        'token.help' => '重复提交或长时间滞留页面！',
        'request' => '请求异常',
        'request.help' => '请求异常，请重试！',
        'session' => '会话异常',
        'session.help' => '会话异常，请重新登录！',
        'data.removed' => '数据已移除',
        'data.removed.help' => '数据已被移除！',
        'data.locked' => '数据已锁定',
        'data.locked.help' => '数据已被锁定！',
        'data.none' => '无数据',
        'data.none.help' => '未找到相关数据！'
    ),
    'error' => array( // 系统级错误
        'database' => '数据库错误',
        'database.help' => '数据库错误，请稍后尝试！',
        'system' => '系统错误',
        'system.help' => '系统错误，请稍后尝试！',
        'system.feedback.click.help' => '如果您希望向我反馈错误，请点击：',
        'email.service' => '邮件服务错误',
        'email.service.help' => '邮件服务错误，请稍后尝试！',
        'mobile.phone.service' => '手机服务错误',
        'mobile.phone.service.help' => '手机服务错误，请稍后尝试！',
        'unknown' => '未知错误',
        'unknown.help' => '未知错误，请稍后尝试！',
        'right' => '权限错误',
        'right.help' => '权限错误，请检查您的账号！'
    ),
    'illegal' => array( // 系统级非法
        'request' => '非法请求',
        'file' => '非法文件'
    ),
    'data' => array( // 数据相关
        'update.last' => '最后更新于'
    ),
    'upload' => array( // 上传相关
        'browser.paste.unsupported' => '浏览器不支持粘帖上传',
        'file.size.limited' => '上传文件大小受限',
        'file.extension.limited' => '上传文件后缀受限',
        'file.mime.type.limited' => '上传文件MIME类型受限',
        'image.success' => '上传图片成功',
        'image.fail' => '上传图片失败',
        'image.size.limited.help' => '上传图片大小不能超过' . prettySize(vImage::MAX_SIZE, true) . '！', // 此处无法使用lang=true，语言无法解析
        'image.extension.limited.help' => '上传图片后缀仅限：' . vImage::FILE_EXT . '！',
        'image.mime.type.limited.help' => '上传图片MIME类型仅限：' . vImage::FILE_MIME . '！',
        'image.wait.help' => '请等待图片上传！'

    ),
    'page' => array( // 页面控制相关
        'number' => '页码',
        'turnTo' => '前往',
        'invalid.help' => '请填写正确的页码：'
    ),
    'timeAgo' => array( // timeAgo插件
        'lang' => 'zh_CN'
    ),
    'moment' => array( // moment插件
        'lang' => 'zh-cn'
    ),
    'captcha' => array( // 图形验证码
        '__self' => '图形验证码',
        'required' => '图形验证码必须',
        'length.error' => '图形验证码长度为{:length}位',
        'format.error' => '图形验证码格式错误',
        'mismatch' => '图形验证码不匹配',
        'expired.refresh.help' => '原图形验证码已失效，请点击刷新后重新输入',
    ),
    'sms' => array(
        'verification.code' => '短信验证码',
        'verification.code.input.help' => '请输入前置为<strong class="mx-1 text-decoration-underline">{:idCode}</strong>的{:length}位数字短信验证码',
        'verification.code.required' => '短信验证码必须',
        'verification.code.length.error' => '短信验证码长度为{:length}位',
        'verification.code.format.error' => '短信验证码格式错误',
        'verification.code.expired' => '短信验证码已失效',
        'verification.code.mismatch' => '短信验证码不匹配',
        'resend.time.max.limit' => '短信验证码最多可重发{:length}次'
    ),
    'nav' => array(
        'index' => '首页',
        'toggle' => '导航切换',
        'post' => '发布',
        'category.management' => '分类管理',
        'about' => '关于'
    ),
    'user' => array(
        '__self' => '用户',
        'none' => '用户不存在',
        'removed' => '用户已移除',

        'sign.in' => '登录',
        'sign.in.again.click.help' => '点击立即再次登录：',
        'sign.in.not' => '用户未登录',

        'logout' => '注销',
        'logout.done' => '已注销',
        'logout.force' => '强制注销',
        'logout.force.help' => '为保护用户安全，当前登录用户已被强制注销！',

        'username' => '用户名',
        'username.required' => '用户名必须',
        'username.length.error' => '用户名长度为' . vUser::USERNAME_MIN_LENGTH . '至' . vUser::USERNAME_MAX_LENGTH,
        'username.format.error' => '用户名仅包括大小写字母、数字、下划线或破折号',
        'username.non-exist' => '用户名不存在',
        'username.find' => '找回用户名',

        'password' => '密码',
        'password.required' => '密码必须',
        'password.length.error' => '密码长度为' . vUser::PASSWORD_MIN_LENGTH . '至' . vUser::PASSWORD_MAX_LENGTH,
        'password.mismatch' => '密码不匹配',
        'password.reset' => '重置密码',
        'password.reset.apply' => '申请密码重置',
        'password.reset.apply.success' => '申请密码重置成功',
        'password.reset.username.help' => '请输入您要重置密码的用户名',
        'password.reset.now.click' => '点击立即重置密码',
        'password.reset.now.click.link.help' => '或复制以下链接在浏览器中打开：',
        'password.reset.email.help' => '您收到本邮件是因为有人申请凭此邮件地址重置密码，如非您本人操作，请忽略或直接回复本邮件与我联系',
        'password.reset.email.sent' => '密码重置邮件已发送',
        'password.reset.email.sent.help' => '请尽快登录您的邮箱并按提示操作',
        'password.reset.to.sign.in.click.help' => '密码已重置？点击立即登录：',
        'password.reset.link.invalid' => '密码重置链接无效',
        'password.reset.link.expired' => '密码重置链接已失效',
        'password.reset.done' => '密码已重置',
        'password.reset.done.help' => '请妥善保存您的密码',
        'password.confirm' => '确认密码',
        'password.confirm.mismatch' => '两次密码不一致',
        'password.old' => '原密码',

        'email.address.required' => '邮件地址必须',
        'email.address.format.error' => '邮件地址格式错误',
        'email.address.mismatch' => '邮件地址不匹配',

        'mobile.phone.number.required' => '手机号码必须',
        'mobile.phone.number.format.error' => '手机号码格式错误',
        'mobile.phone.number.mismatch' => '手机号码不匹配',
        'mobile.phone.number.verify' => '手机号码验证',
        'mobile.phone.number.verify.help' => '请验证您的手机号码',

        'verify' => '用户验证',
        'verify.result' => '验证结果',
        'verify.method' => '验证方式',
        'verify.method.none' => '无验证方式',
        'verify.method.required' => '验证方式必须',
        'verify.method.mismatch' => '验证方式不匹配',
        'verify.method.select.help' => '请选择验证方式',
        'verify.method.info.full' => '完整信息',
        'verify.method.info.full.required' => '验证方式完整信息必须',
        'verify.email.address.full' => '验证完整邮件地址',
        'verify.mobile.phone.number.full' => '验证完整手机号码',

        'display.name' => '显示昵称',
        'display.number' => '用户编号',

        'center' => '个人中心',

        'return.look.forward' => '期待您的归来'
    ),
    'article' => array(
        'post' => '发布',
        'post.fail' => '发布失败',
        'save.draft' => '保存草稿',
        'title' => '标题',
        'title.none' => '暂无标题',
        'subtitle' => '副标题',
        'alias' => '别名',
        'author' => '作者',
        'author.available.none' => '无可用作者',
        'category' => '分类',
        'category.set.help' => '请设置文章分类',
        'category.name' => '分类名称',
        'category.alias' => '分类别名',
        'category.alias.unique.limited' => '分类别名唯一性受限',
        'category.taxis' => '排序序号',
        'category.total' => '文章总数',
        'category.parent' => '上级分类',
        'category.remark' => '分类备注',
        'markdown.enjoy.help' => '开始享受 Markdown！',
        'create.by' => '由{:author}创建',
        'file.in' => '归档于',
        'right.error' => '文章权限错误',
        'right.error.help' => '您没有该文章操作权限！',
        'edit' => '编辑',
        'none' => '没有文章'
    )
);