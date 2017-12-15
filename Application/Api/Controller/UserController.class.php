<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/1/10
 * Time: 14:34
 */

namespace Api\Controller;

use Home\Logic\DriverLogic;
use Think\Exception;
use Base\Logic\PubLogic;
use Home\Logic\UserLogic;

class UserController extends \Api\Controller\ApiController
{
    protected $model;

    /**
     * @name 用户订单
     */
    public function orders(){
        if($this->params['state'] === ''){
            $this->_responseData(apiFormat(40004, '缺少参数:state'));
        }
        if(empty($this->params['token'])){
            $this->_responseData(apiFormat(40004, '缺少参数:token'));
        }
        if(empty($this->params['page'])){
            $this->params['page']=1;
        }
        
        if(empty($this->params['limit'])){
            $this->params['limit']=10;
        }
        
        $user=$this->get_user_id($this->params['token']);
        if(empty($user['userid'])){
            $this->_responseData(40003, '无效的token，用户不存在');
        }
        $where=array(
            'user_id'=>$user['userid']
        );
        $state=(int)$this->params['state'];  //1：待支付；2：进行中；3：已完成
        if($state === 0){  //待支付
            $where['state']=0;
        }else if($state === 1){
            $where['state']=1;
        }else if($state === 2){
            $where['state']=2;
        }elseif($state === 3){
            $where['state']=3;
        }elseif($state===4){
            $where['state']=4;
        }elseif($state===5){
            $where['state']=array('in',array(1,4));
        }elseif($state === -1){
            $where['state']=-1;
        }else{
            $this->_responseData(apiFormat(40001, '参数错误：state'));
            
        }
        try{
            $orderModel=D('Order');
//            $field='id, order_sn, service_id, seat_num*price as money, drivername, carnum, startcity, startstation, purposecity, purposestation, createtime, headimg, brand';
            $count=$orderModel->where($where)->count();
            $data=$orderModel->where($where)->page($this->params['page'])
                    ->limit($this->params['limit'])->order('id desc')->select();
//            $data=$orderModel->where($where)->field($field)->page($this->params['page'])->limit($this->params['limit'])->order('createtime desc')->select();

            if(!empty($data))
            {
                $comment_model = M('comment');
                foreach($data as $key=>$val){
                    $data[$key]['time']=get_time_difference($val['createtime']);
//                    $data[$key]['headimg']=(((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://').I('server.SERVER_NAME').substr($val['headimg'], 1);
                    $data[$key]['headimg']= $val['headimg'] ? getPicUrl($val['headimg']) : getPicUrl('./Public/default/images/ic_default_avator.png');
                    $comment_num = $comment_model->where(array('order_id'=>$val['id'], 'service_id'=>$val['service_id']))->count();
                    $data[$key]['is_comment'] = $comment_num ? 1 : 0;

                    unset($data[$key]['user_id'], $data[$key]['pay_mothod'], $data[$key]['state'],$data[$key]['link_phone'],$data[$key]['is_yy']);
                    unset($data[$key]['price'], $data[$key]['block'], $data[$key]['status'],$data[$key]['departur_time'],$data[$key]['departur_day'], $data[$key]['type'], $data[$key]['departur_period']);
                }
            }
            $this->_responseData(apiFormat(0, 'success', array('items'=>$data,'count'=>$count)));
        } catch (\Exception $e){
            $this->_responseData(apiFormat(40004, '出错了，稍后再试'.$e->getMessage()));
        }  

    }
	/**
     * @name 用户订单
     */
    public function ordersAll(){
        if(empty($this->params['token'])){
            $this->_responseData(apiFormat(40004, '缺少参数:token'));
        }
        if(empty($this->params['page'])){
            $this->params['page']=1;
        }
        
        if(empty($this->params['limit'])){
            $this->params['limit']=10;
        }
        
        $user=$this->get_user_id($this->params['token']);
        if(empty($user['userid'])){
            $this->_responseData(40003, '无效的token，用户不存在');
        }
        $where=array(
            'user_id'=>$user['userid']
        );
        try{
            $orderModel=D('Order');
            $count=$orderModel->where($where)->count();
            $data=$orderModel->where($where)->page($this->params['page'])
                    ->limit($this->params['limit'])->order('id desc')->select();
			
			
            if(!empty($data))
            {
                $comment_model = M('comment');
                foreach($data as $key=>$val){
                    switch ($val['state']) {
                        case 1:
                            $data[$key]['state'] = '进行中';

                            break;
                        case 2:
                            $data[$key]['state'] = '待发车';

                            break;
                        case 3:
                            $data[$key]['state'] = '已完成';

                            break;
                        case 4:
                            $data[$key]['state'] = '待支付';

                            break;
                        case -1:
                            $data[$key]['state'] = '已取消';

                            break;
						case -2:
                            $data[$key]['state'] = '被拒绝';

                            break;
                        case 0:
                            $data[$key]['state'] = '待确认';

                            break;

                        default:
                            break;
                    }
                    $data[$key]['time']=get_time_difference($val['createtime']);
                    $data[$key]['headimg']= $val['headimg'] ? getPicUrl($val['headimg']) : getPicUrl('./Public/default/images/ic_default_avator.png');
                    $comment_num = $comment_model->where(array('order_id'=>$val['id'], 'service_id'=>$val['service_id']))->count();
                    $data[$key]['is_comment'] = $comment_num ? 1 : 0;
                    

                    unset($data[$key]['user_id'], $data[$key]['pay_mothod'],$data[$key]['link_phone'],$data[$key]['is_yy']);
                    unset($data[$key]['price'], $data[$key]['block'], $data[$key]['status'],$data[$key]['departur_time'],$data[$key]['departur_day'], $data[$key]['type'], $data[$key]['departur_period']);
                }
            }
            $this->_responseData(apiFormat(0, 'success', array('items'=>$data,'count'=>$count)));
        } catch (\Exception $e){
            $this->_responseData(apiFormat(40004, '出错了，稍后再试'.$e->getMessage()));
        }  

    }
	/**
     * 注册
     */
    public function regNew()
    {
        try
        {
            $model = M();
            
            $model->startTrans();
            
            //1. 校对验证码
//            PubLogic::checkMobileCode($this->params['mobile'], $this->params['code'], 2);
            //2. 添加用户
            $user_id = UserLogic::addUserNew($this->params['invite_name'],$this->params['cart_id'],$this->params['mobile'], $this->params['pwd'], $this->params['invite_code']);
				
            //4. 获取用户信息
            $info = UserLogic::getUserInfo($user_id);
            
            $model->commit();

            $this->_responseData(apiFormat(0, '注册成功', $info));
        }

        catch (\Exception $e)
        {
            $model->rollback();
            
            $this->_responseData(apiFormat(5001, $e->getMessage()));
        }
    }
    /**
     * 注册
     */
    public function reg()
    {
        try
        {
            $model = M();
            
            $model->startTrans();
            
            //1. 校对验证码
//            PubLogic::checkMobileCode($this->params['mobile'], $this->params['code'], 2);
            //2. 添加用户
            $user_id = UserLogic::addUser($this->params['invite_name'],$this->params['cart_id'],$this->params['mobile'], $this->params['pwd'], $this->params['invite_code']);

            //4. 获取用户信息
            $info = UserLogic::getUserInfo($user_id);
            
            $model->commit();

            $this->_responseData(apiFormat(0, '注册成功', $info));
        }

        catch (\Exception $e)
        {
            $model->rollback();
            
            $this->_responseData(apiFormat(5001, $e->getMessage()));
        }
    }


    /**
     * 登录
     */
    public function loginByPwd()
    {
        try
        {
            //1. 验证登录信息
            $user_id = UserLogic::login(1, $this->params['mobile'], $this->params['pwd']);

            //2. 保存登录信息
            UserLogic::SaveLoginInfo($user_id, $this->params['mobile'], $this->params['registration_id']);

            //3. 获取用户信息
            $info = UserLogic::getUserInfo($user_id);

            $this->_responseData(apiFormat(0, '登录成功', $info));
        }

        catch (\Exception $e)
        {
            $this->_responseData(apiFormat(5002, $e->getMessage()));
        }
    }

    /**
     * 快捷登录（短信验证码）
     */
    public function loginByCode()
    {
        try
        {
            //1. 验证登录信息
            $user_id = UserLogic::login(2, $this->params['mobile'], $this->params['code'], $this->params['registration_id']);

            //2. 保存登录信息
            UserLogic::SaveLoginInfo($user_id, $this->params['mobile'], $this->params['registration_id']);

            //3. 获取用户信息
            $info = UserLogic::getUserInfo($user_id);

            $this->_responseData(apiFormat(0, '登录成功', $info));
        }

        catch (\Exception $e)
        {
            $this->_responseData(apiFormat(5002, $e->getMessage()));
        }
    }



    /**
     * 密码重置
     */
    public function resetPwd()
    {
        try
        {
            //1. 获取司机信息
            $user_id = UserLogic::login(2, $this->params['mobile'], $this->params['code']);

            //2. 修改密码
            UserLogic::updateField($user_id, 'pwd', $this->params['n_pwd']);

            $this->_responseData(apiFormat(0, '密码重置成功'));
        }

        catch (\Exception $e)
        {
            $this->_responseData(apiFormat(5005, $e->getMessage()));
        }
    }


    /**
     * 密码修改
     */
    public function updatePwd()
    {
        try
        {
            $user_model = M('user');

            $user_model->startTrans();

            //1. 验证token
            list($user_id, $mobile, $login_time) = PubLogic::checkToken($this->params['token'], 2);

            //2. 验证原始密码
            UserLogic::login(1, $mobile, $this->params['s_pwd']);

            //3. 修改信息
            UserLogic::updateField($user_id, 'pwd', $this->params['n_pwd']);

            //4. 清空登录口令
            if(!$user_model->where(array('id'=>$user_id))->setField('token', '')) throw new Exception('系统繁忙');

            $user_model->commit();

            $this->_responseData(apiFormat(0, '密码修改成功'));
        }

        catch (\Exception $e)
        {
            $user_model->rollback();

            $this->_responseData(apiFormat(5004, $e->getMessage()));
        }
    }



    /**
     * 个人信息修改
     */
    public function editInfo()
    {
        try
        {
            //1. 验证token
            list($user_id, $mobile, $login_time) = PubLogic::checkToken($this->params['token'], 2);

            //2. 修改字段
            UserLogic::updateField($user_id, $this->params['key'], $this->params['val']);

            $this->_responseData(apiFormat(0, '修改成功'));
        }

        catch (\Exception $e)
        {
            $this->_responseData(apiFormat(5003, $e->getMessage()));
        }
    }


    /**
     * 修改头像
     */
    public function editHeadPic()
    {
        try
        {
            //1. 验证token
            list($user_id, $mobile, $login_time) = PubLogic::checkToken($this->params['token'], 2);

            file_put_contents('text.txt', $this->params['head_pic']);

            //2. 修改字段
            UserLogic::updateField($user_id, 'head_pic', $this->params['head_pic']);

            //3. 获取头像地址
            $head_pic = M('user')->where(array('id'=>$user_id))->getField('head_pic');
            
            $head_pic = $head_pic ? getPicUrl($head_pic) : '';

            $this->_responseData(apiFormat(0, '修改成功', array('head_pic'=>$head_pic)));
        }

        catch (\Exception $e)
        {
            $this->_responseData(apiFormat(5006, $e->getMessage()));
        }
    }
    /**
     * 乘客手机修改
     */
    public function updateMobile()
    {
        try
        {
            $data = $this->params;

            //1. 验证token
            list($data['user_id'], $mobile, $flag) = PubLogic::checkToken($data['token'], 2);
			

            //2. 验证短信验证码
            PubLogic::checkMobileCode($this->params['mobile'], $this->params['code'],2);

            //3. 修改绑定手机
            UserLogic::updateMobile($data['user_id'], $this->params['mobile'],$this->params['pwd']);

            $this->_responseData(apiFormat(0, '手机修改成功,请从新登录'));
        }

        catch (\Exception $e)
        {
            $this->_responseData(apiFormat(1006, $e->getMessage()));
        }
    }

    function __destruct()
    {
        unset($this->model);
    }

}