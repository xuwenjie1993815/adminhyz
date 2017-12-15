<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/1/4
 * Time: 10:33
 */

namespace Base\Model;


class SmsModel extends \Think\Model
{
    protected $_validate = array(
        array('mobile','require','手机号码缺失！', 1),
        array('mobile','/^1[3|4|5|8|7][0-9]\d{4,8}$/','手机号码错误！','0','regex',1)
    );
}