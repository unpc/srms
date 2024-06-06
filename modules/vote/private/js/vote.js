$('#'+add_option_id).bind('click',function(){
	var option_size = $('#'+form_table_id+' tr.option').size();
	var last_tr = $(this).parent().parent().prev();
	
	var new_tr = new Array();
	new_tr.push("<tr class=\"option\">");
	new_tr.push("<td class=\"label right nowrap middle\">&nbsp;</td>");
	new_tr.push("<td class=\"label left top\">");
	new_tr.push("<input name=\"option_"+(parseInt(option_size)+1)+"\" class=\"text\" size=\"30\" value=\"\"/>");
	new_tr.push("</td></tr>");						
	$(last_tr).after(new_tr.join(""));	
});




							
							
								
							