/**
 * 获取省份
 */
function get_province() {
    var url = '/index.php?m=Base&c=Api&a=getRegion&level=1&parent_id=0';
    $.ajax({
        type: "GET",
        url: url,
        error: function (request) {
            alert("服务器繁忙, 请联系管理员!");
            return;
        },
        success: function (v) {
            v = '<option value="0">选择省份</option>' + v;
            $('#province').empty().html(v);
        }
    });
}


/**
 * 获取城市
 * @param t  省份select对象
 */
function get_city(t) {
    var parent_id = $(t).val();
    if (!parent_id > 0) {
        return;
    }
    $('#twon').empty().css('display', 'none');
    var url = '/index.php?m=Inventory&c=Api&a=getRegion&level=2&parent_id=' + parent_id;
    $.ajax({
        type: "GET",
        url: url,
        error: function (request) {
            alert("服务器繁忙, 请联系管理员!");
            return;
        },
        success: function (v) {
            v = '<option value="0">选择城市</option>' + v;
            $('#city').empty().html(v);
        }
    });
}

/**
 * 获取地区
 * @param t  城市select对象
 */
function get_area(t) {
    var parent_id = $(t).val();
    if (!parent_id > 0) {
        return;
    }
    var url = '/index.php?m=Inventory&c=Api&a=getRegion&level=3&parent_id=' + parent_id;
    $.ajax({
        type: "GET",
        url: url,
        error: function (request) {
            alert("服务器繁忙, 请联系管理员!");
            return;
        },
        success: function (v) {
            v = '<option>选择区域</option>' + v;
            $('#district').empty().html(v);
        }
    });
}
// 获取最后一级乡镇
function get_twon(obj) {
    var parent_id = $(obj).val();
    var url = '/index.php?m=Inventory&c=Api&a=getTwon&parent_id=' + parent_id;
    $.ajax({
        type: "GET",
        url: url,
        success: function (res) {
            if (parseInt(res) == 0) {
                $('#twon').empty().css('display', 'none');
            } else {
//                $('#twon').css('display', 'block').css('float','left');
                $("#twon").removeClass('hidden');
                $('#twon').css({ 'float': 'right','margin-right':' -10px','margin-top':' -38px','display': 'block'});
                $('#twon').empty().html(res);
            }
        }
    });
}


/**
 * 输入为空检查
 * @param name '#id' '.id'  (name模式直接写名称)
 * @param type 类型  0 默认是id或者class方式 1 name='X'模式
 */
function is_empty(name, type) {
    if (type == 1) {
        if ($('input[name="' + name + '"]').val() == '') {
            return true;
        }
    } else {
        if ($(name).val() == '') {
            return true;
        }
    }
    return false;
}

/**
 * 邮箱格式判断
 * @param str
 */
function checkEmail(str) {
    var reg = /^[a-z0-9]([a-z0-9\\.]*[-_]{0,4}?[a-z0-9-_\\.]+)*@([a-z0-9]*[-_]?[a-z0-9]+)+([\.][\w_-]+){1,5}$/i;
    if (reg.test(str)) {
        return true;
    } else {
        return false;
    }
}
/**
 * 手机号码格式判断
 * @param tel
 * @returns {boolean}
 */
function checkMobile(tel) {
    var reg = /(^1[3|4|5|7|8][0-9]{9}$)/;
    if (reg.test(tel)) {
        return true;
    } else {
        return false;
    }
    ;
}

/**
 * 身份证格式判断
 * @param str
 * @returns {boolean}
 */
function checkIdcard(str) {
    var reg = /^(\d{15}$)|(^\d{17})([0-9]|X)$/;
    if (reg.test(str)) {
        return true;
    } else {
        return false;
    }
    ;
}


/**
 * QQ号码格式判断
 * @returns bool
 */
function checkQQ(str) {
    var reg = /^[1-9]\d{4,8}$/;
    if (reg.test(str)) {
        return true;
    } else {
        return false;
    }
    ;
}


/**
 * 姓名格式判断
 * @returns bool
 */
function checkName(str) {
    var reg = /^[\u0391-\uFFE5\w]+$/;
    if (reg.test(str)) {
        return true;
    } else {
        return false;
    }
    ;
}

/**
 * 固定电话格式判断
 * @returns bool
 */
function checkFixPhone(str) {
    var reg = /^(([0\+]\d{2,3}-)?(0\d{2,3})-)?(\d{7,8})(-(\d{3,}))?$/;
    if (reg.test(str)) {
        return true;
    } else {
        return false;
    }
    ;
}


/**
 * 400电话格式验证
 * $returns bool
 */
function checkFourPhone(str)
{
    var reg = /^400\-[\d|\-]{7}[\d]{1}$/;
    if (reg.test(str)) {
        return true;
    } else {
        return false;
    }
}

/**
 * 用户名验证
 * $returns bool
 */
function checkUname(str) {
    var reg = /^[a-zA-Z]\w{3,13}[a-zA-Z\d]$/;
    if (reg.test(str)) {
        return true;
    } else {
        return false;
    }
}

/**
 * 密码验证
 * 6-16位任一字符
 * @returns bool
 */
function checkPwd(str) {
    var reg = /^\S{6,16}$/;
    if (reg.test(str)) {
        return true;
    } else {
        return false;
    }
}


