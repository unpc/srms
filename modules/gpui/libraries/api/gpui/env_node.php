<?php


class API_GPUI_Env_Node extends API_Common
{
    public function temperatureByLab($params = []){
        $this->_ready('gpui');

        if (!isset($params['location']) || !isset($params['location2'])) {
            return [];
        }

        $data = [];

        foreach (Q("env_node[location={$params['location']}][location2={$params['location2']}]") as $node) {
            $sensors = [];
            foreach (Q("{$node} env_sensor.node") as $sensor) {
                $sensors[] = [
                    "sensor_name" => H($sensor->name),
                    "unit" => $sensor->unit(),
                    "value" => $sensor->value,
                ];
            }
            $data[] = [
                "node_id" => $node->id,
                "node_name" => H($node->name),
                "sensors" => $sensors
            ];
        }

        return $data;

    }
}
