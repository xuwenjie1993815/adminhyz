<?php


/**
 * Created by PhpStorm
 * User: Administrator
 * Date: 2017/1/4
 * Time: 10:39
 */

namespace Base\Logic;

use Base\Controller\BaseController;
use Think\Crypt\Driver\Think;
use Think\Exception;

class PubLogic
{

    /**
     * @name 短信验证发送
     * @param $mobile 手机号码
     * @param $type 1:司机，2：乘客
     * @return array
     * @throws Exception
     */
    static public function sendSms($mobile, $type=1)
    {
        file_put_contents('getCode.log', var_export('111111',true) . '--' .'chuangjian111-'."\r\n", FILE_APPEND);
        //if($type != 1 && $type != 2) throw new Exception('发送对象异常');
        
        $data['mobile'] = $mobile;
        
        //1. 验证手机号码
        $sms_model = new \Base\Model\SmsModel();
        
        $create = $sms_model->create($data);
        
        file_put_contents('getCode.log', var_export($create,true) . '--' .'chuangjian-'."\r\n", FILE_APPEND);
        if(!$create) throw new Exception($sms_model->getError());
        
        //2. 验证请求时间
        $create_time = $sms_model->where(array('mobile'=>$mobile))->max('create_time');
        
        
        if(time() - $create_time < 60) throw new Exception('短信发送间隔不足一分钟');

        //3. 发送短信
        $data['code'] = mt_rand(100000, 999999);
        $content = '您的验证码为：'.$data['code'] .'，有效期5分钟，请尽快使用。若非本人操作，请忽略该条信息。';
        
        file_put_contents('getCode.log', var_export($content,true) . '--' .'content-'."\r\n", FILE_APPEND);
        
        vendor('Sms.Sms');  

        $sms = new \Sms();
        $result = $sms->send($data['mobile'], $content);
        
        file_put_contents('getCode.log', var_export($result,true) . '--' .'result-'."\r\n", FILE_APPEND);
        
        if(!$result['send_status']) throw new Exception('短信发送失败，请重新获取验证码');
        
        //4. 添加发送记录
        $data['send_status'] = $result['send_status'];
        $data['send_time'] = $data['create_time'] =  NOW_TIME;
        $data['content'] = $result['content'];
        $data['type'] = $type;
        
        file_put_contents('getCode.log', var_export($data,true) . '--' .'chuangjian数据-'."\r\n", FILE_APPEND);
        
        $result_info = $sms_model->add($data);
        
        file_put_contents('getCode.log', var_export($result_info,true) . '--' .'chuangjian添加数据-'."\r\n", FILE_APPEND);
        
        if(!$result_info) throw new Exception('系统繁忙');
        
        unset($sms_model);

        return $data['code'];
    }


    static public function sendMsg($mobile, $content)
    {
        //1. 验证手机号码
        $sms_model = new \Base\Model\SmsModel();

        $data['mobile'] = $mobile;

        if(!$sms_model->create($data)) throw new Exception($sms_model->getError());

        vendor('Sms.Sms');

        $sms = new \Sms();
        $result = $sms->send($mobile, $content);

        //2. 添加发送记录
        $data['send_status'] = $result['send_status'];
        $data['send_time'] = $data['create_time'] =  NOW_TIME;
        $data['content'] = $result['content'];

        if(!$sms_model->add($data)) throw new Exception('系统繁忙');

        return true;
    }


    /**
     * @name 校对手机验证码
     * @param $mobile 手机号码
     * @param $code 验证码
     * @param $type 1：发送司机，2：发送乘客
     * @return bool
     * @throws Exception
     */
    static public function checkMobileCode($mobile, $code, $type=1)
    {
        $sms_model = new \Base\Model\SmsModel();

        //1. 验证发送信息是否存在
        $info = $sms_model->field('id, send_time, status')->where(array('mobile'=>$mobile, 'code'=>$code))->find();
        file_put_contents('reg.log', var_export($info,true) . '---info-'."\r\n", FILE_APPEND);
        if(empty($info)) throw new Exception('手机短信验证失败');

        //2. 验证是否过期
        if(time() - $info['send_time'] > 1800) throw new Exception('验证码已过期，请重新获取');

        //3. 验证是否已使用
        if($info['status']) throw new Exception('验证码已失效，请重新获取');

        //4. 修改使用状态
        $sms_model->where(array('id'=>$info['id']))->setField('status', 1);

        unset($sms_model);

        return true;
    }


    /**
     * 验证登录口令
     * @param $token 口令
     * @param $type 1：司机口令，2：乘客口令
     * @return mixed
     * @throws Exception
     */
    static public function checkToken($token, $type)
    {
        if(empty($token)) throw new Exception('token缺失，请重新登录获取');

        switch ($type)
        {
            case 1:
                $model = M('driver');
                break;

            case 2:
                $model = M('user');
                break;

            default:
                throw new Exception('验证类型异常');
                break;
        }

        if(!$model->where(array('token'=>$token))->count()) throw new Exception('Token验证失败,请重新登录获取');

        list($id, $mobile, $flag) = explode(',', base64_decode($token));

        // 验证过期时间
//        if(time() - $login_time > 3600 *24) throw new Exception('Token已过期，请从新登录');

        return array($id, $mobile, $flag);
    }


    /**
     * @name 单文件上传
     * @param $name 图片字段值
     * @param $type 目录类型
     * @param $file_type 文件类型
     * @param $maxSize 文件大小
     */
    static public function uploadFile($name, $type, $file_type, $maxSize=10485760) // 10M
    {
        if(empty($name)) throw new Exception('图片信息异常');

        $upload = new \Think\Upload();
        $upload->maxSize   =     $maxSize;
        $upload->exts      =     explode('|', $file_type);              // 设置附件上传类型
        $upload->rootPath  =     './Uploads/'.$type.'/';                // 设置附件上传根目录
        $upload->savePath  =     '';                                    // 设置附件上传根目录
        $upload->saveName  =     array('uniqid','');
        $upload->subName   =     array('date','Ymd');

        $info = $upload->uploadOne($_FILES[$name]);

        if(!$info) throw new Exception('2005,'.$upload->getError());

        $info['rootpath'] = $upload->rootPath;

        return $info;
    }



    function base64_upload($base64_image, $pic_txt='')
    {
        file_put_contents('test.log', var_export(123456,true) . '图片1-'."\r\n", FILE_APPEND);
        if(empty($base64_image)) throw new Exception($pic_txt.'图片信息缺失');

        // 解码
        $base64_decode_image = base64_decode($base64_image);

        //匹配成功
        $image_name = md5(uniqid().rand(1, 10000)).'.jpg';

        $dir = './Uploads/images/'.date('Ymd').'/';

        if(!is_dir($dir)) mkdir($dir, 0777);

        $image_file = $dir.$image_name;

        file_put_contents('test.log', var_export($image_file,true) . '图片2-'."\r\n", FILE_APPEND);
        //服务器文件存储路径
        $result = file_put_contents($image_file, $base64_decode_image);

        file_put_contents('test.log', var_export($result,true) . '图片3-'."\r\n", FILE_APPEND);
        if(!$result) throw new Exception('图片写入异常');

        if($result > 1024 * 1024 * 10)
        {
            // 删除图片
            if(file_exists($image_file)) unlink($image_file);

            throw new Exception('图片过大');
        }

        return $image_file;
    }


    /**
     * 获取分页数据
     */
    static public function getListDataByPage($model, $where=array(), $field='*', $order='', $size=20)
    {
        $count = $model->where($where)->count();
        
//        dump($count);
        // 开启分页类
        $page = new \Org\Util\Page($count,$size);

        // 获取分页显示
        $fpage = $count>$size ? $page->Show() : '';

//        dump($order);
        // 获取推送消息数据
        if(!empty($order))
        {
            $list = $model->field($field)->where($where)->order($order)->limit("{$page->firstRow}, {$page->listRows}")->select();
        }
        else
        {
            $list = $model->field($field)->where($where)->limit("{$page->firstRow}, {$page->listRows}")->select();
        }
        return array($list, $fpage);
    }




    /**
     * 消息推送
     * @param $type 接收对象类型
     * @param $alias 别名
     * @param $content 内容
     * @return bool
     * @throws Exception
     */
    static public function pushMessage($type, $alias, $content, $extra=null)
    {
        if($type != 'user' && $type != 'driver') throw new Exception('推送类型异常');
        
        if(empty($alias)) throw new Exception('别名信息异常');
        
        if(empty($content)) throw new Exception('推送内容信息异常');

        //1. 获取key
        list($appkey, $secret) = getJgConfig($type);
        file_put_contents('push.log', var_export($appkey,true) . '--' .'appkey-'."\r\n", FILE_APPEND);
        file_put_contents('push.log', var_export($secret,true) . '--' .'secret-'."\r\n", FILE_APPEND);
        
        //2. 调用三方接口推送消息
        $send_result = sendNotifySpecial($appkey, $secret, $alias, $content, $extra);
        
        file_put_contents('push.log', var_export($send_result,true) . '--' .'推送-'."\r\n", FILE_APPEND);
        //3. 信息入库
        if(!empty($send_result['data']))
        {
            $data['alias'] = $alias;
            $data['sendno'] = $send_result['data']['sendno'];
            $data['msg_id'] = $send_result['data']['msg_id'];
            $data['content'] = $content;
            $data['create_time'] = time();

            if(!M('push')->add($data)) throw new Exception('系统繁忙');
        }
        
        return true;
    }



    /**
     * 功能：生成二维码
     * @param string $qr_data   手机扫描后要跳转的网址
     * @param string $qr_level  默认纠错比例 分为L、M、Q、H四个等级，H代表最高纠错能力
     * @param string $qr_size   二维码图大小，1－10可选，数字越大图片尺寸越大
     * @param string $save_path 图片存储路径
     * @param string $save_prefix 图片名称前缀
     */
    static public function createQRcode($qr_data='http://www.baidu.com', $save_path, $qr_level='L',$qr_size=4,$save_prefix='qrcode')
    {
        if(empty($save_path)) $save_path = './Uploads/images/qrcode/'.date('Ymd').'/';

        //设置生成png图片的路径
        $PNG_TEMP_DIR = & $save_path;
        
        //导入二维码核心程序
        vendor('phpqrcode.phpqrcode');

        //检测并创建生成文件夹
        if (!file_exists($PNG_TEMP_DIR)){
			//mkdir($PNG_TEMP_DIR, '0777');
			mkdir($PNG_TEMP_DIR);
			chmod($PNG_TEMP_DIR,0777);
		}
        $filename = $PNG_TEMP_DIR.time().'.png';

        $errorCorrectionLevel = 'L';

        if (isset($qr_level) && in_array($qr_level, array('L','M','Q','H'))){
            $errorCorrectionLevel = & $qr_level;
        }
        $matrixPointSize = 4;
        if (isset($qr_size)){
            $matrixPointSize = & min(max((int)$qr_size, 1), 10);
        }
        if (isset($qr_data)) {
            if (trim($qr_data) == ''){
                throw new Exception('data cannot be empty!');
            }

            //生成文件名 文件路径+图片名字前缀+md5(名称)+.png
            $filename = $PNG_TEMP_DIR.$save_prefix.md5($qr_data.'|'.$errorCorrectionLevel.'|'.$matrixPointSize).'.png';

            //开始生成
            \QRcode::png($qr_data, $filename, $errorCorrectionLevel, $matrixPointSize, 2);
        } else {
            //默认生成
            \QRcode::png('PHP QR Code :)', $filename, $errorCorrectionLevel, $matrixPointSize, 2);
        }
        
        if(!file_exists($PNG_TEMP_DIR.basename($filename))) throw new Exception('邀请二维码创建失败');

        return $save_path.basename($filename);
    }


    /**
     * 获取邀请二维码图片地址
     * @param $uid 用户ID
     * @param $type 1：司机； 2：乘客； 3：公司；
     * @return mixed
     * @throws Exception
     */
    static public function getQrCode($uid, $type)
    {
        $model;

        switch ($type)
        {
            case 1:
                $model = M('driver');
                break;

            case 2:
                $model = M('user');
                break;

            case 3:
                $model = M('company');
                break;

            default:
                throw new Exception('用户类型异常');
        }

        //1. 验证用户
        $user_info = $model->where(array('id'=>$uid))->field('id, qrcode_url, qrcode_address,invite_code,invite_code_old')->find();

        if(empty($user_info)) throw new Exception('信息异常');

        if(!is_file($user_info['qrcode_url'])) throw new Exception('图片信息异常');

        return [getPicUrl($user_info['qrcode_url']), $user_info['qrcode_address'],$user_info['invite_code'],$user_info['invite_code_old']];
    }


    /**
     * 获取用户一级下线信息
     * @param $uid 用户ID
     * @param $type 用户类型
     * @param int $page 页码
     * @param int $size 每页数量
     * @return array
     * @throws Exception
     */
    static public function getInviteOneData($uid, $type, $page, $size)
    {
        if(!in_array($type, [1,2,3])) throw new Exception('用户类型异常');

        $map['invite_type'] = $type;
        $map['invite_id'] = $uid;
        $map['level'] = 1;

        if(intval($page) == 0) $page = 1;
        if(intval($size) == 0) $size = 10;
        $offset = ($page * $size) - $size;
        
        $driver_model = new \Home\Model\DriverModel();
        $user_model = new \Home\Model\UserModel();
        
        //自身邀请码
        switch ($type) {
            case 1:// 司机
                
                $invite_code = $driver_model->where(['id'=>$uid])->getField('invite_code');

                break;
            case 2:// 乘客
                $invite_code = $user_model->where(['id'=>$uid])->getField('invite_code');
                
                break;

            default://公司
                $invite_code = M('company')->where(['id'=>$uid])->getField('invite_code');
                
                break;
        }
        
        $info = [];
         // 司机
        $info = $driver_model->alias('a')
                 ->join('__DRIVER_INFO__ as b ON a.id = b.id', 'LEFT')
                 ->where(array('a.invite_from_code'=>$invite_code))
                 ->field('mobile, create_time, head_pic, invite_name AS nick_name')
                 ->select();

         // 乘客
        $user_info = $user_model->where(array('invite_from_code'=>$invite_code))->field('mobile, create_time, nick_name, head_pic')->select();
                
        if(!empty($user_info)){
            foreach ($user_info as $key => $value) {
                $info[] = $value;
            }
        }
        
        unset($driver_model, $user_model);
            
        foreach($info as $k=>$v)
        {
            $info[$k]['create_time'] = date('Y-m-d H:i:s', $v['create_time']);
            $info[$k]['head_pic'] = $v['head_pic'] ? getPicUrl($v['head_pic']) : '';
        }
        
        $count = count($info);
        
        $pageTotal = ceil($count/$size);
		
        return array($info, intval($count), $page, $pageTotal);
    }
	
	
	    static public function getInviteOneDataNew($invite_code, $invite_code_old,$uid, $type, $page, $size) {
        if (!in_array($type, [1, 2, 3]))
            throw new Exception('用户类型异常');

//        $map['invite_type'] = $type;
//        $map['invite_id'] = $uid;
//        $map['level'] = 1;
        //$map['invite_code_first'] = $invite_code;
		$map['invite_code_first'] = ['in',[$invite_code,$invite_code_old]];

        if (intval($page) == 0)
            $page = 1;
        if (intval($size) == 0)
            $size = 10;
        $offset = ($page * $size) - $size;

        $driver_model = new \Home\Model\DriverModel();
        $user_model = new \Home\Model\UserModel();

        //自身邀请码
//        switch ($type) {
//            case 1:// 司机
//
//                $invite_code = $driver_model->where(['id' => $uid])->getField('invite_code');
//
//                break;
//            case 2:// 乘客
//                $invite_code = $user_model->where(['id' => $uid])->getField('invite_code');
//
//                break;
//
//            default://公司
//                $invite_code = M('company')->where(['id' => $uid])->getField('invite_code');
//
//                break;
//        }

        $info = [];

        $beinvite_code = M('invite')->where($map)->field('beinvite_code')->select();
		//dump($map);exit;
        if (!empty($beinvite_code)) {
            foreach ($beinvite_code as $key => $value) {
                // 司机
//                $driver_info = $driver_model->alias('a')
//                        ->join('__DRIVER_INFO__ as b ON a.id = b.id', 'LEFT')
//                        ->where(array('a.invite_code' => $value['beinvite_code']))
//                        ->field('mobile, create_time, head_pic, invite_name AS nick_name')
//                        ->find();
//
//                // 乘客
//                $user_info = $user_model->where(array('invite_code' => $value['beinvite_code']))->field('mobile, create_time, invite_name as nick_name, head_pic')->find();

                 $driver_info = $driver_model->alias('a')
                        ->join('__DRIVER_INFO__ as b ON a.id = b.id', 'LEFT')
                        ->where(array('a.invite_code' => $value['beinvite_code']))
                        ->field('mobile, create_time, head_pic, invite_name AS nick_name')
                        ->find();
                $user_info = M('user')->where(['invite_code' => $value['beinvite_code']])->field('mobile, create_time, invite_name as nick_name, head_pic')->find();

                if (empty($driver_info) && !empty($user_info)) {
                    $driver_info = $user_info;
                } else {
                    $driver_info = $driver_info;
                } 
                $info[] = $driver_info;
            }

            unset($driver_model, $user_model);
        }
        if (!empty($info)) {
            foreach ($info as $k => $v) {
                $info[$k]['create_time'] = date('Y-m-d H:i:s', $v['create_time']);
                $info[$k]['head_pic'] = $v['head_pic'] ? getPicUrl($v['head_pic']) : '';
            }
        }

        $count = count($info);

        $pageTotal = ceil($count / $size);

        return array($info, intval($count), $page, $pageTotal);
    }
	/**
     * 获取用户一级下线信息
     * @param $uid 用户ID
     * @param $type 用户类型
     * @param int $page 页码
     * @param int $size 每页数量
     * @return array
     * @throws Exception
     */
    static public function getInviteOneDataNew0615($uid, $type, $page, $size) {
        if (!in_array($type, [1, 2, 3]))
            throw new Exception('用户类型异常');

        $map['invite_type'] = $type;
        $map['invite_id'] = $uid;
        $map['level'] = 1;

        if (intval($page) == 0)
            $page = 1;
        if (intval($size) == 0)
            $size = 10;
        $offset = ($page * $size) - $size;

        $driver_model = new \Home\Model\DriverModel();
        $user_model = new \Home\Model\UserModel();

        //自身邀请码
        switch ($type) {
            case 1:// 司机

                $invite_code = $driver_model->where(['id' => $uid])->getField('invite_code');

                break;
            case 2:// 乘客
                $invite_code = $user_model->where(['id' => $uid])->getField('invite_code');

                break;

            default://公司
                $invite_code = M('company')->where(['id' => $uid])->getField('invite_code');

                break;
        }

        $info = [];

        $beinvite_code = M('invite')->where(['invite_code_first' => $invite_code])->field('beinvite_code')->select();
		
		if (!empty($beinvite_code)) {
            foreach ($beinvite_code as $key => $value) {
                // 司机
                $driver_info = $driver_model->alias('a')
                        ->join('__DRIVER_INFO__ as b ON a.id = b.id', 'LEFT')
                        ->where(array('a.invite_code' => $value['beinvite_code']))
                        ->field('mobile, create_time, head_pic, invite_name AS nick_name')
                        ->find();

                // 乘客
                $user_info = $user_model->where(array('invite_code' => $value['beinvite_code']))->field('mobile, create_time, invite_name as nick_name, head_pic')->find();

                if(empty($driver_info) && !empty($user_info)){
                    $driver_info = $user_info;
                }else if(!empty($driver_info) && empty($user_info)){
                    $driver_info = $driver_info;
                }else if(!empty($driver_info) && !empty($user_info)){
                    array_push($driver_info, $user_info);
                }
                $info[] = $driver_info;
            }

            unset($driver_model, $user_model);

        }
		
		///if(!empty($info[0])){
			//foreach ($info as $k => $v) {
                //$info[$k]['create_time'] = date('Y-m-d H:i:s', $v['create_time']);
                //$info[$k]['head_pic'] = $v['head_pic'] ? getPicUrl($v['head_pic']) : '';
            //}
		//}else{
			//unset($info[0]);
		//}
      
        
        unset($driver_model, $user_model);

        $count = count($info);

        $pageTotal = ceil($count / $size);

        return array($info, intval($count), $page, $pageTotal);
    }
}