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

/**
 * Description of Notify
 * @author Chengwei Wang
 * @datetime  2017-3-9 12:07:14
 */
require_once 'WxPay.Notify.php';
class Notify extends WxPayNotify{
    //查询订单
	public function Queryorder($transaction_id)
	{
		$input = new WxPayOrderQuery();
		$input->SetTransaction_id($transaction_id);
		$result = WxPayApi::orderQuery($input);

		if(array_key_exists("return_code", $result)
			&& array_key_exists("result_code", $result)
			&& $result["return_code"] == "SUCCESS"
			&& $result["result_code"] == "SUCCESS")
		{
			return true;
		}
		return false;
	}
	
	//重写回调处理函数
	public function NotifyProcess($data, &$msg)
	{
		$notfiyOutput = array();
		
		if(!array_key_exists("transaction_id", $data)){
			$msg = "输入参数不正确";
			return false;
		}
		//查询订单，判断订单真实性
		if(!$this->Queryorder($data["transaction_id"])){
			$msg = "订单查询失败";
			return false;
		}
		return true;
	}
}
