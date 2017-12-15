<?php

namespace Common\Controller;

/**
 * Description of PayController
 *
 * @author Chengwei Wang
 */
class PayController extends \Think\Controller{
    
    /**
     * @name 支付
     * @param $orders  支付内容 【type：订单类型 1-车/2-货；order_sn:订单编号；money:支付金额（单位分）】
     * @param $payment 支付平台类型
     * @retun 
     */
    public function pay($orders,$payment = 'wechat',$useintegral=0)
    {
        file_put_contents('pay.log', var_export($orders,true) . '--' .'payorder支付内容-'."\r\n", FILE_APPEND);

        if($payment !== 'wechat' && $payment !== 'alipay'){
            return array('errCode'=>40004,'msg'=>'不存在的支付类型');
        }
        
        if(empty($orders['order_sn']) || empty($orders['type']) || empty($orders['money'])){
            return array('errCode'=>40004,'msg'=>'订单参数错误','order'=>$orders);
        }
        
        if($this->if_pay($orders['order_sn'], $orders['type'])){
            return array('errCode'=>20001,'msg'=>'此订单已支付');
        }
        
        
        
        
        //当积分数大于订单金额时
        $orderModel=D('order');
        $orderModel->startTrans();
        try{
            if($useintegral){

                $configOBJ= new \Api\Controller\ApiController();
                $config=$configOBJ->read_config();
                
                $integralOBJ=new \Api\Controller\IntegralController();
                $integral=$integralOBJ->get_user_c_integral($orders['uid']);
        
                if($integral['deduction']*100 > $orders['money']){
                    $integralPayMoney=$orders['money'] ;
                    $dedutInte= (int)ceil($orders['money']*$config['c_integral']/100);
                }else{
                    $integralPayMoney=$integral['deduction']*100;
                    $dedutInte=$integral['integral'];
                }
                $orders['money']-=$integralPayMoney;  //定义还需要支付的金额
                $payInteOrder=$this->create(array('type'=>$orders['type'],'order_sn'=>$orders['order_sn'],'money'=>$dedutInte),'integral'); //创建积分支付流水
                
                $remark='支付订单：'.$orders['order_sn'].',消费'.$dedutInte.'积分';
                \Api\Controller\IntegralController::log($orders['uid'], $dedutInte, $remark);

                if(false === $payInteOrder){
                    $orderModel->rollback();
                    return array('errCode'=>40001,'msg'=>'订单创建失败1');
                }
            }
            if($orders['money'] > 0){
                $payOrder=$this->create($orders,$payment);
                
                file_put_contents('pay.log', var_export($payOrder,true) . '--' .'pay支付订单信息-'."\r\n", FILE_APPEND);
                file_put_contents('pay.log', var_export($payment,true) . '--' .'pay支付订单方式-'."\r\n", FILE_APPEND);
                
                //\Think\Log::record("errorlog :".  var_export($payment,true));
                if($payOrder === false){
                    $orderModel->rollback();
                    return array('errCode'=>40001,'msg'=>'订单创建失败2');
                }
                $orderModel->commit();
                if($payment === 'wechat'){
                    $end = $this->wechat($payOrder);
                    
                    file_put_contents('pay.log', var_export($end,true) . '--' .'paywechat支付结果-'."\r\n", FILE_APPEND);
                    
                    return $end;
                }else{
                    $end_alipay = $this->alipay($payOrder);
                    
                    file_put_contents('pay.log', var_export($end_alipay,true) . '--' .'payalipay支付结果-'."\r\n", FILE_APPEND);
                    
                    return $end_alipay;
                }
            }else{
                
                file_put_contents('pay.log', var_export($orders['money'],true) . '--' .'payorder支付金额-'."\r\n", FILE_APPEND);
                
                if($orders['type'] === 1){
                    $order_info['pay_mothod'] = 4;
                    $order_info['status'] = 1;
                    M('order')->where(array('order_sn'=>$orders['order_sn']))->save($order_info);
                }elseif($orders['type'] === 4){
                    M('vipcar')->where(array('order_sn'=>$orders['order_sn']))->save(array('state'=>2,'pay'=>1));
                }else{
                    M('express')->where(array('order_sn'=>$orders['order_sn']))->save(array('state'=>1));
                }
                M('pay')->where(array('paycode'=>$payInteOrder['paycode']))->save(array('pay_time'=>date('Y-m-d H:i:s'),'state'=>1));
                $orderModel->commit();
                return array('errCode'=>0,'msg'=>'ok','data'=>array('integral'=>1));
            } 
            
        } catch (\Think\Exception $e){
            $orderModel->rollback();
            return array('errCode'=>40001,'msg'=>'订单创建失败3');
        }
    }
    

    /**
     * @name 生成支付订单
     */
    private function create($order,$payment){
        $model=M('pay');
        $data=array(
            'paycode'=>guid(),
            'order_type'=>$order['type'],
            'order_sn'=>$order['order_sn'],
            'money'=>$order['money'],
            'create_time'=>date('Y-m-d H:i:s'),
            'payment'=>$payment
        );
        if($model->add($data)){
            return $data;
        }else{
            return false;
        }
    }
    
    /**
     * @name 微信支付
     */
    private function wechat($orders){

        vendor('Wepay.lib.WxPay#Api');
        //②、统一下单
        $input = new \WxPayUnifiedOrder();
        $input->SetBody("异城快车-在线支付");
        $input->SetOut_trade_no($orders['paycode']);
        $input->SetAttach($orders['order_type'].'----'.$orders['order_sn']);
        $input->SetTotal_fee($orders['money']);
        $input->SetTime_start(date("YmdHis"));
        $input->SetTime_expire(date("YmdHis", time() + 600));
        $input->SetGoods_tag($orders['order_type'] === 1 ? "快车服务" : "货物托运" );
        $input->SetNotify_url(U('Home/notify/wechat','','html',true));
        $input->SetTrade_type("APP");
       //dump($input);exit;
        $order = \WxPayApi::unifiedOrder($input);
        //\Think\Log::record("order :".  var_export($order,true));
        if($order['result_code']==='SUCCESS' && $order['return_code']==='SUCCESS'){
            return array('errCode'=>0,'msg'=>'ok','data'=>array('prepay_id'=>$order['prepay_id']));
        }else{
            return array('errCode'=>40002,'msg'=>'创建订单失败');
        }
        
    }
    
    
    /**
     * @name 支付宝支付
     */
    private function alipay($orders){
     
        $data=array(
            'title'=>'异城快车出行订单',
            'code'=>$orders['paycode'],
            'money'=>round($orders['money']/100,2),
            'body'=> NULL,
            'notifyurl'=>U('Home/notify/alipay','','html',true)
        );
        return array('errCode'=>0,'msg'=>'ok','data'=>$data);
    }
    
    
    /**
     * @name 检查订单是否已经支付
     * @param $order_sn 订单编号
     * @param $type 订单类型  $type  1:路线订单；2：
     * @return  bool  true 已经支付，false：未支付
     */
    private function if_pay($order_sn,$type){
        if(empty($order_sn) || empty($type)){
            return array('errCode'=>40004,'msg'=>'缺少参数');
        }
        $where=array(
            'order_sn'=>$order_sn,
            'types'=>$type,
            'state'=>0
        );
        $model=D('Pay');
        $model->where(array('order_sn'=>$order_sn,'types'=>$type,'state'=>0))->delete(); //删除相应订单发起请求但未请求的数据
        $lists=$model->field('id,state')->where($where)->select();
        return empty($lists) ? false : true;
    }
    
    
    /**
     * @name 支付宝付款
     * @
     */
    public function alipay_payment()
    {
        vendor('Alipay.aop.AopClient');
        vendor('Alipay.aop.SignData');
        $aop = new \AopClient ();
        $aop->gatewayUrl = C('gatewayUrl');
        $aop->appId = C('appId');
        $aop->rsaPrivateKey = C('rsaPrivateKey');
        $aop->alipayrsaPublicKey=C('alipayrsaPublicKey');
        $aop->apiVersion = C('apiVersion');
        $aop->signType = C('signType');
        $aop->format=C('json');
        vendor('Alipay.aop.request.AlipayFundTransToaccountTransferRequest');
        $request = new \AlipayFundTransToaccountTransferRequest ();
        
        $content=array(
            'out_biz_no'=>date('YmdHis').rand(1000,9999),//转账单号
            'payee_type'=>'ALIPAY_LOGONID',
            'payee_account'=>'bfmxuc1114@sandbox.com',//收款人账号
            'amount'=>'0.01',
            'remark'=>'提现'
        );
        
        
        $request->setBizContent(json_encode($content));
       
        $result = $aop->execute ( $request); 
//        dump($result);
        exit();
        $responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
//        dump($responseNode);
        $resultCode = $result->$responseNode->code;
        
//        dump($resultCode);
        if(!empty($resultCode)&&$resultCode == 10000){
        echo "成功";
        } else {
        echo "失败";
        }
    }
}
