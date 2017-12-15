<?php

function arrayToXml($arr)
    {
        $xml = "<xml>";
        foreach ($arr as $key=>$val)
        {
            if (is_numeric($val)){
                $xml.="<".$key.">".$val."</".$key.">";
            }else{
                 $xml.="<".$key."><![CDATA[".$val."]]></".$key.">";
            }
        }
        $xml.="</xml>";
        return $xml;
    }

function   get_week($date){
    //强制转换日期格式
    $date_str=date('Y-m-d',strtotime($date));

    //封装成数组
    $arr=explode("-", $date_str);

    //参数赋值
    //年
    $year=$arr[0];

    //月，输出2位整型，不够2位右对齐
    $month=sprintf('%02d',$arr[1]);

    //日，输出2位整型，不够2位右对齐
    $day=sprintf('%02d',$arr[2]);

    //时分秒默认赋值为0；
    $hour = $minute = $second = 0;

    //转换成时间戳
    $strap = mktime($hour,$minute,$second,$month,$day,$year);

    //获取数字型星期几
    $number_wk=date("w",$strap);

    //自定义星期数组
    $weekArr=array("星期日","星期一","星期二","星期三","星期四","星期五","星期六");

    //获取数字对应的星期
    return $weekArr[$number_wk];
}



function sub_right($string, $strlen = '50'){
    $tmpstr = "";
//先把字符串减去3位
    $lengs =strlen($string)-3;
//如果截取后的字符窜长度小于或者等于10位
    if($lengs<=9)
        $strlen=$lengs-2; //4个星号，所以这里减去4
    for($i = 0; $i < strlen($string); $i++) {
        //前4位处理
        if($i<$strlen){
            //ord() 函数返回字符串的首个字符的 ASCII 值。  0xa0是十六进制数，asc码一般大于这个值得就是汉字
            if(ord(substr($string, $i, 1)) > 0xa0) {
                //是汉字就截取2位
                $tmpstr .= substr($string, $i, 2);
                $i++;
            } else
//不是汉字就截取1位
                $tmpstr .= substr($string, $i, 1);
        }else{
            //满足4位之后  补足*
            if($i < $lengs){
                $tmpstr .="*";
            }
        }
    }
    $tmpstr .= substr($string,-3);
    return $tmpstr;
}



function get_device_type()
{
    //全部变成小写字母
    $agent = strtolower($_SERVER['HTTP_USER_AGENT']);
    $type = 'other';
    //分别进行判断
    if(strpos($agent, 'iphone') || strpos($agent, 'ipad'))
    {
        $type = 'ios';
    }

    if(strpos($agent, 'android'))
    {
        $type = 'android';
    }
    return $type;
}
