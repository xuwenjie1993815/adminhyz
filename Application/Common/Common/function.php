<?php

use Think\Exception;


/**
 * 计算时间差
 */
function get_time_difference($time,$rftime=null){
    if(empty($rftime)){
        $rftime=time();
    }
    $seconds=$rftime-$time;
    if($seconds<60){
        return '刚刚';
    }elseif($seconds<3600){
        return floor($seconds/60).'分钟前';
    }elseif($seconds<3600*24){
        return floor($seconds/3600).'小时前';
    }elseif($seconds<3600*24*30){
        return floor($seconds/3600/24).'天前';
    }else{
        return date('y-m-d',$time);
    }
}


/**
 * 将数据先转换成json,然后转成array
 */
function json_array($result){
    $result_json = json_encode($result);
    return json_decode($result_json,true);
}



function getJgConfig($type='user')
{
//    switch ($type)
//    {
//        case 'user':
//            $appKey = '2be8bf48fe3c3ab3dafaf35f';
//            $secret = '19d10693831c2891d49654d5';
//            break;
//
//        case 'driver':
//            $appKey = '4eec2dcf0d7af5eeae32bef8';
//            $secret = 'db75ec0294b6c4d5d18c0b27';
//            break;
//
//        default:
//            return false;
//    }
            $appKey = '2be8bf48fe3c3ab3dafaf35f';
            $secret = '19d10693831c2891d49654d5';
    return [$appKey, $secret];
}


/**
 * 向所有设备推送消息
 * @param string $message 需要推送的消息
 */
function sendNotifyAll($app_key, $master_secret, $message){
    require_once "JPush\JPush.php";
    $client = new \JPush($app_key,$master_secret);
    $result = $client->push()->setPlatform('all')->addAllAudience()->setNotificationAlert($message)->send();
    return json_array($result);
}


/**
 * 向特定设备推送消息
 * @param array $regid 特定设备的设备标识
 * @param string $message 需要推送的消息
 */
function sendNotifySpecial($app_key, $master_secret, $alias, $message, $extra){
    require_once "JPush\JPush.php";
    $client = new \JPush($app_key,$master_secret);
    $result = $client->push()->setPlatform('all')->addAlias($alias)->addAndroidNotification($message,'',1, $extra)->send();
    return json_array($result);
}



/**
 * 向指定设备推送自定义消息
 * @param string $message 发送消息内容
 * @param array $regid 特定设备的id
 * @param int $did 状态值1
 * @param int $mid 状态值2
 */
function sendSpecialMsg($app_key, $master_secret, $regid,$message,$did,$mid){
    require_once "JPush\JPush.php";
    $client = new \JPush($app_key,$master_secret);
    $result = $client->push()->setPlatform('all')->addRegistrationId($regid)
        ->addAndroidNotification($message,'',1,array('did'=>$did,'mid'=>$mid))
        ->addIosNotification($message,'','+1',true,'',array('did'=>$did,'mid'=>$mid))->send();

    return json_array($result);
}


/**
 * 得到各类统计数据
 * @param array $msgIds 推送消息返回的msg_id列表
 */
function reportNotify($app_key, $master_secret, $msgIds){
    require_once "JPush\JPush.php";
    $client = new \JPush($app_key,$master_secret);
    $response = $client->report()->getReceived($msgIds);
    return json_array($response);
}
/********************************************** 工具类 ******************************************************/

function expressState($state){
    //状态；1：待审核；0：拒绝；-1：删除；2：通过审核；3：已接单；4：已发货；5：已签收；7：完成
    switch ($state) {
        case 1:$text='待审核';break;
        case 0:$text='拒绝';break;
        case -1:$text='删除';break;
        case 2:$text='通过审核';break;
        case 3:$text='已接单';break;
        case 4:$text='已发货';break;
        case 5:$text='已签收';break;
        case 7:$text='完成';break;
    }
    return $text;
}

function createFolder($path) {
    if (!file_exists($path)) {
        createFolder(dirname($path));
        mkdir($path, 0777);
    }
}


/**
 * @name 二维数组转树状结构
 * @param array $list 要转换的结果集
 * @param string $pid parent标记字段
 * @param string $level level标记字段
 */
function array_to_tree($list, $pk = 'id', $pid = 'pid', $child = 'children', $root = 0) {
    //创建Tree
    $tree = array();

    if (is_array($list)) {
        //创建基于主键的数组引用
        $refer = array();

        foreach ($list as $key => $data) {

            $refer[$data[$pk]] = &$list[$key];
        }

        foreach ($list as $key => $data) {
            //判断是否存在parent
            $parantId = $data[$pid];

            if ($root == $parantId) {

                $tree[] = &$list[$key];
            } else {
                if (isset($refer[$parantId])) {

                    $parent = &$refer[$parantId];

                    $parent[$child][] = &$list[$key];
                }
            }
        }
    }

    return $tree;
}



/**
 * 验证参数完整性 ,传入必填且不为空的字段数组
 * @param arr $check_arr   需要验证的字段数组
 * @param arr $receive_arr 接收的参数
 * @return int
 */
function checkParamIntegrity($check_arr = array(), $receive_arr = array()) {
    if (empty($check_arr) || empty($receive_arr))
        return 0;

    for ($i = 0; $i < count($check_arr); $i++) {
        $field = $check_arr[$i];
        if (!in_array($field, array_keys($receive_arr)) || empty($receive_arr[$field]))
            return 0;
    }
    return 1;
}


/**
 * curl请求方法
 * @param $url
 * @param string $type
 * @param array $post_data
 * @return bool|mixed
 */
function curlFunc($url, $post_data = array(), $is_json=1, $type = "POST") {

    if (empty($url))
        return false;

    //初始化
    $ch = curl_init();

    // 选项参数配置
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

    // https 请求
    if (strlen($url) > 5 && strtolower(substr($url, 0, 5)) == "https") {
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    }

    if ($type == "POST") {
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
    }

    if($form == 'json')
    {
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Content-Length: ' . strlen($post_data))
        );
    }

    // //抓取URL并把它传递给浏览器
    $output = curl_exec($ch);

    if (curl_errno($ch)) {
        throw new \Exception(curl_error($ch), 0);
    } else {
        $httpStatusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if (200 !== $httpStatusCode) {
            throw new \Exception($output, $httpStatusCode);
        }
    }

    curl_close($ch);

    $result = $is_json ? json_decode($output, true) : $output;

    // 返回结果
    return $result;
}



/**
 * 字符截取函数
 * @param $string
 * @param int $start
 * @param $length
 * @param bool $mode
 * @param string $dot
 * @return mixed|string
 */
function sub($string,$start=0,$length,$dot='',$char_set='utf-8'){

    $strlen=strlen($string);
    if($strlen<=$length)
    {
        return $string;
    }

    $string = str_replace(array('&nbsp;','&amp;','&quot;','&lt;','&gt;','&#039;'), array(' ','&','"','<','>',"'"), $string);

    $strcut = '';
    if(strtolower($char_set) == 'utf-8') {

        $n = $tn = $noc = 0;
        while($n < $strlen) {

            $t = ord($string[$n]);
            if($t == 9 || $t == 10 || (32 <= $t && $t <= 126)) {
                $tn = 1; $n++; $noc++;
            } elseif(194 <= $t && $t <= 223) {
                $tn = 2; $n += 2; $noc += 2;
            } elseif(224 <= $t && $t < 239) {
                $tn = 3; $n += 3; $noc += 2;
            } elseif(240 <= $t && $t <= 247) {
                $tn = 4; $n += 4; $noc += 2;
            } elseif(248 <= $t && $t <= 251) {
                $tn = 5; $n += 5; $noc += 2;
            } elseif($t == 252 || $t == 253) {
                $tn = 6; $n += 6; $noc += 2;
            } else {
                $n++;
            }

            if($noc >= $length) {
                break;
            }

        }
        if($noc > $length) {
            $n -= $tn;
        }

        $strcut = substr($string, 0, $n);

    } else {
        for($i = 0; $i < $length; $i++) {
            $strcut .= ord($string[$i]) > 127 ? $string[$i].$string[++$i] : $string[$i];
        }
    }

    $strcut = str_replace(array('&','"','<','>',"'"), array('&amp;','&quot;','&lt;','&gt;','&#039;'), $strcut);

    return $strcut.$dot;
}


/**
 * 导出Xls
 * @param $data 遍历数据
 * @param $title 标题
 * @param $filename 文件名
 * @return bool
 */
function dcExcel($data, $title, $filename)
{
    if(!$data || !$filename) return false;

    header('Content-Type: text/html; charset=utf-8');
    header ( "Content-type:application/vnd.ms-excel" );
    header ( "Content-Disposition:filename={$filename}.xls" );

    $xls .= "<!DOCTYPE html PUBLIC '-//W3C//DTD XHTML 1.0 Transitional//EN' 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd'>
             <html xmlns='http://www.w3.org/1999/xhtml'>
             <head>
             <meta http-equiv='Content-Type' content='text/html; charset=UTF-8' />
             <title>{$filename}</title>
             <style>
             td{
                 text-align:center;
                 font-size:12px;
                 font-family:Arial, Helvetica, sans-serif;
                 border:#1C7A80 1px solid;
                 color:#152122;
                 width:100px;
             }
             table,tr{
                 border-style:none;
             }
             .title{
                 background:#7DDCF0;
                 color:#FFFFFF;
                 font-weight:bold;
             }
             </style>
             </head>
             <body>
             <table width='800' border='1'><tr>";

    // 获取标题
    foreach($title as $k=>$v)
    {
        $xls .= "<td class='title'>{$v}</td>";
    }

    $xls .= "</tr>";

    // 遍历数据
    foreach($data as $k=>$v)
    {
        $xls .= "<tr>";
        foreach (array_keys($v) as $kk=>$vv)
        {
            $xls .= "<td>{$v[$vv]}</td>";
        }
        $xls .= "</tr>";
    }
    $xls .= "</table></body></html>";

    echo $xls;
}


/**
 * 获取客户端IP地址
 * @param integer $type
 * @return mixed
 */
function getclientip() {
    static $realip = NULL;

    if ($realip !== NULL) {
        return $realip;
    }
    if (isset($_SERVER)) {
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) { //但如果客户端是使用代理服务器来访问，那取到的就是代理服务器的 IP 地址，而不是真正的客户端 IP 地址。要想透过代理服务器取得客户端的真实 IP 地址，就要使用 $_SERVER["HTTP_X_FORWARDED_FOR"] 来读取。
            $arr = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            /* 取X-Forwarded-For中第一个非unknown的有效IP字符串 */
            foreach ($arr AS $ip) {
                $ip = trim($ip);
                if ($ip != 'unknown') {
                    $realip = $ip;
                    break;
                }
            }
        } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {//HTTP_CLIENT_IP 是代理服务器发送的HTTP头。如果是"超级匿名代理"，则返回none值。同样，REMOTE_ADDR也会被替换为这个代理服务器的IP。
            $realip = $_SERVER['HTTP_CLIENT_IP'];
        } else {
            if (isset($_SERVER['REMOTE_ADDR'])) { //正在浏览当前页面用户的 IP 地址
                $realip = $_SERVER['REMOTE_ADDR'];
            } else {
                $realip = '0.0.0.0';
            }
        }
    } else {
        //getenv环境变量的值
        if (getenv('HTTP_X_FORWARDED_FOR')) {//但如果客户端是使用代理服务器来访问，那取到的就是代理服务器的 IP 地址，而不是真正的客户端 IP 地址。要想透过代理服务器取得客户端的真实 IP 地址
            $realip = getenv('HTTP_X_FORWARDED_FOR');
        } elseif (getenv('HTTP_CLIENT_IP')) { //获取客户端IP
            $realip = getenv('HTTP_CLIENT_IP');
        } else {
            $realip = getenv('REMOTE_ADDR');  //正在浏览当前页面用户的 IP 地址
        }
    }
    preg_match("/[\d\.]{7,15}/", $realip, $onlineip);
    $realip = !empty($onlineip[0]) ? $onlineip[0] : '0.0.0.0';
    return $realip;
}





/**
 * 系统加密方法
 * @param string $data 要加密的字符串
 * @param string $key  加密密钥
 * @param int $expire  过期时间 单位 秒
 * return string
 * @author 麦当苗儿 <zuojiazi@vip.qq.com>
 */
function think_encrypt($data, $key = '', $expire = 0) {
    $key  = md5(empty($key) ? C('DATA_AUTH_KEY') : $key);
    $data = base64_encode($data);
    $x    = 0;
    $len  = strlen($data);
    $l    = strlen($key);
    $char = '';
    for ($i = 0; $i < $len; $i++) {
        if ($x == $l) $x = 0;
        $char .= substr($key, $x, 1);
        $x++;
    }
    $str = sprintf('%010d', $expire ? $expire + time():0);
    for ($i = 0; $i < $len; $i++) {
        $str .= chr(ord(substr($data, $i, 1)) + (ord(substr($char, $i, 1)))%256);
    }
    return str_replace(array('+','/','='),array('-','_',''),base64_encode($str));
}
/**
 * 系统解密方法
 * @param  string $data 要解密的字符串 （必须是think_encrypt方法加密的字符串）
 * @param  string $key  加密密钥
 * return string
 * @author 麦当苗儿 <zuojiazi@vip.qq.com>
 */
function think_decrypt($data, $key = ''){
    $key    = md5(empty($key) ? C('DATA_AUTH_KEY') : $key);
    $data   = str_replace(array('-','_'),array('+','/'),$data);
    $mod4   = strlen($data) % 4;
    if ($mod4) {
        $data .= substr('====', $mod4);
    }
    $data   = base64_decode($data);
    $expire = substr($data,0,10);
    $data   = substr($data,10);
    if($expire > 0 && $expire < time()) {
        return '';
    }
    $x      = 0;
    $len    = strlen($data);
    $l      = strlen($key);
    $char   = $str = '';
    for ($i = 0; $i < $len; $i++) {
        if ($x == $l) $x = 0;
        $char .= substr($key, $x, 1);
        $x++;
    }
    for ($i = 0; $i < $len; $i++) {
        if (ord(substr($data, $i, 1))<ord(substr($char, $i, 1))) {
            $str .= chr((ord(substr($data, $i, 1)) + 256) - ord(substr($char, $i, 1)));
        }else{
            $str .= chr(ord(substr($data, $i, 1)) - ord(substr($char, $i, 1)));
        }
    }
    return base64_decode($str);
}


function makeOrder_sn() {
    //生成20位唯一订单号码，格式：YYYY-MMDD-HHII-SS-NNNN,NNNN-CC，其中：YYYY=年份，MM=月份，DD=日期，HH=24格式小时，II=分，SS=秒，NNNN=随机数，CC=检查码
    //订购日期
    //订单号码主体（YYYYMMDDHHIISSNNNN）

    $order_id_main = date('YmdHis') . rand(1000, 9999);

    //订单号码主体长度

    $order_id_len = strlen($order_id_main);

    $order_id_sum = 0;

    for ($i = 0; $i < $order_id_len; $i++) {

        $order_id_sum += (int) (substr($order_id_main, $i, 1));
    }

    //唯一订单号码（YYYYMMDDHHIISSNNNNCC）

    return $order_sn = $order_id_main . str_pad((100 - $order_id_sum % 100) % 100, 2, '0', STR_PAD_LEFT);
}



/**
 * @name 多维数据循环删除指定键名
 */
function removeArrayKey($data,$key){
    foreach($data as $k=>$val){
        if(in_array($k, $key)){
            unset($data[$k]);
        }

        if(is_array($val)){
            $data[$k]=  removeArrayKey($val, $key);
        }

    }
    return $data;
}


// 10
function getCcode($t)
{
    if(!$t && $t != 0) return false;

    $code = S('code');

    if(empty($code) || intval($code) > 999)
    {
        $code = 100;
    }
    else
    {
        $code = intval(S('code')) + 1;
    }

    S('code', $code);

    // 时间戳当天秒数
    $secs = substr(time(), -6);

    return $secs.$t.$code;
}



// 15
function getCcode1($t)
{
    if(!$t && $t != 0) return false;

    // 3位随机数
    $random_number = sprintf('%03d', mt_rand(0, 999));

    // 微秒
    $wm = substr(microtime(), 2, 3);

    // 时间戳当天秒数
    $secs = substr(time(), -5);

    return date('md').$random_number.$secs.$t.$wm;
}


function arrayChangeKeyByVal($arr, $field)
{
    if(empty($arr) || empty($field) || !is_array($arr) || !is_string($field)) return false;

    $new_arr = array();

    foreach($arr as $k=>$v) $new_arr[$v[$field]] = $v;

    return $new_arr;
}


/**
 * 英文字母数组
 * @return array
 */
function zmArr()
{
    $arr = array();

    $j = 1;

    for($i=65;$i<91;$i++)
    {
        $arr[$j] = strtoupper(chr($i));
        $j++;
    }

    return $arr;
}


function luhm($s) {
    $n = 0;
    for($i=strlen($s)-1; $i>=0; $i--) {
        if($i % 2) $n += $s{$i};
        else {
            $t = $s{$i} * 2;
            if($t > 9) $t = $t{0} + $t{1};
            $n += $t;
        }
    }
    return ($n % 10) == 0;
}

/**
 * 重置数组KEY
 * @param $arr
 * @return array
 */
function restore_array($arr){
    if (!is_array($arr)){ return $arr; }
    $c = 0; $new = array();
    while (list($key, $value) = each($arr)){
        if (is_array($value)){
            $new[$c] = restore_array($value);
        }
        else { $new[$c] = $value; }
        $c++;
    }
    return $new;
}

/**
 * 设置二维数组Key
 * @param $arr
 * @param $key
 * @return array|bool
 */
function setArrayKey($arr, $key)
{
    if(!is_array($arr) || !is_string($key) || empty($arr) || empty($key)) return false;

    $new_arr = array();

    for ($i=0; $i<count($arr); $i++)
    {
        $new_key_val = $arr[$i][$key];

        $new_arr[$new_key_val] = $arr[$i];
    }

    return $new_arr;
}


/**
 * excel时间格式转换
 * @param $date
 * @param bool $time
 * @return array|int|string
 */
function excelTime($date, $time = false)
{
    if (function_exists('GregorianToJD')) {
        if (is_numeric($date)) {
            $jd = GregorianToJD(1, 1, 1970);
            $gregorian = JDToGregorian($jd + intval($date) - 25569);
            $date = explode('/', $gregorian);
            $date_str = str_pad($date[2], 4, '0', STR_PAD_LEFT) . "-" . str_pad($date[0], 2, '0', STR_PAD_LEFT) . "-" . str_pad($date[1], 2, '0', STR_PAD_LEFT) . ($time ? " 00:00:00" : '');
            return $date_str;
        }
    } else {
        $date = $date > 25568 ? $date + 1 : 25569; /*There was a bug if Converting date before 1-1-1970 (tstamp 0)*/
        $ofs = (70 * 365 + 17 + 2) * 86400;
        $date = date("Y-m-d", ($date * 86400) - $ofs) . ($time ? " 00:00:00" : '');
    }
    return $date;
}



function getInfoById($arr, $id)
{
    if(!is_array($arr) || empty($arr)) return false;

    return $arr[$id];
}

function getFieldByArr($arr, $id, $field)
{
    if(!is_array($arr) || empty($arr) || empty($id) || empty($field)) return false;

    return $arr[$id][$field];
}


// 获取当月起止时间
// $month = '2017-1';
function getMontyTime($month)
{
    $month_start = strtotime($month);//指定月份月初时间戳
    $month_end = mktime(23, 59, 59, date('m', strtotime($month))+1, 00);//指定月份月末时间戳
    
    return array($month_start, $month_end);
}

/**
 * @截取字符
 * @param type $len
 * @param string $chars
 * @return string
 * 
 */
function getRandomString($len, $chars=null)
{
    if (is_null($chars)) {
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
    }
    mt_srand(10000000*(double)microtime());
    for ($i = 0, $str = '', $lc = strlen($chars)-1; $i < $len; $i++) {
        $str .= $chars[mt_rand(0, $lc)];
    }
    return $str;
}
// 1:司机，2：乘客，3：公司
function getIntiteCodeNew()
{
    
    $code=getRandomString(4,'0123456789');

    // 获取时间
    $time = date('ymdHis');

    $invite_code = $code.$time;
    
    return $invite_code;
}
// 1:司机，2：乘客，3：公司
function getIntiteCode($t)
{
    if(!$t) return false;
    
    $code=getRandomString(4,'0123456789');

    // 获取时间
    $time = date('ymd');

    $invite_code = $code.$time.$t;
    
    return $invite_code;
}


/********************************************** 获取字段文本 ************************************************/


/**
 * 审核状态
 */
function getAuditTxt($status, $type=0)
{
    if($type)
    {
        $data = array(
            0 => '未审核',
            1 => '审核通过',
            -1 => '审核失败',
        );
    }
    else 
    {
        $data = array(
            0 => '<span class="text-blue">未审核</span>',
            1 => '<span class="text-green">审核通过</span>',
            -1 => '<span class="text-red">审核失败</span>',
        );
    }

    return $data[$status] ? : '-';
}


/**
 * 启用状态
 */
function getUseTxt($status, $type=0)
{
    if($type)
    {
        $data = array(
            0 => '已禁用',
            1 => '已启用',
        );
    }
    else
    {
        $data = array(
            0 => '<span class="text-red">已禁用</span>',
            1 => '<span class="text-green">已启用</span>',
        );
    }
    
    return $data[$status] ? : '-';
}


function userLevel($level)
{
    $data = array(
        1 => '<span class="text-blue">青铜</span>',
        2 => '<span class="text-blue" >白银</span>',
        3 => '<span class="text-blue">黄金</span>',
    );
    
    return $data[$level] ? : '-';
}

/**
 * 启用状态
 */
function getDriverType($status)
{
    $data = array(
        1 => '专职司机',
        2 => '顺风车司机',
    );

    return $data[$status] ? : '-';
}


/**
 * 发车类型
 * @param $status
 * @return string
 */
function sendType($status, $is_color=0)
{
    if($is_color)
    {
        $data = array(
            1 => '<span class="text-blue">坐满就走</span>',
            2 => '<span class="text-yellow-deep" >定时发车</span>'
        );
    }
    else 
    {
        $data = array(
            1 => '坐满就走',
            2 => '定时发车'
        );
    }
    
    
    return $data[$status] ? : '-';
}



/**
 * 司机服务单状态
 */
function serviceStatus($status)
{
    $data = array(
        1 => '<span class="text-green">待出车</span>',
        2 => '<span class="text-orange">行程中</span>',
        3 => '<span class="text-gray">已完结</span>',
        -1=> '<span class="text-red">已取消</span>'
    );

    return $data[$status] ? : '-';
}


/**
 * 订单状态
 */
function orderStatus($status)
{
    $data = array(
        0 => '待付款',
        1 => '已付款',
        2 => '司机已接单',
        3 => '已完结',
        -1 => '已取消',
        -2 => '已拒绝'
    );

    return $data[$status] ? : '-';
}


// 支付方式
function payMethod($status)
{
    $data = array(
        1 => '支付宝',
        2 => '微信支付',
        3 => '现金支付',
    );

    return $data[$status] ? : '-';
}


/********************************************** 返回格式 ****************************************************/


function getPicUrl($pic_url, $type=0)
{
    $pic_url = substr($pic_url, 1);

    if(!$type) $pic_url = 'http://'.$_SERVER['HTTP_HOST'].$pic_url;

    return $pic_url;
}


function code($code) {
    $msg = C('code');
    if (!IS_AJAX) {
        return "Error:[$code]" . $msg[$code];
    } else {
        return $msg[$code];
    }
}

function returnData($status, $msg='', $data=array())
{
    return array('status'=>$status, 'msg'=>$msg, 'data'=>$data);
}


function apiFormat($error_code=0, $msg='success', $data=array())
{
    if(empty($data)) $data = (object)null;

    return array('errCode'=>$error_code, 'msg'=>$msg, 'data'=>$data);
}


function doExdata($data, $mark=',')
{
    if(empty($data)) return false;
    
    $data = explode($mark, $data);

    return array(intval($data[0]), $data[1]);
}


function guid() {
    if (function_exists('com_create_guid') === true) {
        $str= trim(com_create_guid(), '{}');
    }

    $str= sprintf('%04X%04X-%04X-%04X-%04X-%04X%04X%04X', mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(16384, 20479), mt_rand(32768, 49151), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535));
    
    return str_replace('-', '', $str);
}