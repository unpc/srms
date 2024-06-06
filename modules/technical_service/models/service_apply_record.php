<?php

class Service_Apply_Record_Model extends Presentable_Model
{

    const STATUS_APPLY = 0;
    const STATUS_TEST = 5;

    static $status_labels = [
        self::STATUS_APPLY => '待检测',
        self::STATUS_TEST => '已检测',
    ];

    function save($overwrite = FALSE)
    {
        $this->ctime = $this->ctime ?: time();
        if (!$this->ref_no) {
            $total = Q("service_apply_record[apply={$this->apply}]")->total_count();
            $ref = $this->apply->ref_no . '-' . str_pad($total + 1, 2, 0, STR_PAD_LEFT);
            $this->ref_no = $ref;
        }
        if (!$this->dtstart && $this->connect_type) {
            $this->dtstart = time();
        }
        $result = parent::save($overwrite);
        return $result;
    }

    function & links($mode = 'index')
    {
        if (!$this->id) return [];

        $links = new ArrayIterator;
        $me = L('ME');

        switch ($mode) {
            case 'index':
                if ($me->is_allowed_to('结束检测任务', $this)) {
                    $links['result'] = [
                        'url' => $this->url(NULL, NULL, NULL, 'approval'),
                        'text' => I18N::T('technical_service', '结束服务'),
                        'tip' => I18N::T('technical_service', '结束服务'),
                        'extra' => 'class="blue" q-object="result" q-event="click"  q-static="' . H(['apply_record_id' => $this->id]) . '" q-src="' . URI::url('!technical_service/record') . '"',
                    ];
                }
                if ($me->is_allowed_to('修改检测结果', $this)) {
                    $links['result'] = [
                        'url' => $this->url(NULL, NULL, NULL, 'approval'),
                        'text' => I18N::T('technical_service', '修改结果'),
                        'tip' => I18N::T('technical_service', '修改结果'),
                        'extra' => 'class="blue" q-object="result" q-event="click"  q-static="' . H(['apply_record_id' => $this->id,'edit'=>1]) . '" q-src="' . URI::url('!technical_service/record') . '"',
                    ];
                }
                if ($me->is_allowed_to('查看结果', $this)) {
                    $links['result_view'] = [
                        'url' => $this->url(NULL, NULL, NULL, 'approval'),
                        'text' => I18N::T('technical_service', '查看结果'),
                        'tip' => I18N::T('technical_service', '查看结果'),
                        'extra' => 'class="blue" q-object="result_view" q-event="click"  q-static="' . H(['apply_record_id' => $this->id]) . '" q-src="' . URI::url('!technical_service/record') . '"',
                    ];
                }
                break;
        }

        Event::trigger('service_apply_record.links', $this, $links, $mode);

        return (array)$links;
    }

}
