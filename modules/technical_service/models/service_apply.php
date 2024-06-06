<?php

class Service_Apply_Model extends Presentable_Model
{

    const STATUS_APPLY = 0;
    const STATUS_PASS = 5;
    const STATUS_REJECT = 10;
    const STATUS_SERVING = 15;
    const STATUS_DONE = 20;
    public static $status_labels = [
        self::STATUS_APPLY => '待审批',
        self::STATUS_PASS => '已审批',
        self::STATUS_REJECT => '已拒绝',
        self::STATUS_SERVING => '服务中',
        self::STATUS_DONE => '已完成',
    ];

    protected $object_page = [
        'view' => '!technical_service/apply/index.%id[.%arguments]',
        'edit' => '!technical_service/apply/edit.%id[.%arguments]',
        'delete' => '!technical_service/apply/delete.%id[.%arguments]',
    ];

    function & links($mode = 'index')
    {
        if (!$this->id) return [];

        $links = new ArrayIterator;
        $me = L('ME');

        switch ($mode) {
            case 'index':
                if ($me->is_allowed_to('查看', $this)) {
                    $links['view'] = [
                        'url' => $this->url(NULL, NULL, NULL, 'view'),
                        'text' => I18N::T('technical_service', '查看'),
                        'tip' => I18N::T('technical_service', '查看'),
                        'extra' => 'class="blue" q-object="detail" q-event="click"  q-static="' . H(['apply_id' => $this->id]) . '" q-src="' . URI::url('!technical_service/apply') . '"',
                    ];
                }
                if ($me->is_allowed_to('修改', $this)) {
                    $links['edit'] = [
                        'url' => $this->url(NULL, NULL, NULL, 'edit'),
                        'text' => I18N::T('technical_service', '修改'),
                        'tip' => I18N::T('technical_service', '修改'),
                        'extra' => 'class="blue" q-object="edit" q-event="click"  q-static="' . H(['apply_id' => $this->id]) . '" q-src="' . URI::url('!technical_service/apply') . '"',
                    ];
                }
                if ($me->is_allowed_to('审批', $this)) {
                    $links['approval'] = [
                        'url' => $this->url(NULL, NULL, NULL, 'approval'),
                        'text' => I18N::T('technical_service', '审批'),
                        'tip' => I18N::T('technical_service', '审批'),
                        'extra' => 'class="blue" q-object="approval" q-event="click"  q-static="' . H(['apply_id' => $this->id]) . '" q-src="' . URI::url('!technical_service/apply') . '"',
                    ];
                }
                if ($me->is_allowed_to('下载结果', $this)) {
                    $links['result'] = [
                        'url' => $this->url(NULL, NULL, NULL, 'result'),
                        'text' => I18N::T('technical_service', '下载'),
                        'tip' => I18N::T('technical_service', '下载'),
                        'extra' => 'class="blue" q-object="export_result" q-event="click" q-src="' . URI::url('!technical_service/apply') .'" q-static="' . H(['type' => 'word', 'id' => $this->id]).'"'
                    ];
                }
                if ($me->is_allowed_to('删除', $this)) {
                    $links['delete'] = [
                        'url' => $this->url(NULL, NULL, NULL, 'delete'),
                        'text' => I18N::T('technical_service', '删除'),
                        'tip' => I18N::T('technical_service', '删除'),
                        'extra' => 'class="blue" q-object="delete" q-event="click"  q-static="' . H(['apply_id' => $this->id]) . '" q-src="' . URI::url('!technical_service/apply') . '"',
                    ];
                }
                break;
        }

        Event::trigger('service_apply.links', $this, $links, $mode);

        return (array)$links;
    }

    function save($overwrite = FALSE)
    {
        $this->ctime = $this->ctime ?: time();
        if (!$this->ref_no) {
            $day_start = strtotime(date('Y-m-d', time()));
            $day_end = $day_start + 86399;
            $total = Q("service_apply[ctime>=$day_start][ctime<={$day_end}]")->total_count();
            $ref = date('ymd') . str_pad($total + 1, 3, 0, STR_PAD_LEFT);
            $this->ref_no = $ref;
        }
        $this->lab = Q("{$this->user} lab")->current();
        $result = parent::save($overwrite);
        return $result;
    }

    //总金额
    public function totalAmount()
    {
        $total_amount = 0;
        $records = Q("service_apply_record[apply={$this}]");
        foreach ($records as $record) {
            $charge = O('eq_charge', ['source' => $record]);
            $total_amount += $charge->amount ?? 0;
        }

        return $total_amount;

    }

}
