<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/1/4
 * Time: 14:59
 */

namespace Home\Model;

use Think\Model;

class DriverModel extends Model
{
    protected $_validate = array(
        array('mobile','require','手机号码缺失！'),
        array('mobile','/^1[3|4|5|8|7][0-9]\d{8}$/','手机号码错误！','0','regex',1),
        array('mobile','','该手机已经被注册！',0,'unique',2),
        //array('brand','require','车辆品牌缺失！'),
        array('password','require','密码缺失'),
    );

    protected $_auto = array(
        array('password', '', self::MODEL_UPDATE, 'ignore'),
        array('password', 'md5', self::MODEL_BOTH, 'function'),
        array('password', NULL, self::MODEL_UPDATE, 'ignore'),

        array('create_time', 'time', 1, 'function'),
    );

}