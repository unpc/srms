<?php
/*
NO.TASK282(guoping.zhang@2010.12.02）
仪器送样预约开发
 */
class EQ_Sample_Model extends Presentable_Model
{

	const STATUS_APPLIED = 1; // 申请 | 待PI审核
	const STATUS_APPROVED = 2; // 批准
	const STATUS_REJECTED = 3; // 申请培训被拒绝
	const STATUS_CANCELED = 4; // 因故取消
	const STATUS_TESTED   = 5; // 已测试
	const STATUS_SEND   = 6; // 待机主审核

    static $charge_status = [
        self::STATUS_APPROVED,
        self::STATUS_TESTED,
    ];

    static $status = [1 => '申请中', 2 => '已批准', 5 => '已测试', 3 => '已拒绝', 4 => '因故取消'];

    static $status_background_color = [1=>'f9c365', 2=>'7498e0', 3=>'E46558', 4=>'888', 5=>'6c9'];
    
    public static function setup() {
        if (Approval_Flow::sample_flow_lab()) {
            EQ_Sample_Model::$status = [1 => '待PI审核', 6 => '待机主审核', 2 => '已批准', 3 => '已拒绝', 4 => '因故取消', 5 => '已测试'];
            EQ_Sample_Model::$status_background_color = [1 => 'f9c365', 2 => '7498e0', 3 => 'E46558', 4 => '888', 5 => '6c9', 6 => 'CC6699'];
        }
        //EQ_Sample_Model::$charge_status = [self::STATUS_APPLIED, self::STATUS_APPROVED, self::STATUS_TESTED, self::STATUS_SEND];
	}

    protected $object_page = [
        'print' => '!eq_sample/sample_print.%id[.%arguments]',
        'pdf'   => '!eq_sample/pdf/view.%id[.%arguments]',
    ];

    public function &links($mode = 'index')
    {
        $links = new ArrayIterator;
        $me    = L('ME');
        switch ($mode) {
            case 'check':
                break;
            case 'dashboard':
                if ($me->is_allowed_to('修改', $this)) {
                    $links['update'] = [
                        'url'   => null,
                        'tip'   => I18N::T('eq_sample', '审批'),
                        'text' => I18N::T('eq_sample', '审批'),
                        'extra' => 'class="blue" q-object="edit_sample" q-event="click" q-static="' . H(['id' => $this->id]) . '" q-src="' . URI::url('!eq_sample/index') . '"',
                    ];
                }
                if ($me->is_allowed_to('查看', $this)) {
                    $links['view'] = [
                        'tip'  => I18N::T('eq_sample', '查看'),
                        'text' => I18N::T('eq_sample', '查看'),
                        'html' => V('eq_sample:samples_table/data/view', ['sample' => $this]),
                    ];
                }
                break;
            case 'index':
            default:
                if ($me->is_allowed_to('查看', $this)) {
                    $links['view'] = [
                        'tip'  => I18N::T('eq_sample', '查看'),
                        'text' => I18N::T('eq_sample', '查看'),
                        'html' => V('eq_sample:samples_table/data/view', ['sample' => $this]),
                    ];
				}
				
                if ($me->is_allowed_to('修改', $this)) {
                    $links['update'] = [
                        'url'   => null,
                        'tip'   => I18N::T('eq_sample', '编辑'),
                        'text' => I18N::T('eq_sample', '编辑'),
                        'extra' => 'class="blue" q-object="edit_sample" q-event="click" q-static="' . H(['id' => $this->id]) . '" q-src="' . URI::url('!eq_sample/index') . '"',
                    ];
				}

				if ($me->is_allowed_to('发送消息', $this) && Config::get('messages.add_message.switch_on', true)) {
                    $links['message'] = [
                        'url'   => '!eq_sample/index/send.' . $this->id,
                        'tip'  => I18N::T('eq_sample', '发送消息'),
                        'text' => I18N::T('eq_sample', '发送消息'),
                        'extra'  => 'class="blue"',
                    ];
				}
				
				if ($me->is_allowed_to('发送报告', $this)) {
                    $links['report'] = [
                        'url'   => null,
                        'tip'  => I18N::T('eq_sample', '发送报告'),
                        'text' => I18N::T('eq_sample', '发送报告'),
                        'extra' => 'class="blue" q-src="' . URI::url('!eq_sample/index') . '" q-static="id=' . $this->id . '" q-event="click" q-object="send_report"',
                    ];
                }

                if ($me->is_allowed_to('修改', $this)) {
                    $links['delete'] = [
                        'url'   => null,
                        'tip'  => I18N::T('eq_sample', '删除'),
                        'text' => I18N::T('eq_sample', '删除'),
                        'extra' => 'class="blue" q-object="delete_sample" q-event="click" q-static="' . H(['id' => $this->id]) . '" q-src="' . URI::url('!eq_sample/index') . '"',
                    ];
                }

                if ($me->is_allowed_to('删除', $this)) {
                    if (Module::is_installed('db_sync') && DB_SYNC::is_slave() && Module::is_installed('equipment')) {
                        $extra_links['delete'] = [
                            'url' => Event::trigger('db_sync.transfer_to_master_url', '', ['q_params' => [
                                'q-object' => 'delete_sample',
                                'q-event'  => 'click',
                                'q-static' => ['id' => $this->id],
                                'q-src'    => Event::trigger('db_sync.transfer_to_master_url', '!eq_sample/index', '', true),
                            ]]),
                            'text' => I18N::T('eq_sample', '删除'),
                            'extra'=>' class="blue"',
                        ];
                    }else{
                        $extra_links['delete'] = [
                            'url' => NULL,
                            'text' => I18N::T('eq_sample', '删除'),
                            'extra' => 'class="blue" q-object="delete_sample" q-event="click" q-static="' .  H(['id'=>$this->id]) . '" q-src="' . URI::url('!eq_sample/index') . '"',
                        ];
                    }
                }

                //浙江医学院实时推送之后判断是否能修改
                $canEdit = Event::trigger('eq_charge_can_edit',$this);
                if(true === $canEdit){
                    unset($extra_links['delete']);
                }
				
                /* $extra_links = [];
                if ($me->is_allowed_to('发送消息', $this) && Config::get('messages.add_message.switch_on', true)) {
                    $extra_links['message'] = [
                        'url'   => '!eq_sample/index/send.' . $this->id,
                        'tip'  => I18N::T('eq_sample', '发送消息'),
                        'text'  => '',
                        'extra'  => 'class="icon-message"',
                    ];
                } */

                /* if ($me->is_allowed_to('发送报告', $this)) {
                    $extra_links['report'] = [
                        'url'   => null,
                        'tip'  => I18N::T('eq_sample', '发送报告'),
                        'text'  => '',
                        'extra' => 'class="icon-message" q-src="' . URI::url('!eq_sample/index') . '" q-static="id=' . $this->id . '" q-event="click" q-object="send_report"',
                    ];
                } */

                /* if ($me->is_allowed_to('修改', $this)) {
                    $extra_links['delete'] = [
                        'url'   => null,
                        'tip'  => I18N::T('eq_sample', '删除'),
                        'text'  => '',
                        'extra' => 'class="icon-trash" q-object="delete_sample" q-event="click" q-static="' . H(['id' => $this->id]) . '" q-src="' . URI::url('!eq_sample/index') . '"',
                    ];
                } */

                /* if (count($extra_links)) {
                    $links['extra'] = [
                        'html' => (string) V('eq_sample:samples_table/data/extra', ['sample' => $this, 'extra_links' => $extra_links]),
                    ];
                } */
        }

        Event::trigger('eq_sample.links', $this, $links, $mode);

        return (array) $links;
    }

    public function save($overwrite = false)
    {
        $this->sender_abbr    = PinYin::code($this->sender->name);
        $this->operator_abbr  = PinYin::code($this->operator->name);
        $this->equipment_abbr = PinYin::code($this->equipment->name);
        return parent::save($overwrite);
    }
}
