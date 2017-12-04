<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

return [
    // +----------------------------------------------------------------------
    // | 应用设置
    // +----------------------------------------------------------------------

    'sp_id'                 =>'1086-2',
    'sbt_key'                      => 'D7B06CF4779B444AB944DBBF5AE9641F',
    //'sp_id'                 =>'1086-1',
    //'sbt_key'                      => 'AC0838C5EDE74CDC97A449659E8EFC2A',
    'sbt_api_url'               =>'http://api.shangfudata.com',
    'notify_url'            =>'http://'.$_SERVER['SERVER_NAME'].'/index.php/api/callback',
    'api_page_count'	=>15,
    'bank_no_credit'       =>[
        ['bank_no'=>'01020000','bank_name'=>'工商银行'],
        ['bank_no'=>'03080000','bank_name'=>'招商银行'],
        ['bank_no'=>'03080000','bank_name'=>"招商银行信用卡中心"],
        ['bank_no'=>'03010000','bank_name'=>'交通银行'],
        ['bank_no'=>'03010000','bank_name'=>'交通银行信用卡中心'],
        ['bank_no'=>'01040000','bank_name'=>'中国银行'],
        ['bank_no'=>'03030000','bank_name'=>'光大银行'],
        ['bank_no'=>'03030000','bank_name'=>'光大银行信用卡中心'],
        ['bank_no'=>'03030000','bank_name'=>"光大信用卡中心"],
        ['bank_no'=>'03050000','bank_name'=>'民生银行'],
        ['bank_no'=>'03050000','bank_name'=>'民生信用卡中心'],
        ['bank_no'=>'03090000','bank_name'=>'兴业银行'],
        ['bank_no'=>'03090000','bank_name'=>'兴业信用卡中心'],
        ['bank_no'=>'03020000','bank_name'=>'中信银行'],
        ['bank_no'=>'03020000','bank_name'=>'中信银行信用卡中心'],
        ['bank_no'=>'03060000','bank_name'=>'广发'],
        ['bank_no'=>'03060000','bank_name'=>'广发银行信用卡中心'],
        ['bank_no'=>'03060000','bank_name'=>'广发银行'],
        ['bank_no'=>'31029000','bank_name'=>'浦发银行'],
        ['bank_no'=>'31029000','bank_name'=>'浦东发展'],
        ['bank_no'=>'03070000','bank_name'=>'平安银行'],
        ['bank_no'=>'03070000','bank_name'=>"平安银行信用卡"],
        ['bank_no'=>'04083320','bank_name'=>'宁波银行'],
        ['bank_no'=>'03200000','bank_name'=>'东亚银行'],
        ['bank_no'=>'01000000','bank_name'=>'中国邮储银行'],
        ['bank_no'=>'04243010','bank_name'=>'南京银行'],
        ['bank_no'=>'65012900','bank_name'=>'上海农商银行'],
        ['bank_no'=>'04010000','bank_name'=>'上海银行'],
        ['bank_no'=>'04010000','bank_name'=>"上海银行贷记卡"],
        ['bank_no'=>'04031000','bank_name'=>'北京银行'],
        ['bank_no'=>'01030000','bank_name'=>'农业银行'],
        ['bank_no'=>'03040000','bank_name'=>'华夏银行'],
        ['bank_no'=>'01050000','bank_name'=>'建设银行'],
        ['bank_no'=>'01050000','bank_name'=>'中国建设银行'],
        ['bank_no'=>'04123330','bank_name'=>'温州银行'],
        ['bank_no'=>'04123330','bank_name'=>'温州银行'],
        ['bank_no'=>'04135810','bank_name'=>'广州银行'],
        ['bank_no'=>'04202220','bank_name'=>'大连银行'],
        ['bank_no'=>'04233310','bank_name'=>'杭州银行'],
        ['bank_no'=>'04392270','bank_name'=>'锦州银行'],
        ['bank_no'=>'04416900','bank_name'=>'重庆银行'],
        ['bank_no'=>'04422610','bank_name'=>'哈尔滨银行'],
        ['bank_no'=>'04478210','bank_name'=>'兰州银行'],
        ['bank_no'=>'04484210','bank_name'=>'南昌银行'],
        ['bank_no'=>'04922600','bank_name'=>'龙江银行'],
        ['bank_no'=>'05083000','bank_name'=>'江苏银行'],
        ['bank_no'=>'05083000','bank_name'=>'江苏银行'],
        ['bank_no'=>'14136900','bank_name'=>'重庆农村商业银行']
    ],
    'bank_no_debit'       =>[
        ['bank_no'=>'104100000004','bank_name'=>'中国银行'],
        ['bank_no'=>'103100000026','bank_name'=>'农业银行'],
        ['bank_no'=>'103100000026','bank_name'=>'中国银行'],
        ['bank_no'=>'102100099996','bank_name'=>'中国工商银行'],
        ['bank_no'=>'102100099996','bank_name'=>'工商银行'],
        ['bank_no'=>'105100000017','bank_name'=>'建设银行'],
        ['bank_no'=>'105100000017','bank_name'=>'中国建设银行'],
        ['bank_no'=>'301290000007','bank_name'=>'交通银行'],
        ['bank_no'=>'302100011000','bank_name'=>'中信银行'],
        ['bank_no'=>'303100000006','bank_name'=>'光大银行'],
        ['bank_no'=>'303100000006','bank_name'=>"光大信用卡中心"],
        ['bank_no'=>'304100040000','bank_name'=>'华夏银行'],
        ['bank_no'=>'306581000003','bank_name'=>'广发银行'],
        ['bank_no'=>'307584007998','bank_name'=>'平安银行'],
        ['bank_no'=>'308584000013','bank_name'=>'招商银行'],
        ['bank_no'=>'305100000013','bank_name'=>'民生银行'],
        ['bank_no'=>'309391000011','bank_name'=>'兴业银行'],
        ['bank_no'=>'305100000013','bank_name'=>'上海浦东发展银行'],
        ['bank_no'=>'315456000105','bank_name'=>'恒丰银行'],
        ['bank_no'=>'317110010019','bank_name'=>'天津农村商业银行'],
        ['bank_no'=>'318110000014','bank_name'=>'渤海银行'],
        ['bank_no'=>'319361000013','bank_name'=>'徽商银行'],
        ['bank_no'=>'322290000011','bank_name'=>'上海农商银行'],
        ['bank_no'=>'403100000004','bank_name'=>'中国邮政储蓄银行'],
        ['bank_no'=>'591110000016','bank_name'=>'外换银行'],
        ['bank_no'=>'593100000020','bank_name'=>'友利银行'],
        ['bank_no'=>'595100000007','bank_name'=>'新韩银行'],
        ['bank_no'=>'596110000013','bank_name'=>'企业银行'],
        ['bank_no'=>'597100000014','bank_name'=>'韩亚银行'],
        ['bank_no'=>'313100000013','bank_name'=>'北京银行'],
        ['bank_no'=>'313110000017','bank_name'=>'天津银行'],
        ['bank_no'=>'313121006888','bank_name'=>'河北银行'],
        ['bank_no'=>'313127000013','bank_name'=>'邯郸银行'],
        ['bank_no'=>'313131000016','bank_name'=>'邢台银行'],
        ['bank_no'=>'313138000019','bank_name'=>'张家口市商业银行'],
        ['bank_no'=>'313141052422','bank_name'=>'承德银行'],
        ['bank_no'=>'313143005157','bank_name'=>'沧州银行'],
        ['bank_no'=>'313146000019','bank_name'=>'廊坊银行'],
        ['bank_no'=>'313161000017','bank_name'=>'晋商银行'],
        ['bank_no'=>'313168000003','bank_name'=>'晋城银行'],
        ['bank_no'=>'313191000011','bank_name'=>'内蒙古银行'],
        ['bank_no'=>'313192000013','bank_name'=>'包商银行'],
        ['bank_no'=>'313205057830','bank_name'=>'鄂尔多斯银行'],
        ['bank_no'=>'313222080002','bank_name'=>'大连银行'],
        ['bank_no'=>'313223007007','bank_name'=>'鞍山市商业银行'],
        ['bank_no'=>'313227000012','bank_name'=>'锦州银行'],
        ['bank_no'=>'313227600018','bank_name'=>'葫芦岛银行'],
        ['bank_no'=>'313228000276','bank_name'=>'营口沿海银行'],
        ['bank_no'=>'313229000008','bank_name'=>'阜新银行'],
        ['bank_no'=>'313241066661','bank_name'=>'吉林银行'],
        ['bank_no'=>'313261000018','bank_name'=>'阜新哈尔滨市商业银行'],
        ['bank_no'=>'313261099913','bank_name'=>'龙江银行'],
        ['bank_no'=>'325290000012','bank_name'=>'上海银行'],
        ['bank_no'=>'313301008887','bank_name'=>'南京银行'],
        ['bank_no'=>'313301099999','bank_name'=>'江苏银行'],
        ['bank_no'=>'313305066661','bank_name'=>'苏州银行'],
        ['bank_no'=>'313331000014','bank_name'=>'杭州银行'],
        ['bank_no'=>'313332082914','bank_name'=>'宁波银行'],
        ['bank_no'=>'313333007331','bank_name'=>'温州银行'],
        ['bank_no'=>'313335081005','bank_name'=>'嘉兴银行'],
        ['bank_no'=>'313336071575','bank_name'=>'湖州银行'],
        ['bank_no'=>'313337009004','bank_name'=>'绍兴银行'],
        ['bank_no'=>'313338707013','bank_name'=>'浙江稠州商业银行'],
        ['bank_no'=>'313345001665','bank_name'=>'台州银行'],
        ['bank_no'=>'313345010019','bank_name'=>'浙江泰隆商业银行'],
        ['bank_no'=>'313345400010','bank_name'=>'浙江民泰商业银行'],
        ['bank_no'=>'313391080007','bank_name'=>'福建海峡银行'],
        ['bank_no'=>'313393080005','bank_name'=>'厦门银行'],
        ['bank_no'=>'313421087506','bank_name'=>'南昌银行'],
        ['bank_no'=>'313428076517','bank_name'=>'赣州银行'],
        ['bank_no'=>'313433076801','bank_name'=>'上饶银行'],
        ['bank_no'=>'313452060150','bank_name'=>'青岛银行'],
        ['bank_no'=>'313453001017','bank_name'=>'齐商银行'],
        ['bank_no'=>'313455000018','bank_name'=>'东营市商业银行'],
        ['bank_no'=>'313456000108','bank_name'=>'烟台银行'],
        ['bank_no'=>'313458000013','bank_name'=>'潍坊银行'],
        ['bank_no'=>'313461000012','bank_name'=>'济宁银行'],
        ['bank_no'=>'313463000993','bank_name'=>'泰安市商业银行'],
        ['bank_no'=>'313463400019','bank_name'=>'莱商银行'],
        ['bank_no'=>'313465000010','bank_name'=>'威海市商业银行'],
        ['bank_no'=>'313468000015','bank_name'=>'德州银行'],
        ['bank_no'=>'313473070018','bank_name'=>'临商银行'],
        ['bank_no'=>'313473200011','bank_name'=>'日照银行'],
        ['bank_no'=>'313491000232','bank_name'=>'郑州银行'],
        ['bank_no'=>'313492070005','bank_name'=>'开封市商业银行'],
        ['bank_no'=>'313493080539','bank_name'=>'洛阳银行'],
        ['bank_no'=>'313504000010','bank_name'=>'漯河银行'],
        ['bank_no'=>'313506082510','bank_name'=>'商丘银行'],
        ['bank_no'=>'313513080408','bank_name'=>'南阳银行'],
        ['bank_no'=>'313521000011','bank_name'=>'汉口银行'],
        ['bank_no'=>'313551088886','bank_name'=>'长沙银行'],
        ['bank_no'=>'313581003284','bank_name'=>'广州银行'],
        ['bank_no'=>'313585000990','bank_name'=>'珠海华润银行'],
        ['bank_no'=>'313591001001','bank_name'=>'广东南粤银行'],
        ['bank_no'=>'313602088017','bank_name'=>'东莞银行'],
        ['bank_no'=>'313611001018','bank_name'=>'广西北部湾银行'],
        ['bank_no'=>'313614000012','bank_name'=>'柳州银行'],
        ['bank_no'=>'313653000013','bank_name'=>'重庆银行'],
        ['bank_no'=>'313655091983','bank_name'=>'自贡市商业银行'],
        ['bank_no'=>'313656000019','bank_name'=>'攀枝花市商业银行'],
        ['bank_no'=>'313658000014','bank_name'=>'德阳银行'],
        ['bank_no'=>'313659000016','bank_name'=>'绵阳市商业银行'],
        ['bank_no'=>'313701098010','bank_name'=>'贵阳银行'],
        ['bank_no'=>'313731010015','bank_name'=>'富滇银行'],
        ['bank_no'=>'313791030003','bank_name'=>'长安银行'],
        ['bank_no'=>'313821001016','bank_name'=>'兰州银行'],
        ['bank_no'=>'313851000018','bank_name'=>'青海银行'],
        ['bank_no'=>'313871000007','bank_name'=>'宁夏银行'],
        ['bank_no'=>'313881000002','bank_name'=>'乌鲁木齐市商业银行'],
        ['bank_no'=>'313882000012','bank_name'=>'昆仑银行'],
        ['bank_no'=>'313651099999','bank_name'=>'成都银行'],
        ['bank_no'=>'313673093259','bank_name'=>'南充市商业银行'],
        ['bank_no'=>'314305206650','bank_name'=>'昆山农村商业银行'],
        ['bank_no'=>'314305400015','bank_name'=>'吴江农村商业银行'],
        ['bank_no'=>'314305506621','bank_name'=>'常熟农村商业银行'],
        ['bank_no'=>'314305670002','bank_name'=>'张家港农村商业银行'],
        ['bank_no'=>'314581000011','bank_name'=>'广州农村商业银行'],
        ['bank_no'=>'314588000016','bank_name'=>'顺德农村商业银行'],
        ['bank_no'=>'314653000011','bank_name'=>'重庆农村商业银行'],
        ['bank_no'=>'402100000018','bank_name'=>'北京农村商业银行'],
        ['bank_no'=>'402241000015','bank_name'=>'吉林农村信用社'],
        ['bank_no'=>'402301099998','bank_name'=>'江苏省农村信用社联合社'],
        ['bank_no'=>'402331000007','bank_name'=>'浙江省农村信用社'],
        ['bank_no'=>'402332010004','bank_name'=>'鄞州银行'],
        ['bank_no'=>'402361018886','bank_name'=>'安徽省农村信用社联合社'],
        ['bank_no'=>'402391000068','bank_name'=>'福建福州农村商业银行'],
        ['bank_no'=>'402451000010','bank_name'=>'山东省农村信用社联合社'],
        ['bank_no'=>'402521000032','bank_name'=>'湖北农信'],
        ['bank_no'=>'402584009991','bank_name'=>'深圳农商行'],
        ['bank_no'=>'402602000018','bank_name'=>'东莞农村商业银行'],
        ['bank_no'=>'402611099974','bank_name'=>'广西壮族自治区农村信用社'],
        ['bank_no'=>'402641000014','bank_name'=>'海南省农村信用社'],
        ['bank_no'=>'402731057238','bank_name'=>'云南省农村信用社'],
        ['bank_no'=>'402871099996','bank_name'=>'黄河农村商业银行']
    ],
    // 应用命名空间
    'app_namespace'          => 'app',
    // 应用调试模式
    'app_debug'              => true,
    // 应用Trace
    'app_trace'              => false,
    // 应用模式状态
    'app_status'             => '',
    // 是否支持多模块
    'app_multi_module'       => true,
    // 入口自动绑定模块
    'auto_bind_module'       => false,
    // 注册的根命名空间
    'root_namespace'         => [],
    // 扩展函数文件
    'extra_file_list'        => [THINK_PATH . 'helper' . EXT],
    // 默认输出类型
    'default_return_type'    => 'json',
    // 默认AJAX 数据返回格式,可选json xml ...
    'default_ajax_return'    => 'json',
    // 默认JSONP格式返回的处理方法
    'default_jsonp_handler'  => 'jsonpReturn',
    // 默认JSONP处理方法
    'var_jsonp_handler'      => 'callback',
    // 默认时区
    'default_timezone'       => 'PRC',
    // 是否开启多语言
    'lang_switch_on'         => false,
    // 默认全局过滤方法 用逗号分隔多个
    'default_filter'         => 'htmlspecialchars',
    // 默认语言
    'default_lang'           => 'zh-cn',
    // 应用类库后缀
    'class_suffix'           => false,
    // 控制器类后缀
    'controller_suffix'      => false,

    // +----------------------------------------------------------------------
    // | 模块设置
    // +----------------------------------------------------------------------

    // 默认模块名
    'default_module'         => 'index',
    // 禁止访问模块
    'deny_module_list'       => ['common'],
    // 默认控制器名
    'default_controller'     => 'Index',
    // 默认操作名
    'default_action'         => 'index',
    // 默认验证器
    'default_validate'       => '',
    // 默认的空控制器名
    'empty_controller'       => 'Error',
    // 操作方法后缀
    'action_suffix'          => '',
    // 自动搜索控制器
    'controller_auto_search' => false,

    // +----------------------------------------------------------------------
    // | URL设置
    // +----------------------------------------------------------------------

    // PATHINFO变量名 用于兼容模式
    'var_pathinfo'           => 's',
    // 兼容PATH_INFO获取
    'pathinfo_fetch'         => ['ORIG_PATH_INFO', 'REDIRECT_PATH_INFO', 'REDIRECT_URL'],
    // pathinfo分隔符
    'pathinfo_depr'          => '/',
    // URL伪静态后缀
    'url_html_suffix'        => 'html',
    // URL普通方式参数 用于自动生成
    'url_common_param'       => false,
    // URL参数方式 0 按名称成对解析 1 按顺序解析
    'url_param_type'         => 0,
    // 是否开启路由
    'url_route_on'           => true,
    // 路由使用完整匹配
    'route_complete_match'   => false,
    // 路由配置文件（支持配置多个）
    'route_config_file'      => ['route'],
    // 是否强制使用路由
    'url_route_must'         => false,
    // 域名部署
    'url_domain_deploy'      => false,
    // 域名根，如thinkphp.cn
    'url_domain_root'        => '',
    // 是否自动转换URL中的控制器和操作名
    'url_convert'            => true,
    // 默认的访问控制器层
    'url_controller_layer'   => 'controller',
    // 表单请求类型伪装变量
    'var_method'             => '_method',
    // 表单ajax伪装变量
    'var_ajax'               => '_ajax',
    // 表单pjax伪装变量
    'var_pjax'               => '_pjax',
    // 是否开启请求缓存 true自动缓存 支持设置请求缓存规则
    'request_cache'          => false,
    // 请求缓存有效期
    'request_cache_expire'   => null,
    // 全局请求缓存排除规则
    'request_cache_except'   => [],

    // +----------------------------------------------------------------------
    // | 模板设置
    // +----------------------------------------------------------------------

    'template'               => [
        // 模板引擎类型 支持 php think 支持扩展
        'type'         => 'Think',
        // 模板路径
        'view_path'    => '',
        // 模板后缀
        'view_suffix'  => 'html',
        // 模板文件名分隔符
        'view_depr'    => DS,
        // 模板引擎普通标签开始标记
        'tpl_begin'    => '{',
        // 模板引擎普通标签结束标记
        'tpl_end'      => '}',
        // 标签库标签开始标记
        'taglib_begin' => '{',
        // 标签库标签结束标记
        'taglib_end'   => '}',
    ],

    // 视图输出字符串内容替换
    'view_replace_str'       => [],
    // 默认跳转页面对应的模板文件
    'dispatch_success_tmpl'  => THINK_PATH . 'tpl' . DS . 'dispatch_jump.tpl',
    'dispatch_error_tmpl'    => THINK_PATH . 'tpl' . DS . 'dispatch_jump.tpl',

    // +----------------------------------------------------------------------
    // | 异常及错误设置
    // +----------------------------------------------------------------------

    // 异常页面的模板文件
    'exception_tmpl'         => THINK_PATH . 'tpl' . DS . 'think_exception.tpl',

    // 错误显示信息,非调试模式有效
    'error_message'          => '页面错误！请稍后再试～',
    // 显示错误信息
    'show_error_msg'         => false,
    // 异常处理handle类 留空使用 \think\exception\Handle
    'exception_handle'       => '',

    // +----------------------------------------------------------------------
    // | 日志设置
    // +----------------------------------------------------------------------

    'log'                    => [
        // 日志记录方式，内置 file socket 支持扩展
        'type'  => 'File',
        // 日志保存目录
        'path'  => LOG_PATH,
        // 日志记录级别
        'level' => [],
    ],

    // +----------------------------------------------------------------------
    // | Trace设置 开启 app_trace 后 有效
    // +----------------------------------------------------------------------
    'trace'                  => [
        // 内置Html Console 支持扩展
        'type' => 'Html',
    ],

    // +----------------------------------------------------------------------
    // | 缓存设置
    // +----------------------------------------------------------------------

    'cache'                  => [
        // 驱动方式
        'type'   => 'File',
        // 缓存保存目录
        'path'   => CACHE_PATH,
        // 缓存前缀
        'prefix' => '',
        // 缓存有效期 0表示永久缓存
        'expire' => 0,
    ],

    // +----------------------------------------------------------------------
    // | 会话设置
    // +----------------------------------------------------------------------

    'session'                => [
        'id'             => '',
        // SESSION_ID的提交变量,解决flash上传跨域
        'var_session_id' => '',
        // SESSION 前缀
        'prefix'         => 'think',
        // 驱动方式 支持redis memcache memcached
        'type'           => '',
        // 是否自动开启 SESSION
        'auto_start'     => true,
    ],

    // +----------------------------------------------------------------------
    // | Cookie设置
    // +----------------------------------------------------------------------
    'cookie'                 => [
        // cookie 名称前缀
        'prefix'    => '',
        // cookie 保存时间
        'expire'    => 0,
        // cookie 保存路径
        'path'      => '/',
        // cookie 有效域名
        'domain'    => '',
        //  cookie 启用安全传输
        'secure'    => false,
        // httponly设置
        'httponly'  => '',
        // 是否使用 setcookie
        'setcookie' => true,
    ],

    //分页配置
    'paginate'               => [
        'type'      => 'bootstrap',
        'var_page'  => 'page',
        'list_rows' => 15,
    ],
];
