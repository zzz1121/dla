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
    '__pattern__' => [
        'name' => '\w+',
    ],
    'api\:name'=>'index/api',
    'api/sms'     => 'api/login/sms_send',
    'api\bankcard'=>'api/bankcard/index',
    'api/sub_order'     => 'api/orders/sub_order',
    'api/get_orders'     => 'api/orders/get_orders',
    'api/delete_card'     => 'api/bankcard/delete_card',
    'api/change_debit'     => 'api/bankcard/change_debit',
    'api/get_underling'     => 'api/user/get_underling',
    'api/bind_recommend'     => 'api/online/bind_recommend',
    'api/get_settle_rate'     => 'api/user/get_settle_rate',
    'api/get_identity'     => 'api/identity/get_identity',
    'api/getQrcode'     => 'api/user/getQrcode',
    'api/advance'     => 'api/user/advance',
    'api/get_pay_orders'     => 'api/user/get_pay_orders',
    'api/pay_update'     => 'api/callback/pay_update',
    'api/get_profit'     => 'api/user/get_profit',
    'load'     => 'index/reg/load'

];
