<?php

class UE_Training_Model extends Presentable_Model {

    protected $object_page = [
        'apply'=>'!equipments/training/apply_user[.%arguments]',
        'approve'=>'!equipments/training/approve_user.%id',
        'delete'=>'!equipments/training/reject_user.%id.delete',
        'reject'=>'!equipments/training/reject_user.%id.reject',
    ];

    const STATUS_APPLIED = 1; // 申请
    const STATUS_APPROVED = 2; // 批准
    const STATUS_REFUSE = 3; // 申请培训被拒绝
    const STATUS_OVERDUE = 4; // 过期
    const STATUS_AGAIN = 5; // 再次申请
    const STATUS_DELETED = 6; // 已删除

    function & links($mode = 'applied') {
        $links = new ArrayIterator;
        $me = L('ME');
        switch ($mode) {
        case 'approved':
            $links['edit'] = [
                'url' => '#',
                'tip' => I18N::T('equipments', '修改'),
                'text' => I18N::T('equipments', '修改'),
                'extra' => 'class="blue"
                    q-event="click"
                    q-object="edit_approved_user"
                    q-static="'.HT(['tid'=>$this->id]).'"
                    q-src="'.URI::url('!equipments/training').'"',
				'weight' => 100,
            ];
            $links['delete'] = [
                'url' => $this->url(NULL, NULL, NULL, 'delete'),
                'tip' => I18N::T('equipments', '删除'),
                'text' => I18N::T('equipments', '删除'),
                'extra' => 'class="blue"
                	confirm="'.I18N::T('equipments', '您确定删除该用户的培训资格吗?').'"',
                'weight' => 99,
            ];
            break;
        case 'overdue':
            $links['delete'] = [
                'url' => $this->url(NULL, NULL, NULL, 'delete'),
                'text' => I18N::T('equipments', '删除'),
                'tip' => I18N::T('equipments', '删除'),
                'extra' => 'class="blue nowrap"
                    confirm="'.I18N::T('equipments', '您确定删除该用户的培训资格吗?').'"',
            ];
            break;
        case 'batch_overdue':
            $links['approve'] = [
                'url' => '#',
                'text' => I18N::T('equipments', '批准'),
                'extra' => 'class="blue nowrap"
                    q-event="click"
                    q-object="batch_overdue"
                    q-static="'.HT(['tid'=>$this->id]).'"
                    q-src="'.URI::url('!equipments/training').'"',
            ];
            break;
        case 'applied':
        default:
            $links['approve'] = [
                'url' => '#',
                'text' => '<span class="after_icon_span">'.I18N::T('equipments', '批准').'</span>',
                'tip' => I18N::T('equipments', '批准'),
                'extra' => 'class="blue"
                            q-event="click"
                            q-object="approve_user"
                            q-static="'.HT(['tid'=>$this->id]).'"
                            q-src="'.URI::url('!equipments/training').'"',
            ];
            $links['reject'] = [
                'url' => $this->url(NULL, NULL, NULL, 'reject'),
                'text' => '<span class="after_icon_span">'.I18N::T('equipments', '拒绝').'</span>',
                'tip' => I18N::T('equipments', '拒绝'),
                'extra' => 'class="blue" confirm="'.I18N::T('equipments', '您确定拒绝该用户的培训申请吗?').'"',
                'weight' => 99,
            ];

        }
        return (array) $links;
    }

}
