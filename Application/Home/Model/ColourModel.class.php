<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017-04-13
 * Time: 14:43
 */

namespace Home\Model;


use Think\Model;

class ColourModel extends Model
{

    protected $_validate = array(
        array('colours', 'require', '请填写颜色名称'),
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