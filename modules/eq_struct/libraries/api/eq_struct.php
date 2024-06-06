<?php
class API_Eq_Struct extends API_Common
{
    public function getEqStructs($start, $step = 20)
    {
        $this->_ready();

        $eqStructs = Q('eq_struct')->limit($start, $step);

        $items = [];

        foreach ($eqStructs as $eqStruct) {
            $items[] = [
                'token' => $eqStruct->token,
                'name' => $eqStruct->name,
                'group' => $eqStruct->group,
                // 由于历史命名原因，下面没写错
                'prono' => $eqStruct->ref_no,
                'depno' => $eqStruct->proj_no
            ];
        }

        return $items;
    }

    public function getStructByEquipment($eqIds)
    {
        $this->_ready();

        if (!count($eqIds)) {
            return [];
        }

        $ret = [];
        foreach ($eqIds as $id) {
            $equipment = O('equipment', $id);
            if (!$equipment->id) {
                continue;
            }

            $struct = $equipment->struct;
            $struct = new ArrayIterator([
                'name' => $struct->name,
                'proj_no' => $struct->proj_no,
                'card_no' => $struct->card_no,
                'type' => $struct->type,
            ]);

            Event::trigger('eq_struct.api.get_by_equipment', $struct, $equipment);

            $ret[$equipment->id] = $struct;
        }

        return (array)$ret;
    }
}
