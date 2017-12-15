<?php

namespace Base\Controller;

/**
 * Description of BaseController
 *
 */
class BaseController extends \Think\Controller
{

    public function _initialize()
    {
        $this->access();
    }



    /**
     * @name 权限判断
     */
    private function access()
    {
        $username = session('adminInfo.account');
        $userid = session('adminInfo.id');
        $roleid = session('adminInfo.role_id');

        // 登录信息缺失
        if (empty($username) || strlen($username) < 5) redirect(U('Home/Pub/login', '', 'html', true));
    
        // 当前用户为超级管理员
        if ($username === C('SUPER_ADMIN_NAME') && C('OPEN_SUPER_ADMIN') == 1) return true;
		if ($username === C('SUPER_ADMIN_NAME_TWO') && C('OPEN_SUPER_ADMIN_TWO') == 1) return true;

        // 获取权限
        if(session('?adminAccess'))
        {
            $access = session('adminAccess');
        }
        else
        {
            $personalAccess = M('adminNode')->where(array('adminid' => $userid))->getField('nodeid', true);//角色id

            $roleAccess = M('roleNode')->where(array('roleid' => $roleid))->getField('nodeid', true);//权限id

            $myaccess = $roleAccess;
//            dump($myaccess);
            
            if (empty($myaccess)) {
                echo '请联系管理员分配权限';
                exit();
            }

            $access = M('Node')->where(array('id' => array('in', $myaccess)))->getField('lower(name)', true);
            
            session('adminAccess', $access);
        }

        $thisNode = strtolower(MODULE_NAME . '/' . CONTROLLER_NAME . '/' . ACTION_NAME);
        
        if (!in_array($thisNode, $access))
        {
            if(!in_array_case($thisNode, C('IGNORES')))
            {
                if (IS_AJAX) {
                    $this->ajaxReturn(array('status' => 0, 'msg' => '权限不足！', 'data'=>array('access'=>$access, 'node'=>$thisNode)));
                    $this->U('Home/Pub/login');
                } else {
                    $this->error('权限不足！',U('Home/Pub/login'));
                }
            }
        }
    }

}
