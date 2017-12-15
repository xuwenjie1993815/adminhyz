<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/1/3
 * Time: 17:02
 */

namespace Home\Controller;
use Home\Logic\UserLogic;
use Think\Exception;
use Base\Logic\PubLogic;
use Home\Logic\InviteLogic;

class UserController extends \Base\Controller\BaseController
{
    /**
     * 乘客列表信息
     */
    public function listView()
    {
        if(IS_GET)
        {
            $mobile = I('mobile', '');
            $status = I('status');
            $start_time = I('start_time');
            $end_time = I('end_time' ,date('Y-m-d H:i:s',time()));
            $map = array();
            
            if(!empty($mobile)) {
                $map['mobile|invite_name'] = ['like',"%$mobile%"];
            }
            
            if($status == ''){
                
            }else{
                $map['status'] = $status;
            }
            
            if(empty($start_time) && !empty($end_time)){
                $map['create_time'] = ['ELT',  strtotime($end_time)];
            }else if(empty ($end_time) && !empty ($start_time)){
                $map['create_time'] = ['EGT',  strtotime($start_time)];
            }else if(!empty ($start_time) && !empty ($end_time)){
                $map['create_time'] = ['between',[strtotime($start_time),  strtotime($end_time)]];
            }
            //1. 获取乘客列表信息
            list($data['list'], $data['fpage']) = PubLogic::getListDataByPage(M('user'), $map,'*','id desc');
            
            $data['url'] = U();
            $data['mobile'] = $mobile;
            $data['status'] = $status;
            
            $this->assign('start_time', $start_time);
            $this->assign('end_time', $end_time);
            $this->assign($data)->display();
        }
    }


    /**
     * 司机启用/停用
     */
    public function start()
    {
        try
        {
            $data['status'] = UserLogic::userUse(I('post.user_id/d', 0));

            $this->ajaxReturn(returnData(1, '操作成功', $data));
        }

        catch (\Exception $e)
        {
            $this->ajaxReturn(returnData(0, $e->getMessage()));
        }
    }
    
    
    /**
     * 乘客详情页面
     */
    public function info()
    {
        try
        {
            $user_id = I('user_id/d', 0);
            
            //1. 获取乘客信息
            $data['info'] = M('user')->where(array('id'=>$user_id))->find();
		
			$map['invite_code_first'] = ['in',[$data['info']['invite_code'],$data['info']['invite_code_old']]];
			
            //1. 邀请人信息（一级）
            $driver = M('invite')->where($map)->field('beinvite_code')->select();
             
            if(!empty($driver)){
                foreach ($driver as $key => $value) {
                    $driver_info = M('driver')->where(['invite_code'=>$value['beinvite_code']])->field('mobile,invite_name,id')->find();
                    $user_info = M('user')->where(['invite_code'=>$value['beinvite_code']])->field('mobile,invite_name,id')->find();
                    
                    if(empty($driver_info) && !empty($user_info)){
                        $driver[$key]['invite_name'] = $user_info['invite_name'];
                        $driver[$key]['mobile'] = $user_info['mobile'];
                    }else{
                        $driver[$key]['invite_name'] = $driver_info['invite_name'];
                        $driver[$key]['mobile'] = $driver_info['mobile'];
                    }
                    //二级
                    $driver_two = M('invite')->where(['invite_code_first'=>$value['beinvite_code']])->field('beinvite_code')->select();
                    
                    if(!empty($driver_two)){
                        foreach ($driver_two as $k => $v) {
                            
                            $driver_info_two = M('driver')->where(['invite_code' => $v['beinvite_code']])->field('mobile,invite_name,id')->find();
                            $user_info_two = M('user')->where(['invite_code' => $v['beinvite_code']])->field('mobile,invite_name,id')->find();
                            if (empty($driver_info_two) && !empty($user_info_two)) {
                                $driver[$key]['list'][] = $user_info_two;
                            } else {
                                $driver[$key]['list'][] = $driver_info_two;
                            }
                        }
                    }
                }
                
            }
             
			$this->assign('driver', $driver);
            $this->assign($data)->display();
        }
        
        catch (\Exception $e)
        {
            $this->error($e->getMessage());
        }
    }
    /**
     * 更新字段值
     */
    public function updateValue() {
        if(IS_POST)
        {
            $id = I('post.id');
            $field = I('post.field');
            $values = I('post.values');
            
            $model = M('user');
            
            if($model->where(array('id'=>$id))->save(array($field=>$values))){
                $this->ajaxReturn(array('msg'=>"成功更新"));
            }else{
                $this->ajaxReturn(array('msg'=>'对不起，更新失败！'));
            }
        }
    }
    /**
     * 二级邀请人列表
     * @id 一级邀请人id
     * @invite_code invite_code 一级邀请人自身邀请码
     */
    public function inviteList() {
        $id = I('id',0);
        $invite_code = I('invite_code',0);
        
        $driver_two = M('driver')->where(['invite_from_code'=>$invite_code])->field('invite_name,mobile')->select();
        $user_two = M('user')->where(['invite_from_code'=>$invite_code])->field('invite_name,mobile')->select();
        
//        if(!empty($user_two)){
//            foreach ($user_two as $key => $value) {
//                array_push($driver_two['info'], $value);
//            }
//        }
        
        $this->assign('driver_two',$driver_two);
        $this->assign('user_two',$user_two);
        $this->display();
    }
    /**
     * @重置二维码
     */
    public function resetcode(){
        $driver_id = I('post.id');
        if(InviteLogic::resetInviteCode($driver_id,2)){
            $this->ajaxReturn(array('status'=>1,'msg'=>'重置成功'));
        }else{
            $this->ajaxReturn(array('status'=>0,'msg'=>'重置失败，请重试'));
        }
    }

    //后台用户列表
    public function index(){
        echo "我写不来前端---";
    }
}