<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/4/18
 * Time: 16:43
 */

namespace Home\Model;


use Think\Model;

class AdvertListModel extends Model
{
    protected $_validate = array(
        array('imgurl', 'require', '请填写图片'),
    );


    protected $_auto = array(
        array('create_time', 'time', 1, 'function'),
        array('code', 'getCode', 1, 'callback')
    );


    protected function getCode()
    {
        $max_id = $this->max('id');

        $code = $max_id ? 10000000 + $max_id : 10000000;
        return $code;
    }
}