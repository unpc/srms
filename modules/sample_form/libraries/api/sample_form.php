<?php

class API_Sample_form extends API_Common
{
    public function put($data = [])
    {
        $this->_ready();

        $equipment = O('equipment', $data['equipment_source_id']);
        $user = O('user', $data['user_source_id']);
        $inspector = O('user', $data['inspector_source_id']);

        if (!$equipment->id
        || !$user->id || !$inspector->id) {
            return false;
        }

        $sample_element = O('sample_element', ['remote_id' => $data['id']]);
        if (!$sample_element->id) {
            $sample_element = O('sample_element');
            $sample_element->remote_id = $data['id'];
        }

        $sample_element->eq_element = $data['element_name'];
        $sample_element->equipment = $equipment;
        $sample_element->project_name = $data['project_name'];
        $sample_element->project_ref = $data['project_ref'];
        $sample_element->user = $user;
        $sample_element->user_name = $user->name;
        $sample_element->inspector = $inspector;
        $sample_element->ref = $data['ref'];
        $sample_element->status = $data['status'];
        $sample_element->price = $data['price'];
        $sample_element->ctime = $data['ctime'] ? strtotime($data['ctime']) : Date::time();

        return $sample_element->save();
    }

    public function patch($id = null, $data = [])
    {
        $this->_ready();

        $sample_element = O('sample_element', ['remote_id' => $id]);
        if (!$sample_element->id) {
            return false;
        }

        if ($data['sample_form_status']) {
            $sample_element->status = $data['sample_form_status'];
        }

        return $sample_element->save();
    }
}
