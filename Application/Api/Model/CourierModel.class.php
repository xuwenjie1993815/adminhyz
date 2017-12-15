<?php

namespace Api\Model;

/**
 * Description of CourierModel
 *
 * @author Chengwei Wang
 */
class CourierModel extends \Think\Model\ViewModel{
    public $viewFields=array(
        'Express'=>array('_as'=>'a','order_sn','state','')
    );
}
