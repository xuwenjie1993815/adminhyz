function getprovince(pid,rid) {
	$.get("/admin.php/Main/Index/ajaxRegion",{pid:pid,rid:rid},function(res){
		if(res.status == 200){
			 $("#province").empty().append("<option value=''>-省份-</option>").append(res.data);
		 }
	},'json');
 }
 
 function getcity(pid,rid) {
	 $.get("/admin.php/Main/Index/ajaxRegion",{pid:pid,rid:rid},function(res){
		 if(res.status == 200){
			$("#city option:gt(0)").remove();
			$("#town option:gt(0)").remove();
			$("#city").empty().append("<option value=''>-城市-</option>").append(res.data); 
		 }
	 },'json');
 }
 
 function gettown(pid,rid) {
	 $.get("/admin.php/Main/Index/ajaxRegion",{pid:pid,rid:rid},function(res){
		 if(res.status == 200){
			 $("#town option:gt(0)").remove();
			 $("#town").empty().append("<option value=''>-地区-</option>").append(res.data); 
		 }
	 },'json');
 }
 
  function gettowns() {
			var caddr=document.getElementById('caddr');
			var index = document.getElementById('town').selectedIndex;
			str = document.getElementById('town').options[index].text;
			txt = caddr.value;
			txt = txt + "\r" + str;
			caddr.value=txt;
 }