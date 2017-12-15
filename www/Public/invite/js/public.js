// JavaScript Document
(function (doc, win) {
    // 分辨率Resolution适配
    var docEl = doc.documentElement,
        resizeEvt = 'orientationchange' in window ? 'orientationchange' : 'resize',
        recalc = function () {
            var clientWidth = docEl.clientWidth;
            if (!clientWidth) return;
            if (clientWidth >= 750 && clientWidth <= 1000 ) {
                docEl.style.fontSize = 100 + 'px';
            } else if(clientWidth >= 1000){
                docEl.style.fontSize = 100 * (1000 / 750) + 'px';
            }else{docEl.style.fontSize = 100 * (clientWidth / 750) + 'px';}
        }
    // Abort if browser does not support addEventListener
    if (!doc.addEventListener) return;
    win.addEventListener(resizeEvt, recalc, false);
    recalc();
})
(document, window);

/*弹框JS内容结束*/

