<?php

class CLI_Gapper_Address
{

    public static function sync_address()
    {

        $area = Gateway::getLocationArea();
        $root = Tag_Model::root('location');

        if($area['items'] && $area['total']){

            foreach($area['items'] as $area){
                
                $aid = 'area_'.$area['id'];
                $oldaid = 'area'.$area['id'];
                $location = O('tag_location',['gapper_id'=>$aid]);
                $location = $location->id ? $location : O('tag_location',['gapper_id'=>$oldaid]);
                $location->code = $area['code'];
                $location->name = $area['name'];
                $location->gapper_id = $aid;
                $location->parent = $root;
                $location->root = $root;

                if($location->save()){
                    $total = -1;
                    $page = 1;
                    $perPage = 1;
                    while ($total === -1 || $total >= ($page - 1) * $perPage) {
                        $buildings = Gateway::getLocationAreaBuildings([
                            'area_id' => $area['id'],
                            'pp' => $perPage,
                            'pg' => $page,
                        ]);

                        $total = $buildings['total'];
                        foreach ($buildings['items'] as $remote_build_info) {
                            $bid = 'build_'.$remote_build_info['id'];
                            $oldbid = 'build'.$remote_build_info['id'];
                            $build = O('tag_location',['gapper_id'=>$bid]);
                            $build = $build->id ? $build : O('tag_location',['gapper_id'=>$oldbid]);
                            $build->code = $remote_build_info['code']??'';
                            !$build->id ? ($build->name = $remote_build_info['name']??'') : '';
                            $build->root = $root;
                            $build->gapper_id = $bid;
                            $build->parent = $location;
                            if($build->save()){
                                $rtotal = -1;
                                $rpage = 1;
                                while ($rtotal === -1 || $rtotal >= ($rpage - 1) * $perPage) {
                                    $rooms = Gateway::getLocationAreaBuildingRooms([
                                        'building_id' => $remote_build_info['id'],
                                        'pp' => $perPage,
                                        'pg' => $rpage,
                                    ]);
                                    $rtotal = $rooms['total'];
                                    foreach ($rooms['items'] as $remote_room_info) {
                                        $rid = 'room_'.$remote_room_info['id'];
                                        $oldrid = 'room'.$remote_room_info['id'];
                                        $room = O('tag_location',['gapper_id'=>$rid]);
                                        $room = $room->id ? $room : O('tag_location',['gapper_id'=>$oldrid]);
                                        $room->code = $remote_room_info['code'];
                                        $room->name = $remote_room_info['name'];
                                        $room->root = $root;
                                        $room->gapper_id = $rid;
                                        $room->parent = $build; 
                                        $room->save();
                                    }
                                    $rpage++;
                                }
                            }
                        }

                        $page++;
                    }
                }
            }

        }

        Upgrader::echo_success("Done.");
    }

    public function test()
    {
        // $res = Gateway::getLocationArea();
        // $res2 = Gateway::getLocationAreaBuildings(['area_id'=>2]);
        // $res3 = Gateway::getLocationAreaBuildingRooms(['building_id'=>1]);
        // var_dump($res);
        // var_dump($res2);
        // var_dump($res3);
    }
}
