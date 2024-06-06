<?php

//billing api 类
class API_Billing {

    //验证失败返回错误信息
    const AUTH_FAILED = 0;

    //无权操作错误返回信息
    const PERMISSION_DENIED = 1;

    const DEP_NOT_FOUND = 2;

    const USER_NOT_FOUND = 3;

    const USER_NOT_PI = 4;

    const ACC_NOT_FOUND = 5;

    const ACC_NOT_BALANCE = 6;


    //检测本地账号绑定远程账号是否正确,传入本地account的source和voucher
    function check_remote_account($source, $voucher = null){
        if(!$source) return FALSE;

        if($source == 'local') {
            throw new API_Exception(T('未绑定远程账号!'), self::AUTH_FAILED);
            return FALSE;
        }

        $server = Scope_Api::current_server();
        
        if(!$voucher || $server != $source) {
            throw new API_Exception(T('远程账号绑定错误!'), self::AUTH_FAILED);
            return FALSE;
        }

        return TRUE;
    }
    /*
     * 获取财务账号信息函 
	 * @params
     *   department      string      财务部门识别ID
     *   username        string      财务账号所属课题组PI的一卡通账号
     * @return
     *   NULL: 当前服务器不存在该账号
     *   JSON字串:字串财务账号信息集合
     *   {
     *       "name": "xx",           // 财务账号名称(财务部门名称)
     *       "debit": xxx,          // 账号总收入
     *       "credit": xxx,         // 账号总支出
     *       "balance": "x",        // 可用余额
     *       "source": 'xxx'        //账号来源
     *       "voucher" :xx          //远程账号id
     *   }
     */
    function get_account_info($department, $username) {

        if (!Scope_Api::is_authenticated('billing')) {
            throw new API_Exception(T('访问未授权!'), self::AUTH_FAILED);
        }

        $department = O('billing_department', ['nickname'=> $department]);

        if (!$department->id) {
            throw new API_Exception(T('没有找到相应财务中心!'), self::DEP_NOT_FOUND);
        }

        $user = O('user', ['token' => $username]);

        if (!$user->id) {
            throw new API_Exception(T('没有找到相应用户!'), self::USER_NOT_FOUND);
        }

        $lab = O('lab', ['owner'=>$user]);
        if (!$lab->id) {
            throw new API_Exception(T('非课题组负责人!'), self::USER_NOT_PI);
        }

		$account = O('billing_account', ['lab'=>$lab, 'department'=>$department]);
        if (!$account->id) {
            throw new API_Exception(T('没有找到相应财务账号!'), self::ACC_NOT_FOUND);
        }

        $server = Scope_Api::current_server();
        Log::add(strtr('%server_name获取了nickname为 %department_name 实验室pi账号为 %user_name 的财务账号的信息', [
        				'%server_name' => $server,
        				'%department_name' => $department,
        				'%user_name' => $username,
        ]), 'billing_api');

        //账号总收入
        //远程已确定收入 + 本地充值收入 + 本地调账收入
        $debit = $account->income_remote_confirmed + $account->income_local + $account->income_transfer;

        //账号总支出
        //远程扣费 +  本地扣费 + 调账扣费 + 本地使用
        $credit = $account->outcome_remote + $account->outcome_local + $account->outcome_transfer + $account->outcome_use;

        //账号余额
        $balance = $account->balance;

        return [
            'name' => $department->name,
            'debit'=> (float) $debit,
            'credit'=> (float) $credit,
            'balance'=> (float) $balance,
            'source'=>$account->source,
            'voucher'=>$account->voucher
        ];
    }

    /*
     * 创建财务明细函数
     * @params
     *    voucher         string      远程交易凭证号
     *    data	          array       必要的财务信息
	 *		department		string		财务部门nickname
	 *		username		string		财务账号PI ID
     *    	amount          float       金额，精度为2的浮点数
     *    	note		    string      说明，纯文本
     *    	status          int         状态
     * @return
     *    * NULL: 失败
     *    * array: 操作成功之后返回过来的支付/扣费的流水号
     */
    function create_transaction($voucher, $data) {

        if (!Scope_Api::is_authenticated('billing')) {
            throw new API_Exception(T('访问未授权!'), self::AUTH_FAILED);
        }

        $server = Scope_Api::current_server();

        $current_transaction = O('billing_transaction', ['voucher' => $voucher, 'source' => $server]);
        if ($current_transaction->id) {
            return self::read_transaction($current_transaction->voucher);
        }

        $department = O('billing_department', ['nickname'=> $data['department']]);
        if (!$department->id) {
            return NULL;
        }

        $user = O('user', ['token' => $data['username']]);
        if (!$user->id) {
            return NULL;
        }

		$lab = O('lab', ['owner'=>$user]);
        if (!$lab->id) {
            return NULL;
        }

		$account = O('billing_account', ['lab'=>$lab, 'department'=>$department]);
        if (!$account->id || !self::check_remote_account($account->source, $account->voucher)) {
            return NULL;
        }

        // 2017.7.7 cheng.liu@geneegroup.com
        // 很郁闷一件事就是为嘛这个地方再最开始设计的时候没有进行余额的判断限制，这样很容易会导致超支的行为，这个不管是老师还是设备处都不应该会允许此类事件的发生阿 ????!!!!! 这TMD在最开始设计的时候为嘛想法就这么单纯呢  ?????!!!!!
        $amount = (float) $data['amount'];

        if ($amount < 0 && ($account->balance + $account->credit_line) < abs($amount)) {
            throw new API_Exception(T('实验室余额不足!'), self::ACC_NOT_BALANCE);
            return NULL;
        }

		$status = (int) $data['status'];
		$description = $data['note'];
        $servers = Config::get('rpc.servers');
        $title = $servers[$server]['title'];

		switch ($status) {
		case Billing_Transaction_Model::STATUS_CONFIRMED:
			break;
		default:
			$status = Billing_Transaction_Model::STATUS_PENDING;
		}

        $transaction = O('billing_transaction');
		$transaction->voucher = $voucher;
        $transaction->account = $account;
        $transaction->user = $user;
        $transaction->source = $server;
        $transaction->status = $status;

        if ($amount > 0) {
            $transaction->income = $amount;
        }
        else {
            $transaction->outcome = -$amount;
        }

        $note = $amount > 0 ? "$title 充值" : "$title 扣费";
        $note .= "(#$voucher)";

        $transaction->description = [
            'module' => 'billing',
            'template'=> $note,
            'amend' => H($description)
        ];


        if (!$transaction->save()) return NULL;

		$type = $amend > 0 ? '收入' : '支出';

		Log::add(strtr('%server_name在财务部门nickname为 %department_name 实验室pi账号为 %user_name 的财务账号内创建了财务明细[%transaction_id], %type %amount', [
					'%server_name' => $server,
					'%department_name' => $department,
					'%user_name' => $username,
					'%transaction_id' => $transaction->id,
					'%type' => $type,
					'%amount' => $amount,
		]), 'billing_api');

        return [
            'department'=> $department->nickname,
            'username'=> $lab->owner->token,
			'voucher' => $transaction->voucher,
            'amount'=> (float) ($transaction->income - $transaction->outcome),
            'status'=> $transaction->status
        ];

    }


    /*
     * 获取财务明细信息函数
     * @params
     *     voucher  string      扣费/充值的财务流水号 ( // 这个地方怎么可能给出来lims 的流水号？？？Fuck，都是 voucher 阿？？？？？)
     * @return
     *     * 说明: JSON串，包括传送来的所有支付处理的数据
     *
     *     {
     *         "department": "123",
     *             "username": "genee",
     *             "amount": 2000.00,
     *             "status": 123
     *     }
     *
     *     * 参数
     *         * department: 财务部门的识别ID
     *         * username: 账号所属课题组PI的一卡通账号
     *         * amount:   明细的数目
     *         * voucher : 远程交易凭证号 
     *         * status:status财务明细的审核处理状态，可能存在以下状态
     *             0 => 不存在
     *             1 => 办理中
     *             2 => 已成功
     */
    function read_transaction($voucher) {

        if (!Scope_Api::is_authenticated('billing')) {
            throw new API_Exception(T('访问未授权!'), self::AUTH_FAILED);
        }

        $server = Scope_Api::current_server();

        $transaction = O('billing_transaction', ['voucher' => $voucher, 'source' => $server]);
        if (!$transaction->id) {
            return NULL;
        }

        $account = $transaction->account;
        if(!self::check_remote_account($account->source, $account->voucher)) return NULL;

        $department = $account->department;
        
        Log::add(strtr('%server_name读取财务明细[%transaction_id]', [
        			'%server_name' => $server,
        			'%transaction_id' => $transaction->id,
        ]), 'billing_api');

        return [
            'department'=> $department->nickname,
            'username'=> $account->lab->owner->token,
			'voucher' => $transaction->voucher,
            'amount'=> (float)($transaction->income - $transaction->outcome),
            'status'=> $transaction->status
        ];
    }


    /*
     * 更新财务明细信息函数
     * @params
     *    transaction_no  string      扣费/充值的财务流水号
     *    data	          array       必要的财务信息
	 *		department		string		财务部门nickname
	 *		username		string		财务账号PI ID
     *    	amount          float       金额，精度为2的浮点数
     *    	note		    string      说明，纯文本
     *    	status          int         状态
     * @return
     *     * 说明: JSON串，包括传送来的所有支付处理的数据
     *
     *     {
     *         "department": "123",
     *             "amount": 2000.00,
     *             "status": 1,
	 * 			  "voucher": "34"
     *     }
     *     * 参数
     *         * department: 财务部门的识别ID
     *         * amount:     明细的数目
     *         * username: 账号所属课题组PI的一卡通账号
     *         * status: 	 0: pending,  1: confirmed
     *         * voucher:    远程财务凭证号
     */

    function update_transaction($voucher, $data) {

        if (!Scope_Api::is_authenticated('billing')) {
            throw new API_Exception(T('访问未授权!'), self::AUTH_FAILED);
        }

        $server = Scope_Api::current_server();

        $transaction = O('billing_transaction', ['voucher' => $voucher, 'source' => $server]);
        if (!$transaction->id) {
            return NULL;
        }

        if ($transaction->source != $server || $transaction->status != Billing_Transaction_Model::STATUS_PENDING) {
            throw new API_Exception(T('操作未授权!'), self::PERMISSION_DENIED);
        }

		$account = $transaction->account;
        if(!self::check_remote_account($account->source, $account->voucher)) return NULL;

		$department = $account->department;

		if (isset($data['amount'])) {
			$amount = (float) $data['amount'];

            if ($amount < 0 && 
                abs($amount) > $transaction->outcome && 
                ($account->balance + $account->credit_line) < (abs($amount) - $transaction->outcome)) {
                throw new API_Exception(T('实验室余额不足!'), self::ACC_NOT_BALANCE);
                return NULL;
            }

			$old_income = $transaction->income;
			$old_outcome = $transaction->outcome;

			if ($amount > 0) {
				$transaction->income = $amount;
				$transaction->outcome = 0;
			}
			else {
				$transaction->income = 0;
				$transaction->outcome = -$amount;
			}

			$income = $transaction->income;
			$outcome = $transaction->outcome;
		}

		if (isset($data['status'])) {
			$status = (int) $data['status'];
	
			switch ($status) {
			case Billing_Transaction_Model::STATUS_CONFIRMED:
				break;
			default:
				$status = Billing_Transaction_Model::STATUS_PENDING;
			}

			$transaction->status = $status;

		}


		if (isset($data['note'])) {
			$description = $data['note'];
			$transaction->description = [
					'module' => 'billing',
					'template'=> '修改财务明细',
					'amend'=>H($description)
					];
		}

		if (!$transaction->save()) return NULL;

		if (isset($data['amount'])) {
			Log::add(strtr('%server_name更新了财务明细[%transaction_id] 修改前支出%old_outcome 收入%old_income 修改后支出%outcome 收入%income', [
						'%server_name' => $server,
						'%transaction_id' => $transaction->id,
						'%old_outcome' => $old_outcome,
						'%old_income' => $old_income,
						'%outcome' => $outcome,
						'%income' => $income,
			]), 'billing_api');
		}

		return [
				'department' => $department->nickname,
            	'username'=> $account->lab->owner->token,
				'voucher' => $transaction->voucher,
				'amount'=> (float)($transaction->income - $transaction->outcome),
                'status'=> $transaction->status
            ];
    }


    /*
     * 删除财务明细信息
     * @params
     *    transaction_no string       扣费/充值的财务流水号
     * @return
     *    财务明细的删除结果
     *        0 => 失败
     *        1 => 成功
     */
    function delete_transaction($transaction_no) {

        if (!Scope_Api::is_authenticated('billing')) {
            throw new API_Exception(T('访问未授权!'), self::AUTH_FAILED);
        }

        $transaction = O('billing_transaction', $transaction_no);
        if (!$transaction->id) {
            return FALSE;
        }

        $account = $transaction->account;
        if(!self::check_remote_account($account->source, $account->voucher)) return NULL;

        $server = Scope_Api::current_server();
        if ($transaction->source != $server || $transaction->status != Billing_Transaction_Model::STATUS_PENDING) {
            throw new API_Exception(T('操作未授权!'), self::PERMISSION_DENIED);
        }

        $outcome = $transaction->outcome;
        $income = $transaction->income;
        $status = $transaction->status;

        $ret = $transaction->delete();
        if ($ret) {
	         Log::add(strtr('%server_name删除了财务明细[%transaction_id] 支出%outcome 收入%income 状态%status', [
	        			'%server_name' => $server,
	        			'%transaction_id' => $transaction->id,
	        			'%outcome' => $outcome,
	        			'%income' => $income,
	        			'%status' => $status,
	        ]), 'billing_api');
            return TRUE;
        }

		return FALSE;
    }

    function bind($department, $username, $voucher){
        if(!$voucher){
            throw new API_Exception(T('远程账号id错误!'), self::AUTH_FAILED);
        }

        if (!Scope_Api::is_authenticated('billing')) {
            throw new API_Exception(T('访问未授权!'), self::AUTH_FAILED);
        }

        $department = O('billing_department', ['nickname'=> $department]);

        if (!$department->id) {
            return NULL;
        }

        $user = O('user', ['token' => $username]);

        if (!$user->id) {
            return NULL;
        }

        $lab = O('lab', ['owner'=>$user]);
        if (!$lab->id) {
            return NULL;
        }

		$account = O('billing_account', ['lab'=>$lab, 'department'=>$department]);
        if (!$account->id) {
            return NULL;
        }

        //绑定远程账号
        $server = Scope_Api::current_server();
        $account->source = $server;
        $account->voucher = $voucher;
        if($account->save()){
            return TRUE;
        }

        return FALSE;

    }

    function unbind($department, $username) {

        if (!Scope_Api::is_authenticated('billing')) {
            throw new API_Exception(T('访问未授权!'), self::AUTH_FAILED);
        }

        $department = O('billing_department', ['nickname'=> $department]);

        if (!$department->id) {
            return NULL;
        }

        $user = O('user', ['token' => $username]);

        if (!$user->id) {
            return NULL;
        }

        $lab = O('lab', ['owner'=>$user]);
        if (!$lab->id) {
            return NULL;
        }

        $account = O('billing_account', ['lab'=>$lab, 'department'=>$department]);
        if (!$account->id) {
            return NULL;
        }

        $account->source = 'local';
        $account->voucher = '';
        if($account->save()){
            return TRUE;
        }

        return FALSE;
    }
}
