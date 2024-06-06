<?php

class Vidcam_Capture_Data_Model extends Presentable_Model {

    public function get_img () {
        $path = Vidmon::video_capture_file($this->vidcam, $this->ctime);
        $url = Cache::cache_file($path);
        $img = '<img src="'.$url.'">';
        return $img;
    }

    public function get_thumbnail ($dialog = FALSE) {
        $path = Vidmon::video_alarm_thumbnail_file($this->vidcam, $this->ctime);
        $url = Cache::cache_file($path);

        //区分警报点
        $image_class = 'thumbnail';
        if ($this->is_alarm) {
            $image_class .= ' alarm';
        }
        $width = Config::get('vidmon.thumbnail_width');
        $height = Config::get('vidmon.thumbnail_height');


        $img = '<img class="'.$image_class.'" src="'.$url.'" width="'.$width.'" height="'.$height.'">';
        if ($dialog) {
            $img = '<a q-object="view_img"
                q-event="click"
                q-static="img_id='.$this->id.'"
                q-src="'. URI::url('!vidmon/vidcam').'">'.$img.'</a>';
        }
        return $img;
    }
}
