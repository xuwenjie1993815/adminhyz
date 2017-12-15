<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2009 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: hn <461809251@qq.com>
// +----------------------------------------------------------------------

/**
 * 短信
 */
class Sms
{
    const userid = '12';
    const account = 'AB00287';
    const password = 'AB0028760';
    const sign = '【异城快车】';
    const send_url = 'http://dx.ipyy.net/smsJson.aspx';
    const status_url = 'http://dx.ipyy.net/statusJsonApi.aspx';
    const getsx_url = 'http://dx.ipyy.net/callJsonApi.aspx';
    const getbalance_url = 'http://dx.ipyy.net/smsJson.aspx';

    /**
     * 发送短信
     * @param string $url		请求地址
     * @param int 	 $userid	企业ID
     * @param string $account	接口商账号
     * @param string $password  接口商密码
     * @param string $mobile	手机号码
     * @param string $content   发送内容
     * @param string $sendTime  发送时间
     * @param string $action    任务命令
     * @param string $extno		扩展字号
     * @return array
     */
    public function send($phone, $content, $sendTime='', $action='send', $extno='', $userid=self::userid, $account=self::account, $password=self::password, $url=self::send_url, $sign=self::sign)
    {
        // 构造参数，发送请求
        $data['mobile'] = $phone;
        $data['content'] = $sign.$content;
        $data['action'] = $action;
        $data['account'] = $account;
        $data['password'] =  strtoupper(md5($password));
        $result = curlFunc($url, $data); unset($data);

        $result['phone'] = $phone;
        $result['content'] = $sign.$content;
        if($result['returnstatus'] == 'Success')
        {
            $result['send_status'] = 1;
        }
        else
        {
            $result['send_status'] = 0;
        }
        return $result;
    }




    /**
     * 状态查询
     * @param string $url		请求地址
     * @param int 	 $userid	企业ID
     * @param string $account	接口商账号
     * @param string $password  接口商密码
     * @param string $action    任务命令
     * @param array
     */
    public function getStatus($statusNum = '', $taskid = '', $url=self::status_url, $action='query', $userid=self::userid, $account=self::account, $password=self::password)
    {
        $post_data['userid'] = $userid;
        $post_data['account'] = $account;
        $post_data['password'] = strtoupper(md5($password));
        $post_data['action'] = $action;
        $result = curlFunc($url, $post_data); unset($data);
        return $result;
    }


    /**
     * 上行查询
     * @param string $url		请求地址
     * @param int 	 $userid	企业ID
     * @param string $account	接口商账号
     * @param string $password  接口商密码
     * @param string $action    任务命令
     * @param array
     */
    public function getSx( $url=self::getsx_url, $action='query', $userid=self::userid, $account=self::account, $password=self::password)
    {
        $post_data['userid'] = $userid;
        $post_data['account'] = $account;
        $post_data['password'] = strtoupper(md5($password));
        $post_data['action'] = $action;
        $result = curlFunc($url, $post_data); unset($data);
        return $result;
    }


    /**
     * 余额查询
     * @param string $url		请求地址
     * @param int 	 $userid	企业ID
     * @param string $account	接口商账号
     * @param string $password  接口商密码
     * @param string $action    任务命令
     * @param array
     */
    public function getBalance($action='overage', $url=self::getbalance_url, $userid=self::userid, $account=self::account, $password=self::password)
    {
        $post_data['userid'] = $userid;
        $post_data['account'] = $account;
        $post_data['password'] = strtoupper(md5($password));
        $post_data['action'] = 'overage';
        $result = curlFunc($url, $post_data);
        return $result;
    }
}