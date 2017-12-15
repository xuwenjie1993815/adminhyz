<?php

// +----------------------------------------------------------------------
// | Think.Admin
// +----------------------------------------------------------------------
// | 重庆瀚乐思信息技术有限公司   重庆市渝北区金开大道106号互联网产业园3栋407
// +----------------------------------------------------------------------
// | 官方网站: http://www.highnes.com
// +----------------------------------------------------------------------
// | 联系人  Chengwei Wang     
// +----------------------------------------------------------------------
// | 电子邮箱   617090255@qq.com
// +----------------------------------------------------------------------

namespace Common\Controller;

/**
 * Description of RemittanceController
 * @author Chengwei Wang
 * @datetime  2017-3-17 20:04:46
 */
class RemittanceController extends \Think\Controller{
    
    private $userModel;

	public function remittance($userID,$userType,$money){
        $model = M('remittance');
        
        $model->startTrans();
        
        $checkRes=$this->_check($userID, $userType, $money);
        file_put_contents('remittance.log', var_export($checkRes, true) . '--' . '0000222-' . "\r\n", FILE_APPEND);
        if(0 !== $checkRes['errCode']){
            $model->rollback();
            return $checkRes;
        }
        
        switch ($userType) {
            case 1://user:
                $info = M("user")->where(['id'=>$userID])->field('invite_code,mobile,invite_code_old,alipay')->find();
                break;
            case 2://driver:
                $info = M("driver")->where(['id'=>$userID])->field('invite_code,mobile,invite_code_old,alipay')->find();
                break;
            default:
                return false;
                break;
        }
        $data = array(
            'order_sn'=> guid(),
            'userid'=>$userID,
            'usertype'=>$userType,
            'account'=>$checkRes['alipay'],
            'money'=>$money,
            'createtime'=>date('Y-m-d H:i:s',time()),
            'invite_code' => $info['invite_code'],
        );
        file_put_contents('remittance.log', var_export($data, true) . '--' . '0000111-' . "\r\n", FILE_APPEND);
        $id = $model->data($data)->add();
        file_put_contents('remittance.log', var_export($id, true) . '--' . '0000333-' . "\r\n", FILE_APPEND);
        if(!$id){
            $model->rollback();
            return array('errCode'=>40004,'msg'=>'创建退款订单失败！');
        }
        
        $call=$this->money($data['order_sn'],$checkRes['alipay'],$money);
        
        file_put_contents('remittance.log', var_export($call, true) . '--' . '0000-' . "\r\n", FILE_APPEND);
        
        if(0 === $call['errCode']){
            
            if(!$model->where(array('id'=>$id))->save(array('overtime'=>date('Y-m-d H:i:s'),'state'=>1,'remark'=>'打款完成'))){
                $model->rollback();
                file_put_contents('remittance.log', var_export('编辑退款订单失败', true) . '--' . '1111-' . "\r\n", FILE_APPEND);
                return array('errCode'=>40004,'msg'=>'编辑退款订单失败！');
            }
            
            $table=($userType===1 ? 'user' :'driver');
            $this->userModel=M("$table");
            
            //$info = $this->userModel->where(['id'=>$userID])->field('invite_code,mobile,invite_code_old')->find();
            $driver_info = M("driver")->where(['invite_code'=>$info['invite_code']])->field('invite_code,mobile,invite_code_old')->find();
            $user_info = M("user")->where(['invite_code'=>$info['invite_code']])->field('invite_code,mobile,invite_code_old')->find();
            
            file_put_contents('remittance.log', var_export($driver_info, true) . '--' . '222-' . "\r\n", FILE_APPEND);
            file_put_contents('remittance.log', var_export($user_info, true) . '--' . '3-' . "\r\n", FILE_APPEND);
            
            if(empty($driver_info) && !empty($user_info)){
                if(!M("user")->where(array('invite_code'=>$user_info['invite_code']))->setInc('spend',$money)){
                    $model->rollback();
                }
                BillController::write($userID, 0-$money*100,'提现',$userType,$data['order_sn'],$user_info['invite_code']);
            }else if(!empty($driver_info) && empty($user_info)){
                if(!M("driver")->where(array('invite_code'=>$driver_info['invite_code']))->setInc('spend',$money)){
                    $model->rollback();
                }
                BillController::write($userID, 0-$money*100,'提现',$userType,$data['order_sn'],$driver_info['invite_code']);
            }else{
                if(!M("user")->where(array('invite_code'=>$driver_info['invite_code']))->setInc('spend',$money)){
                    $model->rollback();
                }
                if(!M("driver")->where(array('invite_code'=>$driver_info['invite_code']))->setInc('spend',$money)){
                    $model->rollback();
                }
                BillController::write($userID, 0-$money*100,'提现',4,$data['order_sn'],$driver_info['invite_code']);
            }
            file_put_contents('remittance.log', var_export('111', true) . '--' . '333333-' . "\r\n", FILE_APPEND);
            
            $model->commit();
        }
        return $call;
        
    }
    public function remittance_old($userID,$userType,$money){
        
        $checkRes=$this->_check($userID, $userType, $money);
        if(0 !== $checkRes['errCode']){
            return $checkRes;
        }
        
        $orders=$this->createOrder($userID, $userType, $checkRes['alipay'], $money);
        if(false === $orders){
            return array('errCode'=>40004,'msg'=>'创建退款订单失败！');
        }
        
        $call=$this->money($orders['order_sn'],$checkRes['alipay'],$money);
        file_put_contents('remittance.log', var_export($call, true) . '--' . '0000-' . "\r\n", FILE_APPEND);
        $model = M('remittance');
        if(0 === $call['errCode']){
            $model->startTrans();
            
            if(!$model->where(array('id'=>$orders['id']))->save(array('overtime'=>date('Y-m-d H:i:s'),'state'=>1,'remark'=>'打款完成'))){
                $model->rollback();
				file_put_contents('remittance.log', var_export('编辑退款订单失败', true) . '--' . '1111-' . "\r\n", FILE_APPEND);
                return array('errCode'=>40004,'msg'=>'编辑退款订单失败！');
            }
            
            $table=($userType===1 ? 'user' :'driver');
            $this->userModel=M("$table");
            
            $info = $this->userModel->where(['id'=>$userID])->field('invite_code,mobile,invite_code_old')->find();
            $driver_info = M("driver")->where(['invite_code'=>$info['invite_code']])->field('invite_code,mobile,invite_code_old')->find();
            $user_info = M("user")->where(['invite_code'=>$info['invite_code']])->field('invite_code,mobile,invite_code_old')->find();
            
            
            file_put_contents('remittance.log', var_export($driver_info, true) . '--' . '222-' . "\r\n", FILE_APPEND);
            file_put_contents('remittance.log', var_export($user_info, true) . '--' . '3-' . "\r\n", FILE_APPEND);
            
            if(empty($driver_info) && !empty($user_info)){
                if(!M("user")->where(array('id'=>$userID))->setInc('spend',$money)){
                    $model->rollback();
                }
                BillController::write($userID, 0-$money,'提现',$userType,$orders['order_sn'],$user_info['invite_code']);
            }else if(!empty($driver_info) && empty($user_info)){
                if(!M("driver")->where(array('id'=>$userID))->setInc('spend',$money)){
                    $model->rollback();
                }
                BillController::write($userID, 0-$money,'提现',$userType,$orders['order_sn'],$driver_info['invite_code']);
            }else{
                if(!M("user")->where(array('id'=>$userID))->setInc('spend',$money)){
                    $model->rollback();
                }
                if(!M("driver")->where(array('id'=>$userID))->setInc('spend',$money)){
                    $model->rollback();
                }
                BillController::write($userID, 0-$money,'提现',4,$orders['order_sn'],$driver_info['invite_code']);
            }
            
            $model->commit();
        }
        return $call;
        
    }
    /**
     * @name 打款
     */
    private function money($order_sn,$account,$money){
        vendor('Alipay.aop.AopClient');
        $aop = new \AopClient ();
        $aop->gatewayUrl = C('alipay.gatewayUrl');
        $aop->appId = C('alipay.appId');
        $aop->rsaPrivateKey = C('alipay.rsaPrivateKey');
        $aop->alipayrsaPublicKey=C('alipay.alipayrsaPublicKey');
        $aop->apiVersion = C('alipay.apiVersion');
        $aop->signType = C('alipay.signType');
        $aop->format=C('alipay.format');
        
        vendor('Alipay.aop.request.AlipayFundTransToaccountTransferRequest');
        $request = new \AlipayFundTransToaccountTransferRequest ();
        $data=array(
            'out_biz_no'=>$order_sn,
            'payee_type'=>'ALIPAY_LOGONID',
            'payee_account'=>$account,
            'amount'=>$money
        );
        $request->setBizContent(json_encode($data));
        $result = $aop->execute ( $request); 

        $responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
        $resultCode = $result->$responseNode->code;

        if(!empty($resultCode)&&$resultCode == 10000){
            return array('errCode'=>0,'msg'=>'打款成功','data'=>array('money'=>$money));
        } else {
            return array('errCode'=>40004,'msg'=>$result->$responseNode->sub_msg,'data'=>array('money'=>$money));
        }
    }




    /**
     * @name 创建打款订单
     */
    private function createOrder($userID,$userType,$account,$money){
        switch ($userType) {
            case 1://user:

                $info = M("user")->where(['id'=>$userID])->field('invite_code,mobile,invite_code_old')->find();
                break;
            case 2://driver:
                $info = M("driver")->where(['id'=>$userID])->field('invite_code,mobile,invite_code_old')->find();

                break;

            default:
                return false;
                break;
        }
        
        $data=array(
            'order_sn'=> guid(),
            'userid'=>$userID,
            'usertype'=>$userType,
            'account'=>$account,
            'money'=>$money,
            'createtime'=>date('Y-m-d H:i:s',time()),
            'invite_code' => $info['invite_code'],
        );
        $obj=D('remittance');
        try{
            
            $obj->startTrans();
            $insertId=$obj->add($data);
//            $this->userModel->where(array('id'=>$userID))->setInc('spend',$money);
            $obj->commit();
            return array('id'=>$insertId,'order_sn'=>$data['order_sn']);
        } catch (Think\Exception $e){
            $obj->rollback();
            return false;
        }
        
    }
    
    /**
     * @name 检查提现金额
     */
    private function _check($userID,$userType,$money){
        if($money<0.1){
            return array('errCode'=>10004,'msg'=>'参数错误[money]');
        }
        $table=($userType===1 ? 'user' :'driver');
        $this->userModel=M("$table");

        $find = $this->userModel->where(array('id'=>$userID))->field('(income-spend) as balance,alipay')->find();//(income-spend)收入-支出
        
        $balance=$find['balance'];
        
        file_put_contents('remittance.log', var_export($find, true) . '--' . '0000-' . "\r\n", FILE_APPEND);
        file_put_contents('remittance.log', var_export($money, true) . '--' . '1111-' . "\r\n", FILE_APPEND);
        
        if($balance<$money){
            return array('errCode'=>10004,'msg'=>'提现金额不能大于账户金额');
        }elseif(empty($find['alipay'])){
            return array('errCode'=>10004,'msg'=>'您还没有设置支付宝账号');
        }else{
            return array('errCode'=>0,'alipay'=>$find['alipay']);
        }
        
    }
}