<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017-04-13
 * Time: 9:07
 */

namespace Home\Model;


use Think\Model;

class TypeModel extends Model
{
    protected $_validate = array(
        array('brand_type', 'require', '请填写类型名称名称'),
        array('brand_id', 'require', ''),
    );

}