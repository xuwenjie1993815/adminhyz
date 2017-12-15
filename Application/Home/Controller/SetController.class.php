<?php

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2017/3/7
 * Time: 15:08
 */

namespace Home\Controller;

use Think\Exception;
use Home\Logic\SetLogic;

class SetController extends \Base\Controller\BaseController {

    /**
     * @name 版本列表
     */
    public function versionlist() {
        $data = M('version')->order('id desc')->select();

        $this->assign('data', $data);
        $this->display();
    }

    /**
     * @name 版本更新
     */
    public function version() {
        if (IS_POST) {
            set_time_limit(0);
//            var_dump(ini_get('file_uploads'));
//            var_dump(ini_get('upload_max_filesize'));
//            var_dump(ini_get('post_max_size'));

            ini_set('file_uploads', 'ON');
            ini_set('max_input_time', '90');
            ini_set('max_execution_time', '180');
            ini_set('post_max_size', '12M');
            ini_set('upload_max_filesize', '10M');
            ini_set('memory_limit', '20M');


            $data = I('post.');
            $upload = new \Think\Upload(); // 实例化上传类
            $upload->maxSize = 50 * 1204 * 1204; // 设置附件上传大小
            $upload->exts = array('apk'); // 设置附件上传类型
            $upload->rootPath = './Public/'; // 设置附件上传根目录
            $upload->autoSub = false;
            $upload->replace = true;
            $upload->saveName = false;
            $upload->savePath = 'downloads/'; // 设置附件上传（子）目录
            // 上传文件 
            $info = $upload->upload();
            if (!$info) {// 上传错误提示错误信息
                $this->error($upload->getError());
            } else {// 上传成功
                $data['downloadlink'] = $upload->rootPath . $info['file']['savepath'] . $info['file']['savename'];
            }
            $obj = M('version');
            if ($obj->add($data)) {
                $this->success('添加成功', U('versionlist', '', 'html', true));
            } else {
                $this->error('添加失败');
            }
        } else {
            $this->display();
        }
    }

    //设置司机提成比例
    public function setDriverProprot() {
        if (IS_POST) {
            try {
                $data = I('post.');
                if (!is_numeric($data['driver_rate']) || $data['driver_rate'] < 0 || $data['driver_rate'] > 100) {
                    throw new Exception('请填写正确的司机提成比例，0 ~ 100 之间');
                }
                $data['id'] = 1;
                $res = M('set')->save($data);
                $this->ajaxReturn(returnData(1, '配置成功'));
            } catch (Exception $e) {
                $this->ajaxReturn(returnData(0, $e->getMessage()));
            }
        }
        $info = M('set')->find();
        $this->assign('info', $info);
        $this->display();
    }

    /**
     * 设置三级分销比例
     */
    public function setDistrProprot() {
        if (IS_POST) {
            try {
                $data = I('post.');
                if (!is_numeric($data['one_rate']) || !is_numeric($data['two_rate']) || !is_numeric($data['three_rate']) || !is_numeric($data['company_rate'])) {
                    throw new Exception('请填写正确的抽成比例');
                }
                $rate = floatval($data['one_rate']) + floatval($data['two_rate']) + floatval($data['three_rate']+floatval($data['company_rate']));
                if ($rate < 0 || $rate > 100) {
                    throw new Exception('提成比例之和不能高于100或低于0');
                }
                $data['id'] = 1;
                $data['terrace_rate'] = 100 - $rate;
                $res = M('set')->save($data);
                $this->ajaxReturn(returnData(1, '配置成功'));
            } catch (Exception $e) {
                $this->ajaxReturn(returnData(0, $e->getMessage()));
            }
        }
        $info = M('set')->find();
        $this->assign('info', $info);
        $this->display();
    }

    /**
     * 积分设置
     */
    public function setInteg() {
        if (IS_POST) {
            try {
                $data = I('post.');
                if ($data['z_integral'] == "") {
                    throw new Exception('请填写正确的积分');
                }

                if ($data['d_money'] > 5 || $data['x_integral'] < 1000) {
                    throw new Exception('请填写正确的消费积分比例');
                }

                if ($data['zf_money'] < 50 || $data['zs_integral'] < 500) {
                    throw new Exception('请填写正确的支付积分比例');
                }
                $data['id'] = 1;
                $res = M('set')->save($data);
                $this->ajaxReturn(returnData(1, '配置成功'));
            } catch (Exception $e) {
                $this->ajaxReturn(returnData(0, $e->getMessage()));
            }
        }
        $info = M('set')->find();
        $this->assign('info', $info);
        $this->display();
    }

    /**
     * 设置司机奖励
     */
    public function setdriverReward() {
        if (IS_POST) {
            try {
                $data = I('post.');
                if (!is_numeric($data['db_money']) || !is_numeric($data['db_person']) || !is_numeric($data['bonud_person']) || !is_numeric($data['bonud_money'])) {
                    throw new Exception('请填写正确的奖励设置');
                }
                $data['id'] = 1;
                $res = M('set')->save($data);
                $this->ajaxReturn(returnData(1, '配置成功'));
            } catch (Exception $e) {
                $this->ajaxReturn(returnData(0, $e->getMessage()));
            }
        }
        $info = M('set')->find();
        $this->assign('info', $info);
        $this->display();
    }

    /**
     * 分销提成比例配置
     */
    public function setCommissionRate() {
        // 加载配置界面
        if (IS_GET) {
            try {
                //1. 获取系统设置数据
                $data['info'] = M('set')->find();

                $this->assign($data)->display();
            } catch (\Exception $e) {
                $this->error($e->getMessage());
            }
        }

        // 保存提成比例信息
        if (IS_POST) {
            try {
                //1. 保存配置数据
                SetLogic::saveSetData(I('post.'));

                $this->ajaxReturn(returnData(1, '配置成功'));
            } catch (\Exception $e) {
                $this->ajaxReturn(returnData(0, $e->getMessage()));
            }
        }
    }

    /**
     * 用户协议
     */
    public function protocol() {
        // 修改
        if (IS_POST) {
            try {
                $protocol = I('post.protocol/s', '');

                if (empty($protocol))
                    throw new Exception('请填写协议内容');

                if (M('set')->where(array('id' => 1))->setField('protocol', $protocol) === false)
                    throw new Exception('系统繁忙');

                $this->ajaxReturn(returnData(1, '保存成功'));
            } catch (\Exception $e) {
                $this->ajaxReturn(returnData(0, $e->getMessage()));
            }
        }


        // 加载页面
        else {
            $data['protocol'] = M('set')->getField('protocol');

            $data['protocol'] && $data['protocol'] = htmlspecialchars_decode($data['protocol']);

            $this->assign($data)->display();
        }
    }
	/**
     * 司机协议
     */
    public function driver_protocol() {
        // 修改
        if (IS_POST) {
            try {
                $protocol = I('post.protocol/s', '');

                if (empty($protocol))
                    throw new Exception('请填写协议内容');

                if (M('set')->where(array('id' => 1))->setField('driver_protocol', $protocol) === false)
                    throw new Exception('系统繁忙');

                $this->ajaxReturn(returnData(1, '保存成功'));
            } catch (\Exception $e) {
                $this->ajaxReturn(returnData(0, $e->getMessage()));
            }
        }


        // 加载页面
        else {
            $data['driver_protocol'] = M('set')->getField('driver_protocol');

            $data['driver_protocol'] && $data['driver_protocol'] = htmlspecialchars_decode($data['driver_protocol']);
            
            $this->assign($data)->display();
        }
    }

    /**
     * @修改密码
     */
    public function UserPWD() {
        $data['username'] = session('adminInfo.account');
        $this->assign($data)->display();
    }

    /**
     * @修改密码
     */
    public function UpdatePWD() {
        $username = I('post.username');
        $oldpwd = md5(I('post.oldpwd'));
        $newpwd = md5(I('post.newpwd'));
        $newpwd2 = md5(I('post.newpwd2'));

        if ($newpwd == $newpwd2) {
            if (M('admin')->where(array('account' => $username, 'pwd' => $oldpwd))->save(array('pwd' => $newpwd))) {
                $this->ajaxReturn(array('status' => 1, 'msg' => '密码修改成功!'));
            } else {
                $this->ajaxReturn(array('status' => 0, 'msg' => '密码修改失败!'));
            }
        } else {
            $this->ajaxReturn(array('status' => 0, 'msg' => '两次密码不相同!'));
        }
    }

}
