/**
 * Created by Administrator on 2016/12/5.
 */




// url参数设置
function changeURLArg(url, arg, arg_val) {
    var pattern = arg + '=([^&]*)';
    var replaceText = arg + '=' + arg_val;
    if (url.match(pattern)) {
        var tmp = '/(' + arg + '=)([^&]*)/gi';
        tmp = url.replace(eval(tmp), replaceText);
        return tmp;
    } else {
        if (url.match('[\?]')) {
            return url + '&' + replaceText;
        } else {
            return url + '?' + replaceText;
        }
    }
    return url + '\n' + arg + '\n' + arg_val;
}


function msg(txt) {
    layer.msg(txt);
}

function redirect(url) {
    setTimeout(function() {
        window.location.href = url;
    }, 800);
} 

/*用于双击编辑时*/
function ShowElement(element, $field) {
    console.log($(element))
    var oldhtml = element.innerText;
    //创建新的input元素
    var newobj = document.createElement('input');
    //为新增元素添加类型
    newobj.type = 'text';
    //为新增元素添加value值
    newobj.value = oldhtml;
    //为新增元素添加光标离开事件
    newobj.onblur = function() {
        //当触发时判断新增元素值是否为空，为空则不修改，并返回原有值 
        element.innerText = this.value == oldhtml ? oldhtml : this.value;
        //更新数据到数据库
        if(this.value == oldhtml){
            return false;
        }else{
            updateData($field,this.value);
        }                         
    }
    //设置该标签的子节点为空
    element.innerHTML = '';
    //添加该标签的子节点，input对象
    element.appendChild(newobj);
    //设置选择文本的内容或设置光标位置（两个参数：start,end；start为开始位置，end为结束位置；如果开始位置和结束位置相同则就是光标位置）
    newobj.setSelectionRange(0, oldhtml.length);
    //设置获得光标
    newobj.focus();
}