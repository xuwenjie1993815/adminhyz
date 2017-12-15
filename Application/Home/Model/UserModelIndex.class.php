<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/1/10
 * Time: 14:58
 */

namespace Home\Model;


class UserModelIndexModel extends \Think\Model
{
    protected $_validate = array(
        array('mobile','require','手机号码缺失！', 1),
        array('mobile','/^1[3|4|5|8|7][0-9]\d{4,8}$/','手机号码错误！','0','regex',1),
        array('mobile','','该手机已经被注册！',0,'unique',1),

    );

    protected $_auto = array(
        
        array('create_time', 'time', 1, 'function'),
    );


    public function updateFields()
    {
        return array('pwd', 'nick_name', 'head_pic');
    }
}