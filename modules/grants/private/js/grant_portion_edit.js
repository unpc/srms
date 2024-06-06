/*
rel_id: 容器ID
*/
jQuery(function($){
	/*
		此段 js 作 3 件事情
		1. 添加子 grant_portion， 并对 grant_portion 的 name / class / id / size 等作一些调整
		2. 递归删除 grant_portion, 并对其祖先 grant_portion 的 amount 进行设置
		3. 修改 amount 的值时， 对其 balance 和 avail_balance 及 祖先 amount 同步修改
	*/
	var selector = ['#', rel_id].join('');
	var $table = $(selector);
	var $form = $table.parents('form').eq(0);

	function gp_uniqid() {
		var id;
		do {
			id = Q.uniqid();
		}
		while ($table.find('[name="portion_name\['+id+'\]"]').length > 0);
		return id;
	}
	
	// 使 blur 事件延迟绑定，让 number js 先执行
	window.setTimeout(function(){
		$('tbody tr:not(.gp_item_0) [name^="portion_amount"]', $table).blur(gp_amount_blur);
		
		// grant amount 赋值 (同下)
		$('input[name="amount"]').blur(function(){

			var amount = $(this);
			var old_val = parseFloat(amount.data('old_value')) || 0;
			var new_val = parseFloat(amount.val());
		
			if (old_val == new_val) { return; }
		
			var avail_balance = $('input[name="avail_balance"]');
			var avail_balance_val = parseFloat(avail_balance.val()) || 0;
			
			new_val = Math.max(new_val, old_val - avail_balance_val);
			avail_balance_val += new_val - old_val;

			amount.val(new_val).change();
			
			avail_balance.val(avail_balance_val).change();
			
		});
	}, 200);

	// blur hook 函数
	// avail_balance += amount 增加的值
	// balance += amount 增加的值
	// parent_avail_balance -= amount 增加的值
	// amount 不能小于 old_amount - avail_balance
	// amount 增加的值不能大于 parent_avail_balance 
	 
	function gp_amount_blur() {

		// 得到新值 旧值
		var amount = $(this);
		var old_val = parseFloat(amount.data('old_value'));
		var new_val = parseFloat(amount.val());
		
		if (old_val == new_val) { return; }

		var item = amount.parents('.gp_item').eq(0);
		var id = item.classAttr('gp_item');
		var parent_id = item.classAttr('gp_parent');
		var parent_avail_balance;
		if (parent_id == 0) {
			
			parent_avail_balance = $form.find('input[name="avail_balance"]');
			
		}
		else {
			var parent = $('.gp_item\\:' + parent_id, $table);
			
			parent_avail_balance = parent.find('input[name^="portion_avail_balance"]');
		}
		
		var balance = item.find('input[name^="portion_balance"]');
		var avail_balance = item.find('input[name^="portion_avail_balance"]');
		
		var avail_balance_val = parseFloat(avail_balance.val());
		var parent_avail_balance_val = parseFloat(parent_avail_balance.val());
		var balance_val = parseFloat(balance.val());
		
		// 计算
		new_val = Math.max(new_val, old_val - avail_balance_val);

        if (isNaN(parent_avail_balance_val)) {
            //对于修改分配数值为0后，parent_avail_balance_val会为NaN，导致无法分配份额
            //grant_avail_balance 为input中的可分配份额，最大不可超过grant的可分配份额
            var grant_avail_balance_val = $('input[name="grant_avail_balance"]').val();
            new_val = Math.min(new_val, grant_avail_balance_val);
        }
        else {
		    new_val = Math.min(new_val, old_val + parent_avail_balance_val);
        }

		parent_avail_balance_val -= new_val - old_val;
		avail_balance_val += new_val - old_val;
		balance_val += new_val - old_val;
		
		// 赋值
		balance.val(balance_val).change();
        amount.val(new_val).change();
		parent_avail_balance.val(parent_avail_balance_val).change();
		avail_balance.val(avail_balance_val).change();
		
	}
	
	// 点击整体的，即第一个添加按钮		
	$('thead a.gp_item_add', $table).bind('click', function(e) {
	
		// 找到 clone
		var id = gp_uniqid();
		var clone = $('tr.gp_item\\:0', $table).clone();

		// 处理  clone
		clone.find('[name="portion_parent\[0\]"]').attr('name', 'portion_parent['+id+']');
		clone.find('[name="portion_amount\[0\]"]').attr('name', 'portion_amount['+id+']');
		clone.find('[name="portion_name\[0\]"]').attr('name', 'portion_name['+id+']');
		
		// 需要处理 portion_balance 和 portion_avail_balance, 因为 错误检测
		clone.find('[name="portion_balance\[0\]"]').attr('name', 'portion_balance['+id+']');
		clone.find('[name="portion_avail_balance\[0\]"]').attr('name', 'portion_avail_balance['+id+']');
		
		clone.removeClass('gp_item:0').addClass('gp_item:'+id).addClass('gp_parent:0').removeClass('hidden');
		clone.find(':input').removeAttr('disabled');

		// 绑定 事件
		clone.find('a.gp_item_add').bind('click', {parent: clone}, gp_item_add);
		clone.find('a.gp_item_delete').bind('click', {parent: clone}, gp_item_delete);
		window.setTimeout(function(){
			clone.find('[name^="portion_amount"]').blur(gp_amount_blur);
		}, 200);

		// append
		$('tbody', $table).append(clone);

	});
	
	function gp_item_add(e){
		
		// 找到 parent
		var parent;
		
		if (e.data) { parent = e.data.parent; }
		else { parent = $(this).parents('.gp_item').eq(0); }
		
		var parent_id = parent.classAttr('gp_item');

		// 找到 clone
		var clone = $('tr.gp_item\\:0', $table).clone();
		var id = gp_uniqid();

		// 处理 clone
		clone.find('[name="portion_parent\[0\]"]').attr('name', 'portion_parent['+id+']').val(parent_id);
		clone.find('[name="portion_amount\[0\]"]').attr('name', 'portion_amount['+id+']');
		// 需要处理 portion_balance 和 portion_avail_balance, 因为 错误检测
		clone.find('[name="portion_balance\[0\]"]').attr('name', 'portion_balance['+id+']');
		clone.find('[name="portion_avail_balance\[0\]"]').attr('name', 'portion_avail_balance['+id+']');
		
		var size = parseInt(parent.find('[name="portion_name\['+parent_id+'\]"]').attr('size'), 10);
		clone.find('[name="portion_name\[0\]"]').attr('name', 'portion_name['+id+']').attr('size', size - 2).removeAttr('style');
		if (size < 18) {
			clone.find('a.gp_item_add').remove();
		}
		
		clone.removeClass('gp_item:0').addClass('gp_item:'+id).addClass('gp_parent:' + parent_id).removeClass('hidden');

		clone.find(':input').removeAttr('disabled');
		
		// 绑定事件
		clone.find('a.gp_item_add').bind('click', {parent: clone}, gp_item_add);
		clone.find('a.gp_item_delete').bind('click', {parent: clone}, gp_item_delete);
		window.setTimeout(function(){
			clone.find('[name^="portion_amount"]').blur(gp_amount_blur);
		}, 200);
		
		// append
		var p = parent;
		var pid = parent_id;
		for(;;) {
			var children = $table.find('tbody tr.gp_parent\\:' + pid);
			if (children.length == 0) {
				break;
			}
			p = children.eq(-1);
			pid = p.classAttr('gp_item');
		}
		p.after(clone);

	}
	
	$('tbody a.gp_item_add', $table).bind('click', gp_item_add);
	
	// 1. 递归删除 子节点
	// 2. 递归修改父节点 相应的值
	// parent_avail_balance_val += amount
	// parent_balance += expense
	function gp_item_delete(e){
		if( !confirm(delete_msg)) {
			return;
		}
		// 找到 parent
		var item;
	
		if (e.data) { item = e.data.parent; }
		else { item = $(this).parents('.gp_item').eq(0); }
		
		var parent_id = item.classAttr('gp_parent');

		/*
			parent_avail_balance_val += amount		
		*/
		// 顶层 item
		var parent_avail_balance;
		if(parent_id == 0){
			parent_avail_balance = $form.find('input[name="avail_balance"]').eq(0);
		}
		// 其他 item
		else{
			var parent = $('.gp_item\\:' + parent_id, $table);
			parent_avail_balance = parent.find('input[name^="portion_avail_balance"]');
		}
				
		var amount = item.find('input[name^="portion_amount"]');
		var parent_avail_balance_val = parseFloat(parent_avail_balance.val());
		var amount_val = parseFloat(amount.val());
	
		parent_avail_balance_val += amount_val;
		parent_avail_balance.val(parent_avail_balance_val).change();

		// 非顶层 record 处理，对其 parent 的 expense 和 balance 的影响
		function _gp_item_sub_expense(item, expense_val){
	
			var parent_id = item.classAttr('gp_parent');
			if (parent_id) {
				var parent = $('.gp_item\\:' + parent_id, $table);
		
				var parent_balance = parent.find('input[name^="portion_balance"]');
				var parent_balance_val = parseFloat(parent_balance.val());
				
				var parent_expense = parent.find('input[name^="portion_expense"]');
				var parent_expense_val = parseFloat(parent_expense.val());
				
				parent_expense_val -= expense_val;
				parent_expense.val(parent_expense_val).change();
				
				parent_balance_val += expense_val;
				parent_balance.val(parent_balance_val).change();
		
				_gp_item_sub_expense(parent, expense_val);
			}
		
		}
	
		if (parent_id) {
			var expense = item.find('input[name^="portion_expense"]');
			var expense_val = parseFloat(expense.val());
		
			_gp_item_sub_expense(item, expense_val);
		}
		
		function _gp_item_delete(parent){

			var parent_id = parent.classAttr('gp_item');
		
			$table.find('tbody tr.gp_parent\\:' + parent_id).each(function(){
			
				_gp_item_delete($(this));
			});
	
			parent.remove();
		}

		_gp_item_delete(item);		
	}
	
	$('tbody a.gp_item_delete', $table).bind('click', gp_item_delete);
					
});
