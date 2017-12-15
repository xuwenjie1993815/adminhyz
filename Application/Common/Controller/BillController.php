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
class BillController extends \Think\Controller
{
    /**
     * @name 流水写入
     * @param $userid  用户ID
     * @param $money   流水资金；单位：分；（收入为正数；支出为负数）
     * @param $tradetypes 流水类型；（文字说明，如：快车服务；货运服务；推广奖励；平台奖励；现金提现）
     * @param $usertypes  用户类型：1：司机；2：用户；3：租赁公司
     * @remark array('errCode'=>0,'msg'=>'success')  ,errCode 为0表示无错误；其它都是错误
     */
    static function write($userid, $money, $tradetypes = '快车服务', $usertypes = 1)
    {

        if (empty($userid) || empty($money)) {
            return array('errCode' => 10003, 'msg' => '参数错误');
        }
        try {
            $data = array(
                'usertypes' => $usertypes,
                'userid' => $userid,
                'tradetypes' => $tradetypes,
                'money' => (int)$money
            );
            if (M('bill')->add($data)) {
                return array('errCode' => 0, 'msg' => 'success');
            } else {
                return array('errCode' => 10002, 'msg' => '操作异常');
            }
        } catch (\Think\Exception $e) {
            return array('errCode' => 10002, 'msg' => '操作异常');
        }
    }

}
