<?php

class API_YiQiKong_Sample extends API_Common
{

    public function create($data)
    {
        $this->_ready();
        try {
            $res = Common_Sample::create($data);
            return $res;
        } catch (API_Exception $e) {
            throw $e;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function update($id, $data)
    {
        try {
            $res = Common_Sample::update($id, $data);
            return $res;
        } catch (API_Exception $e) {
            throw $e;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function delete($id, $data)
    {
        try {
            $res = Common_Sample::delete($id, $data);
            return $res;
        } catch (API_Exception $e) {
            throw $e;
        } catch (\Exception $e) {
            throw $e;
        }
    }

}
