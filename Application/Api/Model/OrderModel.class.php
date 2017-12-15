<?php

namespace Api\Model;

/**
 * Description of OrderModel
 *
 * @author Chengwei Wang
 */
class OrderModel extends \Think\Model\ViewModel{
    public $viewFields=array(
        'Order'=>array('id','order_sn','seat_num','money','user_id','pay_mothod','status'=>'state','create_time'=>'createtime','remark','_as'=>'a'),
        'Service'=>array('id'=>'service_id','link_phone','type','is_yy','price','block','status','departur_time','departur_day','departur_period','remark','_on'=>'a.service_id=b.id','_as'=>'b'),
        'Driver_info'=>array('license_sn'=>'carnum','true_name'=>'drivername','driver_id','head_pic'=>'headimg','_on'=>'b.driver_id=c.id','_as'=>'c'),
        'Driver'=>array('mobile'=>'mobile','_on'=>'dr.id=c.id','_as'=>'dr'),
        'scity'=>array('_table'=>"__REGION__",'name'=>'startcity','_as'=>'d','_on'=>'b.start_city=d.id'),
        'pcity'=>array('_table'=>"__REGION__",'name'=>'purposecity','_as'=>'e','_on'=>'b.purpose_city=e.id'),
        'sstation'=>array('_table'=>"__REGION__",'name'=>'startstation','_as'=>'f','_on'=>'b.start_point=f.id'),
        'pstation'=>array('_table'=>"__REGION__",'name'=>'purposestation','_as'=>'g','_on'=>'b.purpose_point=g.id'),
        
    );
}
