<?php

class CLI_Uno_Perm
{
    public static function replace_uno_perm()
    {

        //先设置global下perm_in_uno = false;
        $perms = Config::get('perms');
        $unoPerms = [];
        foreach ($perms as $module => $modulePerms) {
            foreach ($modulePerms as $permname => $v) {

                if (strpos($permname, '-') !== false) continue;
                if (in_array($permname, ['管理所有内容', '管理组织机构'])) continue;

                $type = 'system';

                if ((strpos($permname, '实验室') || strpos($permname, '课题组')) && strpos($permname, '下属') === false && strpos($permname, '所有') === false) {
                    $type = 'lab';
                }
                if (strpos($permname, '所属') !== false || strpos($permname, '下属') !== false) {
                    $type = 'group';
                }

                if (preg_match('/[所有|下属]/', $permname)) {
                    $newPername = str_replace(['所有', '机构的', '机构', '下属', '下属的', '机构的', '机构', '所属', '所属的', '组织机构', '组织机构的'], [''], $permname);
                    if ($newPername == $permname) continue;
                    $unoPerms[$module][$permname] = [
                        'name' => $newPername,
                        'object' => $type
                    ];
                }
            }
        }
        unset($unoPerms['default_roles']);

        $setModules = ['eq_reserv', 'equipments', 'eq_record', 'people', 'labs', 'eq_sample', 'eq_charge'];
        foreach ($unoPerms as $module => $perm) {
            if (!empty($setModules) && !in_array($module, $setModules)) continue;
            $configname = MODULE_PATH . $module . '/config/uno_perm.php';
            file_put_contents($configname, '');
            $ss = "<?php\n";
            foreach ($perm as $kk => $kv) {
                $ss .= '$config[\'' . $kk . '\'] = [' . "\n\t" . '\'name\' => \'' . $kv['name'] . '\',' . "\n\t";
                $ss .= '\'object\' => \'' . $kv['object'] . '\',' . "\n" . '];' . "\n";
            }
            file_put_contents($configname, $ss);
        }
        //还原global下perm_in_uno = true;

    }
}