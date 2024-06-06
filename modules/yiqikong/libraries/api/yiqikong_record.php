<?php

class API_YiQiKong_Record extends API_Common
{

    public function create($data)
    {
        $this->_ready();
        try {
            $res = Common_Record::create($data);
            return $res;
        } catch (API_Exception $e) {
            throw $e;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function update($id, $data)
    {
        $this->_ready();
        try {
            $res = Common_Record::update($id, $data);
            return $res;
        } catch (API_Exception $e) {
            throw $e;
        } catch (\Exception $e) {
            throw $e;
        }
    }

}
