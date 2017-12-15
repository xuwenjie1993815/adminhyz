<?php

namespace Test\Controller;

use Base\Controller\BaseController;
use Base\Logic\PubLogic;
use Home\Logic\AdvertLogic;
use Test\Logic\LastLogic;
use Think\Exception;

/**
 * Description of LastController
 * 处理数据之前老数据
 * @author 姣姣
 */
class TemittanceController extends BaseController {
    /**
     * 提现
     */
    public function remittance() {
        $info = M('remittance')->select();
        foreach ($info as $key => $value) {
            switch ($value['usertype']) {
                case 1://乘客
                    $model = M('user');
                    $invite_code = $model->where(['id'=>$value['userid']])->getField('invite_code');
                    
                    break;
                case 2://司机
                    $model = M('driver');
                    $invite_code = $model->where(['id'=>$value['userid']])->getField('invite_code');
                    
                    break;

                default:
                    break;
            }
            M('remittance')->where(['id'=>$value['id']])->save(['invite_code'=>$invite_code]);
        }
    }
}
