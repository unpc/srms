<?php

class Patents_Index_Controller extends Base_Controller
{

    public function index()
    {
        $form = Lab::form(function (&$old_form, &$form) {});

        $me = L('ME');

        if (!$me->is_allowed_to('列表成果', 'lab')) {
            URI::redirect('error/401');
        }

        $selector      = "patent";
        $pre_selectors = [];

        if (!$GLOBALS['preload']['people.multi_lab']) {
            if ($me->access('查看所有实验室成果')) {} else {
                $pre_selectors[] = "{$me} lab";
            }
        } else {
            if ($me->access('查看所有实验室成果')) {} else {
                $pre = [];
                if ($me->access('查看下属机构仪器的关联成果')) {
                    $pre[] = "{$me->group} equipment";
                }
                if ($me->access('查看所属实验室成果')) {
                    $pre[] = "{$me} lab";
                }
                if ($me->access('查看负责实验室成果')) {
                    $pre[] = "{$me}<pi lab";
                }
                if (!$pre) {
                    URI::redirect('error/401');
                }
                $pre_selectors[] = implode('|', $pre);
            }
        }

        if ($form['lab_id']) {
            $lab             = O('lab', $form['lab_id']);
            $pre_selectors[] = "{$lab}";
        }

        if ($form['people']) {
            $people                     = Q::quote($form['people']);
            $pre_selectors['ac_author'] = "ac_author[name*={$people}]<achievement ";
        }

        if ($form['name']) {
            $name     = Q::quote($form['name']);
            $selector = $selector . "[name*=$name]";
        }
        if (trim($form['ref_no'])) {

            $ref_no   = Q::quote($form['ref_no']);
            $selector = $selector . "[ref_no*=$ref_no]";
        }

        if ($form['dtstart']) {
            $dtstart  = Q::quote($form['dtstart']);
            $selector = $selector . "[date>=$dtstart]";
        }
        if ($form['dtend']) {
            $dtend    = Q::quote($form['dtend']);
            $selector = $selector . "[date<=$dtend]";
        }

        $tag      = O('tag_achievements_patent', $form['tag_id']);
        $tag_root = Tag_Model::root('achievements_patent');

        if ($tag->id && $tag->root->id == $tag_root->id) {
            $pre_selectors['tag'] = $tag;
        } else {
            $tag = null;
        }

        if (!$pre_selectors['tag'] && $form['sort'] == 'tag') {
            $pre_selectors['tag'] = "tag";
        } elseif (!$pre_selectors['ac_author'] && $form['sort'] == 'people') {
            $pre_selectors['ac_author'] = "ac_author<achievement";
        }
        if (count($pre_selectors) > 0) {
            $selector = '(' . implode(',', $pre_selectors) . ') ' . $selector;
        }

        $sort_by   = $form['sort'];
        $sort_asc  = $form['sort_asc'];
        $sort_flag = $sort_asc ? 'A' : 'D';
        switch ($sort_by) {
            case 'name':
                $selector .= ":sort(name_abbr {$sort_flag} ,id D)";
                break;
            case 'ref_no':
                $selector .= ":sort(ref_no {$sort_flag} ,id D)";
                break;
            case 'tag':
                $selector .= ":sort(tag.name_abbr {$sort_flag} ,id D)";
                break;
            case 'date':
                $selector .= ":sort(date {$sort_flag} ,id D)";
                break;
            case 'people':
                $selector .= ":sort(ac_author.name_abbr {$sort_flag} ,id D)";
                break;
            default:
                $selector .= ':sort(date D)';
                break;
        }
        $form_token = Session::temp_token('patent_list_', 300);

        $_SESSION[$form_token] = ['selector' => $selector];

        $patents = Q($selector);

        $pagination = Lab::pagination($patents, (int) $form['st'], 15);

        $content = V('patents/list', [
            'patents'    => $patents,
            'pagination' => $pagination,
            'form'       => $form,
            'lab'        => $lab,
            'tag'        => $tag,
            'tag_root'   => $tag_root,
            'sort_by'    => $sort_by,
            'sort_asc'   => $sort_asc,
            'form_token' => $form_token,
        ]);

        $this->layout->body->primary_tabs
            ->select('patents')
            ->set('content', $content);

    }

}
