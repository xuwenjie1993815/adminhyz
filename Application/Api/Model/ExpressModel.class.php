<?php

namespace Api\Model;

/**
 * Description of ExpressModel
 *
 * @author Chengwei Wang
 */
class ExpressModel extends \Think\Model{
    
    protected $_auto=array(
        array('createtime','getCreateTime',1,'callback'),
        array('validtime','getValidTime',1,'callback'),
        array('order_sn','getOrder',1,'callback'),
    );
    
    protected $_validate=array();
    
    protected function getCreateTime(){
        return date('Y-m-d H:i:s');
    }
    
    protected function getValidTime(){
        return date('Y-m-d H:i:s',strtotime('+1 day'));
    }
    
    protected function getOrder(){
        return '200'.date('YmdHis').rand(1000,9999);
    }
}
