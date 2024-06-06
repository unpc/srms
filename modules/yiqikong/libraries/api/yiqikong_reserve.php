<?php

class API_YiQiKong_Reserve extends API_Common
{
    public function delete($data)
    {
        $this->_ready();
        try {
            return Common_Reserve::delete($data);
        } catch (API_Exception $e) {
            throw $e;
        } catch (\Exception $e) {
            throw $e;
        }
    }
}