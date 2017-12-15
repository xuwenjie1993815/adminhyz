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
 * Description of WechatController
 * @author Chengwei Wang
 * @datetime  2017-3-9 15:00:08
 */
class RefundController extends \Think\Controller{
    
    /**
     * @name 支付退款
     * @param $out_trade_no 本地订单号
     * @param $types  1:车；2：物
     * @param $refundmoney  退款金额 ，单位分
     * @return  true ：退款成功；  其他：不成功   
     */
    
    public function refund($out_trade_no,$types=1,$refundmoney=null,$userid=0){

        
        $integralPay=M('pay')->where(array('order_sn'=>$out_trade_no,'order_type'=>$types,'state'=>1,'payment'=>'integral'))->field('paycode,money,payment')->find();
        
        if(!empty($integralPay)){
 
             $remark='取消订单：'.$out_trade_no.',退还积分'.$integralPay['money'];
             \Api\Controller\IntegralController::log($userid, $integralPay['money'], $remark,1);
        }
        
        $pay=M('pay')->where(array('order_sn'=>$out_trade_no,'order_type'=>$types,'state'=>1,'payment'=>array('neq','integral')))->field('paycode,money,payment')->find();

        if(empty($pay)){
            return array(
                'state'=>true,
                'msg'=>'success'
            );
        }
        
        if(empty($refundmoney)){
            $refundmoney=$pay['money'];
        }
        
        if($refundmoney>$pay['money']){
            return array(
                'state'=>false,
                'msg'=>'退款金额不能大于支付金额'
            );
        }
        
        $data=array(
            'order_sn'=>$out_trade_no,
            'btypes'=>$types,
            'refundcode'=> guid(),
            'money'=>$refundmoney,
            'dealtime'=>date('Y-m-d H:i:s'),
            'state'=>0
        );
        $obj=M('refund');
        $refundID=$obj->add($data);
        if(!$refundID){
            return array(
                'state'=>false,
                'msg'=>'退款订单生产失败'
            );
        }
        
        if($pay['payment'] ==='wechat'){
            $refundRes= $this->_wechat_refund($data['refundcode'],$pay['paycode'],$refundmoney,$pay['money']);
        }elseif($pay['payment'] === 'alipay'){
            $refundRes= $this->_alipay_refund($pay['paycode'],$pay['money']);
        }
      
        if($refundRes['result'] === true){
             $obj->where(array('id'=>$refundID))->setField('state',1);
             return array(
                'state'=>true,
                'msg'=>'success'
            );
        }else{
            $obj->where(array('id'=>$refundID))->setField('remark',$refundRes['msg']);
            return array(
                'state'=>false,
                'msg'=>$refundRes['msg']
            );
        }

    }
    
    private function _wechat_refund($code,$out_trade_no,$refundmoney,$totalmoney)
    {
        vendor('Wepay.lib.WxPay#Api');
        $input = new \WxPayRefund();
        $input->SetOut_trade_no($out_trade_no);
        $input->SetTotal_fee($totalmoney);
        $input->SetRefund_fee($refundmoney);
        $input->SetOut_refund_no($code);
        $input->SetOp_user_id(\WxPayConfig::MCHID);
        
	    $result=\WxPayApi::refund($input);
        
        if($result['return_code'] === 'SUCCESS' && $result['result_code'] === 'SUCCESS'){
            return true;
        }else{
            $err='return_msg:'.$result['return_msg'].' ；err_code:'.$result['err_code'].' ;err_code_des：'.$result['err_code_des'];
            return $err;
        }
    }
    
    
    private function _alipay_refund($out_trade_no,$refundmoney){
        vendor('Alipay.aop.AopClient');
        $aop = new \AopClient ();
        $aop->gatewayUrl = C('alipay.gatewayUrl');
        $aop->appId = C('alipay.appId');
        $aop->rsaPrivateKey = C('alipay.rsaPrivateKey');
        $aop->alipayrsaPublicKey=C('alipay.alipayrsaPublicKey');
        $aop->apiVersion = C('alipay.apiVersion');
        $aop->signType = C('alipay.signType');
        $aop->format=C('alipay.format');
 
        vendor('Alipay.aop.request.AlipayTradeRefundRequest');
        $request = new \AlipayTradeRefundRequest ();
        
        $data=array(
            'out_trade_no'=>$out_trade_no,
            'refund_amount'=>round($refundmoney/100,2),
            'refund_reason'=>'正常退款'
        );
        $request->setBizContent(json_encode($data));
        $result = $aop->execute ( $request); 

        $responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
        $resultCode = $result->$responseNode->code;
        if(!empty($resultCode)&&$resultCode == 10000){
            return ['result'=>true, 'msg'=>'退款成功'];
        } else {
            $err='CODE:'.$result->$responseNode->code.'; msg:'.$result->$responseNode->msg;
            return ['result'=>false, 'msg'=>$err];
        }
    }
}
