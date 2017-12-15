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
 * Description of BillController
 * @author Chengwei Wang
 * @datetime  2017-3-14 9:57:08
 */
class BillController extends \Think\Controller {

    /**
     * @name 流水写入
     * @param $userid  用户ID
     * @param $money   流水资金；单位：分；（收入为正数；支出为负数）
     * @param $tradetypes 流水类型；（文字说明，如：快车服务；货运服务；推广奖励；分销奖励；现金提现）
     * @param $usertypes  用户类型：1：司机；2：用户；3：租赁公司,4:司机乘客信息均有，5：平台收入
     * @param $order_sn  订单号
     * @remark array('errCode'=>0,'msg'=>'success')  ,errCode 为0表示无错误；其它都是错误
     */
    public static function write($userid, $money, $tradetypes = '快车服务', $usertypes = 1, $order_sn, $invite_info_code) {
        if (empty($userid) || empty($money)) {
            return array('errCode' => 10003, 'msg' => '参数错误');
        }


        try {

            $data['usertypes'] = $usertypes;
            $data['userid'] = $userid;
            $data['tradetypes'] = $tradetypes;
            $data['money'] = round($money, 4);
            $data['order_sn'] = $order_sn;
            $data['invite_code'] = $invite_info_code;


            file_put_contents('bill.log', var_export($data, true) . '--' . '流水信息-' . "\r\n", FILE_APPEND);
            file_put_contents('bill.log', var_export(1111, true) . '--' . '流水信息000-' . "\r\n", FILE_APPEND);

            $result = M('bill')->data($data)->add();
            file_put_contents('bill.log', var_export(2222, true) . '--' . '流水信息2222-' . "\r\n", FILE_APPEND);

            file_put_contents('bill.log', var_export($result, true) . '--' . '流水信息3333-' . "\r\n", FILE_APPEND);
            if ($result) {
                return array('errCode' => 0, 'msg' => 'success');
            } else {
                return array('errCode' => 10002, 'msg' => '操作异常2');
            }
        } catch (\Think\Exception $e) {
            return array('errCode' => 10002, 'msg' => '操作异常1');
        }
    }

    /**
     * @name 流水写入
     * @param $userid  用户ID
     * @param $money   流水资金；单位：分；（收入为正数；支出为负数）
     * @param $tradetypes 流水类型；（文字说明，如：快车服务；货运服务；推广奖励；分销奖励；现金提现）
     * @param $usertypes  用户类型：1：司机；2：用户；3：租赁公司,4:司机乘客信息均有
     * @param $order_sn  订单号
     * @remark array('errCode'=>0,'msg'=>'success')  ,errCode 为0表示无错误；其它都是错误
     */
    static public function platform($money, $tradetypes = '平台收入', $order_sn) {

        file_put_contents('platform.log', var_export($money, true) . '--' . '平台流水000-' . "\r\n", FILE_APPEND);

        if (empty($money)) {
            return array('errCode' => 10003, 'msg' => '参数错误');
        }
        try {
            $data['tradetypes'] = $tradetypes;
            $data['money'] = round($money, 4);
            $data['order_sn'] = $order_sn;

            file_put_contents('platform.log', var_export($data, true) . '--' . '平台流水111-' . "\r\n", FILE_APPEND);

            if (M('platform')->add($data)) {
                return array('errCode' => 0, 'msg' => 'success');
            } else {
                return array('errCode' => 10002, 'msg' => '操作异常');
            }
        } catch (\Think\Exception $e) {
            return array('errCode' => 10002, 'msg' => '操作异常');
        }
    }

    public static function platform_old($money, $tradetypes = '平台收入', $order_sn) {

        if (empty($money)) {
            return array('errCode' => 10003, 'msg' => '参数错误');
        }
        try {
            $data = array(
                'tradetypes' => $tradetypes,
                'money' => floatval($money),
                'order_sn' => $order_sn,
            );
            if (M('platform')->add($data)) {
                return array('errCode' => 0, 'msg' => 'success');
            } else {
                return array('errCode' => 10002, 'msg' => '操作异常');
            }
        } catch (\Think\Exception $e) {
            return array('errCode' => 10002, 'msg' => '操作异常');
        }
    }

}
