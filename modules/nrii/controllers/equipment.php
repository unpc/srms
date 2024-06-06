<?php

class Equipment_Controller extends Base_Controller {

    function index($id=0) {
        $equipment = O('equipment', (int)$id);
        if (!$equipment->id || !$equipment->nrii_share) {
            URI::redirect(URI::url('!nrii/nrii.equipment'));
        }

        $nrii_equipment = O('nrii_equipment', ['eq_id' => $equipment->id]);
        if ($nrii_equipment->id) {
            URI::redirect(URI::url('!nrii/equipment/edit.'.$nrii_equipment->id));
        }
        else {
            URI::redirect(URI::url('!nrii/equipment/add', ['eq_id' => $equipment->id]));
        }
    }
    function add() {

        $form = Form::filter(Input::form());

        $me = L('ME');
        $equipment = O('equipment', $form['eq_id']);
        if (!$me->is_allowed_to('管理', 'nrii') && !Q("{$equipment}<incharge {$me}")->total_count()) URI::redirect('error/401');

        if ($form['submit']) {
            if(empty($form['eq_id'])){
                $form->set_error('eq_id', I18N::T('nrii', '请关联仪器!'));
            }
            $form = $form
                ->validate('eq_id', 'not_empty', I18N::T('nrii', '请关联仪器!'))
                ->validate('eq_name', 'not_empty', I18N::T('nrii', '请输入仪器设备名称!'))
                ->validate('ename', 'not_empty', I18N::T('nrii', '请输入英文名称!'))
                ->validate('innerId', 'not_empty', I18N::T('nrii', '请输入所属单位科学装置编号!'))
                ->validate('worth', 'not_empty', I18N::T('nrii', '请输入正确原值!'))
                ->validate('worth', 'is_numeric', I18N::T('nrii', '请输入正确原值!'))
                ->validate('street', 'not_empty', I18N::T('nrii', '请输入安放地址!'))
                ->validate('nation', 'not_empty', I18N::T('nrii', '请输入产地国别!'))
                ->validate('model_no', 'not_empty', I18N::T('nrii', '请输入规格型号!'))
                ->validate('manufacturer', 'not_empty', I18N::T('nrii', '请输入生产制造商!'))
                ->validate('beginDate', 'not_empty', I18N::T('nrii', '请选择建账日期!'))
                ->validate('technical', 'not_empty', I18N::T('nrii', '请输入主要技术指标!'))
                ->validate('function', 'not_empty', I18N::T('nrii', '请输入主要功能!'))
                ->validate('requirement', 'not_empty', I18N::T('nrii', '请输入对外开放共享规定!'))
                ->validate('fee', 'not_empty', I18N::T('nrii', '请输入参考收费标准!'))
                ->validate('serviceContent', 'not_empty', I18N::T('nrii', '请输入服务内容!'))
                ->validate('contact', 'not_empty', I18N::T('nrii', '请输入联系人!'))
                ->validate('phone', 'not_empty', I18N::T('nrii', '联系人电话填写有误!'))
                ->validate('email', 'not_empty', I18N::T('nrii', '联系人电子邮箱填写有误!'))
                ->validate('email', 'is_email', I18N::T('nrii', '联系人电子邮箱填写有误!'))
                ->validate('contact_address', 'not_empty', I18N::T('nrii', '请输入通信地址!'))
                ->validate('zip_code', 'not_empty', I18N::T('nrii', '邮政编码输入有误!'))
                ->validate('zip_code', 'is_numeric', I18N::T('nrii', '邮政编码输入有误!'))
                ->validate('zip_code', 'length(6)', I18N::T('nrii', '邮政编码输入有误!'))
                ->validate('run_machine', 'is_numeric', I18N::T('nrii', '年总运行机时输入有误!'))
                ->validate('service_machine', 'is_numeric', I18N::T('nrii', '年服务机时输入有误!'))
                ->validate('inside_depart', 'not_empty', I18N::T('nrii', '所属单位内部门输入有误!'))
                ;

            if ($form['customs'] == 1){
                $form = $form
                    ->validate('cus_inner_id', 'not_empty', I18N::T('nrii', '请输入单位内部编号!'))
                    ->validate('cus_ins_code', 'not_empty', I18N::T('nrii', '请输入所属单位标识!'))
                    ->validate('cus_declaration_number', 'not_empty', I18N::T('nrii', '请输入进口报关单编号!'))
                    ->validate('cus_item_number', 'not_empty', I18N::T('nrii', '请输入进口报关单项号!'))
                    ->validate('cus_import_date', 'not_empty', I18N::T('nrii', '请输入海关放行日期!'))
                    ->validate('cus_form_name', 'not_empty', I18N::T('nrii', '请输入仪器设备进口报关单名称!'));
                Event::trigger('nrii.equipment.customs.add_columns.validate',$form);
            }

            if($form['affiliate'] == -1){
                $form->set_error('affiliate', I18N::T('nrii', '请选择是否附属于其他设备!'));
            }
            if($form['affiliate'] != 4){
                $form = $form->validate('affiliate_name', 'not_empty', I18N::T('nrii', '请输入所附属仪器名称!'));
            }else{
                $form['affiliate_name'] = '无';
            }
            
            if($form['class_lg'] == -1 || $form['class_md'] == -1 || $form['class_sm'] == -1){
                $form->set_error('class_lg', I18N::T('nrii', '请选择设备分类!'));
                $form->set_error('class_md', I18N::T('nrii', ''));
                $form->set_error('class_sm', I18N::T('nrii', ''));
            }
            if($form['province'] == -1 || $form['city'] == -1 || $form['area'] == -1){
                $form->set_error('province', I18N::T('nrii', '请选择安放地址!'));
                $form->set_error('city', I18N::T('nrii', ''));
                $form->set_error('area', I18N::T('nrii', ''));
            }
            if($form['worth'] < 0 ){
                $form->set_error('worth', I18N::T('nrii', '仪器价格不能设置为负数'));
            }
            if($form['eq_source'] == -1){
                $form->set_error('eq_source', I18N::T('nrii', '请选择仪器设备来源!'));
            }
            if($form['type_status'] == -1){
                $form->set_error('type_status', I18N::T('nrii', '请选择仪器设备类别!'));
            }

            if ($form['realm'] == '{}' || empty($form['realm'])) {
                $form->set_error('realm', I18N::T('nrii', '请输入主要科学领域!'));
            }
            elseif(count(json_decode($form['realm'], true)) > 4){
                $form->set_error('realm', I18N::T('nrii', '主要科学领域填写不能超过4个!'));
            }
            if ($form['funds'] == '{}' || !$form['funds']) {
                $form->set_error('funds', I18N::T('nrii', '请输入主要购置经费来源!'));
            }

            $equipment = O('nrii_equipment', ['eq_id' => $form['eq_id']]); 
            if ($equipment->id){
                $form->set_error('eq_id', I18N::T('nrii', '关联仪器已经被其他数据关联!'));
            }
            $equipment = O('nrii_equipment', ['inner_id' => $form['innerId']]); 
            if ($equipment->id){
                $form->set_error('innerId', I18N::T('nrii', "已存在所属单位科学装置编号为{$form['innerId']}的仪器!"));
            }
            $equipment = O("nrii_equipment");
            Event::trigger('nrii.equipment.add_columns.validate',$form);
            if($form->no_error){
                $customs = O('nrii_customs');
                if ($form['customs'] == 1){
                    $customs->inner_id = $form['cus_inner_id'];
                    $customs->ins_code = $form['cus_ins_code'];
                    $customs->declaration_number = $form['cus_declaration_number'];
                    $customs->import_date = $form['cus_import_date'];
                    $customs->item_number = mb_substr($form['cus_item_number'], 0, 2, 'utf-8');
                    $customs->form_name = mb_substr($form['cus_form_name'], 0, 30, 'utf-8');
                    Event::trigger('nrii.equipment.customs.add_columns.save',$form,$customs);
                    $customs->save();
                }

                $token = 'nrii';
                $types = Config::get('nrii')['type'] ? : [];
                if (in_array(LAB_ID, $types)) {
                    $token .= "_".LAB_ID;
                }

                $serviceUrl = 'http://17kong.com/?oauth-sso='.$token.'&site=' . LAB_ID . '&id=' . $form['eq_id'];

                $equipment->eq_id = $form['eq_id'];
                $equipment->eq_name = mb_substr($form['eq_name'], 0, 100, 'utf-8');
                $equipment->ename = mb_substr($form['ename'], 0, 100, 'utf-8');
                $equipment->inner_id = $form['innerId'];
                $equipment->affiliate = $form['affiliate'];
                if (in_array((int)$form['affiliate'], Nrii_Equipment_Model::$affiliate_resource_type)) {
                    $equipment->resource_name = $form['affiliate_name'] ?: '无';
                    $equipment->affiliate_name = '无';
                }
                else {
                    $equipment->affiliate_name = $form['affiliate_name'] ?: '无';
                    $equipment->resource_name = '无';
                }
                $equipment->class = $form['class_sm'];
                $equipment->address = $form['area'];
                $equipment->street = mb_substr($form['street'], 0, 100, 'utf-8');
                $equipment->worth = (double)round($form['worth'],2);

                $equipment->eq_source = $form['eq_source'];
                $equipment->type_status = $form['type_status'];
                // $equipment->status = $form['status'];
                // $equipment->share_status = $form['share_status'];
                $equipment->realm = $form['realm'];
                $equipment->nation = $form['nation'];
                $equipment->model_no = mb_substr($form['model_no'], 0, 100, 'utf-8');
                $equipment->manufacturer = mb_substr($form['manufacturer'], 0, 50, 'utf-8');
                $equipment->begin_date = $form['beginDate'];

                $equipment->technical = mb_substr($form['technical'], 0, 500, 'utf-8');
                $equipment->function = mb_substr($form['function'], 0, 300, 'utf-8');
                $equipment->requirement = mb_substr($form['requirement'], 0, 500, 'utf-8');
                $equipment->fee = mb_substr($form['fee'], 0, 500, 'utf-8');
                $equipment->service_content = mb_substr($form['serviceContent'], 0, 200, 'utf-8');
                $equipment->service_url = $serviceUrl;
                $equipment->customs = $customs;

                $equipment->run_machine = $form['run_machine'];
                $equipment->service_machine = $form['service_machine'];
                $equipment->funds = $form['funds'];
                $equipment->inside_depart = $form['inside_depart'];


                $equipment->contact = mb_substr($form['contact'], 0, 20, 'utf-8');
                $equipment->phone = mb_substr($form['phone'], 0, 20, 'utf-8');
                $equipment->email = mb_substr($form['email'], 0, 50, 'utf-8');
                $equipment->contact_address = mb_substr($form['contact_address'], 0, 100, 'utf-8');
                $equipment->zip_code = $form['zip_code']; 
                
                $equipment->yiqikong_id = '';
                $eqOrigin = O("equipment",$form['eq_id']);
                $equipment->yiqikong_id = $eqOrigin->yiqikong_id;
                Event::trigger('nrii.equipment.add_columns.save',$form,$equipment);
                $equipment->save();
                
                if ($equipment->id) {
                    Log::add(strtr('[nrii_equipment] %user_name[%user_id]添加%equipment_name[%equipment_id]科学仪器', ['%user_name'=> $me->name, '%user_id'=> $me->id, '%equipment_name'=> $equipment->cname, '%equipment_id'=> $equipment->id]), 'journal');

                    Lab::message(Lab::MESSAGE_NORMAL, I18N::T('nrii', '设置科学仪器成功!'));

                    URI::redirect(URI::url('!nrii/nrii.equipment'));
                } else {
                    Lab::message(Lab::MESSAGE_ERROR, I18N::T('nrii', '添加失败! 请与系统管理员联系。'));
                }

            }
        }
        
        $breadcrumb = [
            [
                'url' => URI::url('!nrii/nrii.equipment'),
                'title' => I18N::T('nrii', '单台套科学仪器设备')
            ],
            [
                'url' => URI::url('!nrii/equipment/add'),
                'title' => I18N::T('nrii', '添加')
            ]
        ];

        $this->layout->body->primary_tabs
            ->add_tab('equipment', ['*' => $breadcrumb])
            ->select('equipment')
            ->set('content', V('nrii:equipment/add', [
                    'form' => $form,
                ]));
    }

    function edit($id = 0) {
        $me = L('ME');
        $equipment = O('nrii_equipment',$id);
        if (!$equipment->id){
            URI::redirect('error/404');
        }
        if (!$me->is_allowed_to('编辑', $equipment)) URI::redirect('error/401');

        $form = Form::filter(Input::form());

        if ($form['submit']) {
            $form 
                ->validate('eq_id', 'not_empty', I18N::T('nrii', '请关联仪器!'))
                ->validate('eq_name', 'not_empty', I18N::T('nrii', '请输入仪器设备名称!'))
                ->validate('ename', 'not_empty', I18N::T('nrii', '请输入英文名称!'))
                ->validate('innerId', 'not_empty', I18N::T('nrii', '请输入所属单位科学装置编号!'))
                ->validate('worth', 'not_empty', I18N::T('nrii', '请输入正确原值!'))
                ->validate('worth', 'is_numeric', I18N::T('nrii', '请输入正确原值!'))
                ->validate('street', 'not_empty', I18N::T('nrii', '请输入安放地址!'))
                ->validate('nation', 'not_empty', I18N::T('nrii', '请输入产地国别!'))
                ->validate('model_no', 'not_empty', I18N::T('nrii', '请输入规格型号!'))
                ->validate('manufacturer', 'not_empty', I18N::T('nrii', '请输入生产制造商!'))
                ->validate('beginDate', 'not_empty', I18N::T('nrii', '请选择建账日期!'))
                ->validate('technical', 'not_empty', I18N::T('nrii', '请输入主要技术指标!'))
                ->validate('function', 'not_empty', I18N::T('nrii', '请输入主要功能!'))
                ->validate('requirement', 'not_empty', I18N::T('nrii', '请输入对外开放共享规定!'))
                ->validate('fee', 'not_empty', I18N::T('nrii', '请输入参考收费标准!'))
                ->validate('serviceContent', 'not_empty', I18N::T('nrii', '请输入服务内容!'))
                ->validate('contact', 'not_empty', I18N::T('nrii', '请输入联系人!'))
                ->validate('phone', 'not_empty', I18N::T('nrii', '联系人电话填写有误!'))
                ->validate('email', 'not_empty', I18N::T('nrii', '联系人电子邮箱填写有误!'))
                ->validate('email', 'is_email', I18N::T('nrii', '联系人电子邮箱填写有误!'))
                ->validate('contact_address', 'not_empty', I18N::T('nrii', '请输入通信地址!'))
                ->validate('zip_code', 'not_empty', I18N::T('nrii', '邮政编码输入有误!'))
                ->validate('zip_code', 'length(6)', I18N::T('nrii', '邮政编码输入有误!'))
                ->validate('zip_code', 'is_numeric', I18N::T('nrii', '邮政编码输入有误!'))
                ->validate('run_machine', 'is_numeric', I18N::T('nrii', '年总运行机时输入有误!'))
                ->validate('service_machine', 'is_numeric', I18N::T('nrii', '年服务机时输入有误!'))
                ->validate('inside_depart', 'not_empty', I18N::T('nrii', '所属单位内部门输入有误!'))
                ;

            if ($form['customs'] == 1){
                $form = $form
                    ->validate('cus_inner_id', 'not_empty', I18N::T('nrii', '请输入单位内部编号!'))
                    ->validate('cus_ins_code', 'not_empty', I18N::T('nrii', '请输入所属单位标识!'))
                    ->validate('cus_declaration_number', 'not_empty', I18N::T('nrii', '请输入进口报关单编号!'))
                    ->validate('cus_item_number', 'not_empty', I18N::T('nrii', '请输入进口报关单项号!'))
                    ->validate('cus_import_date', 'not_empty', I18N::T('nrii', '请输入海关放行日期!'))
                    ->validate('cus_form_name', 'not_empty', I18N::T('nrii', '请输入仪器设备进口报关单名称!'));
                Event::trigger('nrii.equipment.customs.add_columns.validate',$form);
            }

            if($form['affiliate'] != 0){
                $form = $form->validate('affiliate_name', 'not_empty', I18N::T('nrii', '请输入所附属仪器名称!'));
            }else{
                $form['affiliate_name'] = '-';
            }
            
            if($form['class_lg'] == -1 || $form['class_md'] == -1 || $form['class_sm'] == -1){
                $form->set_error('class_lg', I18N::T('nrii', '请选择设备分类!'));
                $form->set_error('class_md', I18N::T('nrii', ''));
                $form->set_error('class_sm', I18N::T('nrii', ''));
            }
            if($form['province'] == -1 || $form['city'] == -1 || $form['area'] == -1){
                $form->set_error('province', I18N::T('nrii', '请选择安放地址!'));
                $form->set_error('city', I18N::T('nrii', ''));
                $form->set_error('area', I18N::T('nrii', ''));
            }
            if($form['worth'] < 0 ){
                $form->set_error('worth', I18N::T('nrii', '仪器价格不能设置为负数'));
            }
            if($form['eq_source'] == -1){
                $form->set_error('eq_source', I18N::T('nrii', '请选择仪器设备来源!'));
            }
            if($form['type_status'] == -1){
                $form->set_error('type_status', I18N::T('nrii', '请选择仪器设备类别!'));
            }
            // if($form['status'] == -1){
            //     $form->set_error('status', I18N::T('nrii', '请选择运行状态!'));
            // }
            // if($form['share_status'] == -1){
            //     $form->set_error('share_status', I18N::T('nrii', '请选择共享模式!'));
            // }
            if ($form['realm'] == '{}' || empty($form['realm'])) {
                $form->set_error('realm', I18N::T('nrii', '请输入主要科学领域!'));
            }
            elseif(count(json_decode($form['realm'], true)) > 4){
                $form->set_error('realm', I18N::T('nrii', '主要科学领域填写不能超过4个!'));
            }
            if ($form['funds'] == '{}' || !$form['funds']) {
                $form->set_error('funds', I18N::T('nrii', '请输入主要购置经费来源!'));
            }

            $equipmentOther = O('nrii_equipment', ['eq_id' => $form['eq_id']]); 
            if ($equipmentOther->id && $equipmentOther->id != $id){
                $form->set_error('eq_id', I18N::T('nrii', '关联仪器已经被其他数据关联!'));
            }
            $equipmentOther = O('nrii_equipment', ['inner_id' => $form['innerId']]); 
            if ($equipmentOther->id && $equipmentOther->id != $id){
                $form->set_error('innerId', I18N::T('nrii', "已存在所属单位科学装置编号为{$form['innerId']}的仪器!"));
            }
            Event::trigger('nrii.equipment.add_columns.validate',$form);
            if($form->no_error){
                $customs = $equipment->customs;
                if (!$customs->id){
                    $customs = O('nrii_customs');
                }
                if ($form['customs'] == 1){
                    $customs->inner_id = $form['cus_inner_id'];
                    $customs->ins_code = $form['cus_ins_code'];
                    $customs->declaration_number = $form['cus_declaration_number'];
                    $customs->import_date = $form['cus_import_date'];
                    $customs->item_number = mb_substr($form['cus_item_number'], 0, 2, 'utf-8');
                    $customs->form_name = mb_substr($form['cus_form_name'], 0, 30, 'utf-8');
                    Event::trigger('nrii.equipment.customs.add_columns.save',$form,$customs);
                    $customs->save();
                }else{
                    if ($customs->id){
                        $customs->delete();
                    }
                    $customs = O('nrii_customs');
                }

                $token = 'nrii';
                $types = Config::get('nrii')['type'] ? : [];
                if (in_array(LAB_ID, $types)) {
                    $token .= "_".LAB_ID;
                }

                $serviceUrl = 'http://17kong.com/?oauth-sso='.$token.'&site=' . LAB_ID . '&id=' . $form['eq_id'];

                $equipment->eq_id = $form['eq_id'];
                $equipment->eq_name = mb_substr($form['eq_name'], 0, 100, 'utf-8');
                $equipment->ename = mb_substr($form['ename'], 0, 100, 'utf-8');
                $equipment->inner_id = $form['innerId'];
                $equipment->affiliate = $form['affiliate'];
                if (in_array((int)$form['affiliate'], Nrii_Equipment_Model::$affiliate_resource_type)) {
                    $equipment->resource_name = $form['affiliate_name'] ?: '无';
                    $equipment->affiliate_name = '无';
                }
                else {
                    $equipment->affiliate_name = $form['affiliate_name'] ?: '无';
                    $equipment->resource_name = '无';
                }
                $equipment->class = $form['class_sm'];
                $equipment->address = $form['area'];
                $equipment->street = mb_substr($form['street'], 0, 100, 'utf-8');
                $equipment->worth = (double)round($form['worth'],2);

                $equipment->eq_source = $form['eq_source'];
                $equipment->type_status = $form['type_status'];
                // $equipment->status = $form['status'];
                // $equipment->share_status = $form['share_status'];
                $equipment->realm = $form['realm'];
                $equipment->nation = $form['nation'];
                $equipment->model_no = mb_substr($form['model_no'], 0, 100, 'utf-8');
                $equipment->manufacturer = mb_substr($form['manufacturer'], 0, 50, 'utf-8');
                $equipment->begin_date = $form['beginDate'];

                $equipment->technical = mb_substr($form['technical'], 0, 500, 'utf-8');
                $equipment->function = mb_substr($form['function'], 0, 300, 'utf-8');
                $equipment->requirement = mb_substr($form['requirement'], 0, 500, 'utf-8');
                $equipment->fee = mb_substr($form['fee'], 0, 500, 'utf-8');
                $equipment->service_content = mb_substr($form['serviceContent'], 0, 200, 'utf-8');
                $equipment->service_url = $serviceUrl;
                $equipment->customs = $customs;

                $equipment->run_machine = $form['run_machine'];
                $equipment->service_machine = $form['service_machine'];
                $equipment->funds = $form['funds'];
                $equipment->inside_depart = $form['inside_depart'];


                $equipment->contact = mb_substr($form['contact'], 0, 20, 'utf-8');
                $equipment->phone = mb_substr($form['phone'], 0, 20, 'utf-8');
                $equipment->email = mb_substr($form['email'], 0, 50, 'utf-8');
                $equipment->contact_address = mb_substr($form['contact_address'], 0, 100, 'utf-8');
                $equipment->zip_code = $form['zip_code']; 
                
                $equipment->yiqikong_id = '';
                $eqOrigin = O("equipment",$form['eq_id']);
                $equipment->yiqikong_id = $eqOrigin->yiqikong_id;
                Event::trigger('nrii.equipment.add_columns.save',$form,$equipment);
                $equipment->save();

                if ($equipment->id) {
                    Log::add(strtr('[nrii_equipment] %user_name[%user_id]添加%equipment_name[%equipment_id]科学仪器', ['%user_name'=> $me->name, '%user_id'=> $me->id, '%equipment_name'=> $equipment->cname, '%equipment_id'=> $equipment->id]), 'journal');

                    Lab::message(Lab::MESSAGE_NORMAL, I18N::T('nrii', '设置科学仪器成功!'));

                    URI::redirect(URI::url('!nrii/nrii.equipment'));
                } else {
                    Lab::message(Lab::MESSAGE_ERROR, I18N::T('nrii', '添加失败! 请与系统管理员联系。'));
                }

            }
        }

        $eqContact = O('equipment',$equipment->eq_id);
        $breadcrumb = [
            [
                'url' => URI::url('!nrii/nrii.equipment'),
                'title' => I18N::T('nrii', '单台套科学仪器设备')
            ],
            [
                'url' => URI::url('!nrii/equipment/edit.' . $id),
                'title' => I18N::T('nrii', '编辑')
            ]
        ];

        $this->layout->body->primary_tabs
            ->add_tab('equipment', ['*' => $breadcrumb])
            ->select('equipment')
            ->set('content', V('nrii:equipment/edit', [
                    'form' => $form,
                    'equipment' => $equipment,
                    'eqContact' => $eqContact
                ]));
    }

    function delete($id = 0) {
        $equipment = O('nrii_equipment', $id);

        if (!$equipment->id) {
            URI::redirect('error/404');
        }

        $me = L('ME');
        // if (!$me->is_allowed_to('删除', $equipment)) {
        //  URI::redirect('error/401');
        // }

        Log::add(strtr('[nrii_equipment] %user_name[%user_id]删除%equipment_name[%equipment_id]科学仪器', ['%user_name'=> $me->name, '%user_id'=> $me->id, '%equipment_name'=> $equipment->eq_name, '%equipment_id'=> $equipment->id]), 'journal');
        $equipment->delete_icon();
        if ($equipment->customs->id){
            $equipment->customs->delete();
        }
        if ($equipment->delete()) {
            Lab::message(Lab::MESSAGE_NORMAL, I18N::T('nrii', '科学仪器删除成功!'));
        }
        URI::redirect(URI::url('!nrii/nrii.equipment'));
        
    }

    function import() {
        
        $file = Input::file('file');
        if ($file['tmp_name']) {
            if(!Event::trigger('nrii.equipment.import',$file)){
                try{
                    $import = new Nrii_Import();
                    $result = $import->equipment($file['tmp_name']);
                    $me = L('ME');
                    Log::add(strtr('[nrii_equipment] %user_name[%user_id]批量导入单台单套科学仪器', ['%user_name'=> $me->name, '%user_id'=> $me->id]), 'journal');

                    $_SESSION['equipment_import'] = $result;

                }
                catch(Error_Exception $e){
                    // Lab::message(Lab::MESSAGE_ERROR, I18N::T('nrii', $e->getMessage()));
                    Lab::message(Lab::MESSAGE_ERROR, I18N::T('nrii', '单台单套科学仪器导入失败!'));
                }
            }
        }
        else {
            $form->set_error('file', I18N::T('nrii', '请选择您要上传的单台单套科学仪器csv文件!'));
        }
        exit;
    }

    function sync($id = 0) {
        putenv('Q_ROOT_PATH=' . ROOT_PATH);
        $cmd = 'SITE_ID=' . SITE_ID . ' LAB_ID=' . LAB_ID . ' php ' . ROOT_PATH . 'cli/cli.php nrii sync_equipment '.$id;
        //增加传递的参数
        $cmd .= " ".L('ME')->id;
        $cmd .= " >/dev/null 2>&1 &";

        $process = proc_open($cmd, [], $pipes);
        $var = proc_get_status($process);
        proc_close($process);
        // $pid = intval($var['pid']) + 1;
        
        Lab::message(Lab::MESSAGE_NORMAL, I18N::T('nrii', '上传单台套科学仪器至国家科技部成功!'));

        URI::redirect(URI::url('!nrii/nrii.equipment'));
    }

    function pass($id = 0) {
        $equipment = O('nrii_equipment', $id);

        if (!$equipment->id) {
            URI::redirect('error/404');
        }

        $me = L('ME');
        if (!$me->is_allowed_to('审核', $equipment)) {
            URI::redirect('error/401');
        }

        Log::add(strtr('[nrii_equipment] %user_name[%user_id]审核通过%equipment_name[%equipment_id]科学仪器', ['%user_name'=> $me->name, '%user_id'=> $me->id, '%equipment_name'=> $equipment->eq_name, '%equipment_id'=> $equipment->id]), 'journal');
        $equipment->shen_status = Nrii_Equipment_Model::SHEN_STATUS_FINISH;
        if ($equipment->save()) {
            Lab::message(Lab::MESSAGE_NORMAL, I18N::T('nrii', '操作成功!'));
        }
        URI::redirect(URI::url('!nrii/nrii.equipment'));

    }
}

class Equipment_AJAX_Controller extends AJAX_Controller {
    function index_import_click() {
        JS::dialog((string)V('nrii:upload',[
            'mode' => 'equipment'
        ]));
    }
    function index_equipment_change($id = 0){
        $id = intval($_POST['id']);
        $equipment = O('equipment', $id);
        $contacts = Q("{$equipment} user.contact")->to_assoc('id', 'name');
        if (!$equipment->id) return;

        $ret = [
            'eq_name' => $equipment->name,
            // 'innerId' => $equipment->group_id ? $equipment->group_id : '',
            'org' => $equipment->group_id ? O('tag_group',$equipment->group_id)->name : '',
            'worth' => (double)round(($equipment->price)/10000,2),
            'street' => $equipment->location.$equipment->location2,
            'nation' => $equipment->manu_at,
            'manufacturer' => $equipment->manufacturer,
            'model_no' => $equipment->specification.$equipment->model_no,
            'technical' => $equipment->tech_specs,
            'function' => $equipment->features,
            'contact' => join('/', $contacts),
            'phone' => $equipment->phone,
            'email' => $equipment->email,
        ];
        $equipment->atime != 0 && $ret['beginDate'] = $equipment->atime;
        
        Output::$AJAX['info'] = $ret;
    }

    function index_equipment_export_click() {

        $file_name_time = microtime(TRUE);
        $file_name_arr = explode('.', $file_name_time);
        $file_name = $file_name_arr[0].$file_name_arr[1];

        $selector = $_SESSION['nrii_equipment'];

		$pid = $this->_export_csv($selector, $file_name);
		JS::dialog(V('export_wait', [
			'file_name' => $file_name,
			'pid' => $pid
		]), [
			'title' => I18N::T('calendars', '导出等待')
		]);
		
    }

	private function _export_csv($selector, $file_name) {
		$me = L('ME');
		$form = [
			'form_token' => '',
			'selector' => ''
		];
		$valid_columns = Config::get('columns.export_columns.equipment');

		if (isset($_SESSION[$me->id.'-export'])) {
			foreach ($_SESSION[$me->id.'-export'] as $old_pid => $old_form) {
				$new_valid_form = $form['form'];
				unset($new_valid_form['form_token']);
				unset($new_valid_form['selector']);
				if ($old_form == $new_valid_form) {
					unset($_SESSION[$me->id.'-export'][$old_pid]);
					proc_close(proc_open('kill -9 '.$old_pid, [], $pipes));
				}
			}
		}
		$samplesp= Q($selector);
		putenv('Q_ROOT_PATH=' . ROOT_PATH);
		$cmd = 'SITE_ID=' . SITE_ID . ' LAB_ID=' . LAB_ID . ' php ' . ROOT_PATH . 'cli/cli.php export_nrii_equipment export ';
		$cmd .= "'".$selector."' '".$file_name."' '".json_encode($valid_columns, JSON_UNESCAPED_UNICODE)."' >/dev/null 2>&1 &";
		// exec($cmd, $output);
		$process = proc_open($cmd, [], $pipes);
		$var = proc_get_status($process);
		proc_close($process);
		$pid = intval($var['pid']) + 1;
		$valid_form = $form['form'];
		unset($valid_form['form_token']);
		unset($valid_form['selector']);
		$_SESSION[$me->id.'-export'][$pid] = $valid_form;
		return $pid;
	}
}
