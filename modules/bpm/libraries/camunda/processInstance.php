<?php

namespace Gini\BPM\Camunda;

class ProcessInstance implements \Gini\BPM\Driver\ProcessInstance {

    private $camunda;
    private $id;
    private $data;

    public function __construct($camunda, $id, $data=null) {
        $this->camunda = $camunda;
        $this->id = $id;
        if ($data) {
            $this->data = (array) $data;
        }
    }

    private function _fetchInstance() {
        if (!$this->data) {
            $id = $this->id;
            try {
                $this->data = $this->camunda->get("history/process-instance/$id");
            } catch (\Gini\BPM\Exception $e) {
                $this->data = [];
            }
        }
    }

    public function exists() {
        $this->_fetchInstance();
        return isset($this->data['id']);
    }

    public function delete() {
        if ($this->id) {
            $id = $this->id;
            try {
                error_log("process-instance/$id");
                // $this->data = $this->camunda->delete("process-instance/$id");
            } catch (\Gini\BPM\Exception $e) {
                $this->data = [];
            }
        }
    }

    public function __get($name) {
        if ($name == 'id') {
            return $this->id;
        }

        $this->_fetchInstance();
        return $this->data[$name];
    }

    public function getData() {
        $this->_fetchInstance();
        return $this->data;
    }

    /**
     * [setVariables Sets a variable of a given process instance by id.]
     * @param array $criteria [criteria]
     * @param type : boolean,interger,double,string,null,file,object,json,xml
     */
    public function setVariable(array $criteria) {
        $id = $this->id;
        if (!$id) return ;

        if (!$criteria['variableName'] || !$criteria['value'] || !$criteria['type']) {
            return ;
        }

        $query = [];
        $varName = $criteria['variableName'];
        $query['value'] = $criteria['value'];
        $query['type'] = $criteria['type'];
        if ($criteria['valueInfo']) {
            $query['valueInfo'] = $criteria['valueInfo'];
        }

        $result = $this->camunda->put("process-instance/$id/variables/$varName", $query);
        return empty($result) ? true : false;
    }

    /**
     * [getVariables Retrieves all variables or a a variable from the instance.]
     * @return [array] [A JSON object of variables key-value pairs. ]
     */
    public function getVariables(array $criteria, $start=0, $perPage=25) {
        $id = $this->id;
        if (!$id) return ;

        $query = [];
        $query['processInstanceId'] = $id;
        if ($criteria['processInstanceIdIn']) {
            unset($query['processInstanceId']);
            $query['processInstanceIdIn'] = $criteria['processInstanceIdIn'];
        }
        if ($criteria['variableName']) {
            $result = $this->_makeQuery($criteria['variableName']);
            if ($result['like']) {
                $query['variableNameLike'] = $result['pattern'];
            } else {
                $query['variableName'] = $result['pattern'];
            }
        }
        if ($criteria['taskIdIn']) {
            $query['taskIdIn'] = $criteria['taskIdIn'];
        }
        if ($criteria['sorting']) {
            $query['sorting'] = $criteria['sorting'];
        }

        $result = $this->camunda->post("history/variable-instance?deserializeValues=false&firstResult=$start&maxResults=$perPage", $query);
        return empty($result) ? false : $result;
    }

    private function _makeQuery($name)
    {
        $pos = strpos($name, '=');
        if (!$pos) {
            if ($pos === 0) {
                $val = substr($name, $pos+1);
                return ['pattern' => $val];
            } else {
                return ['pattern' => $name];
            }
        }
        else {
            $val = substr($name, $pos+1);
            $pos--;
            $opt = $name[$pos].'=';

            switch ($opt) {
                case '^=': {
                    $pattern = $val.'%';
                }
                break;

                case '$=': {
                    $pattern = '%'.$val;
                }
                break;

                case '*=': {
                    $pattern = '%'.$val.'%';
                }
                break;
            }

            return [
                'like' => true,
                'pattern' => $pattern
            ];
        }
    }
}
