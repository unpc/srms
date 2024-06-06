<?php

class Publications_Index_Controller extends Base_Controller
{

    public function index()
    {
        $form = Lab::form(function (&$old_form, &$form) {});

        $me = L('ME');

        if (!$me->is_allowed_to('列表成果', 'lab')) {
            URI::redirect('error/401');
        }

        $multi_lab = $GLOBALS['preload']['people.multi_lab'];

        $selector      = "publication";
        $pre_selectors = [];

        if ($form['author']) {
            $author                     = Q::quote($form['author']);
            $pre_selectors['ac_author'] = "ac_author[name*={$author}]<achievement ";
        }
        
        if (!$multi_lab) {
            if ($me->access('查看所有实验室成果')) {}
			else {
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
                $pre_selectors['labs'] = implode('|', $pre);
            }
        }
        
        if ($form['lab_id']) {
            $lab                  = O('lab', $form['lab_id']);
            $pre_selectors['lab'] = "{$lab}";
        }

        if ($form['title']) {
            $title    = Q::quote($form['title']);
            $selector = $selector . "[title*=$title]";
        }
        if ($form['journal']) {
            $journal  = Q::quote($form['journal']);
            $selector = $selector . "[journal*=$journal]";
        }
        if ($form['dtstart']) {
            $dtstart  = Q::quote($form['dtstart']);
            $selector = $selector . "[date>=$dtstart]";
        }
        if ($form['dtend']) {
            $dtend    = Q::quote($form['dtend']);
            $selector = $selector . "[date<=$dtend]";
        }

        $tag      = O('tag_achievements_publication', $form['tag_id']);
        $tag_root = Tag_Model::root('achievements_publication');

        if ($tag->id && $tag->root->id == $tag_root->id) {
            $pre_selectors['tag'] = $tag;
        } else {
            $tag = null;
        }

        if ($form['sort'] == 'author' && !$pre_selectors['ac_author']) {
            $pre_selectors['ac_ahthor'] = 'ac_author<achievement';
        }
        if (count($pre_selectors) > 0) {
            $selector = '(' . implode(',', $pre_selectors) . ') ' . $selector;
        }

        $sort_by   = $form['sort'];
        $sort_asc  = $form['sort_asc'];
        $sort_flag = $sort_asc ? 'A' : 'D';
        switch ($sort_by) {
            case 'title':
                $selector .= ":sort(name_abbr {$sort_flag} ,id D)";
                break;
            case 'journal':
                $selector .= ":sort(journal_abbr {$sort_flag} ,id D)";
                break;
            case 'date':
                $selector .= ":sort(date {$sort_flag} ,id D)";
                break;
            case 'author':
                $selector .= ":sort(ac_author.name_abbr {$sort_flag} ,id D)";
                break;
            default:
                $selector .= ':sort(date D)';
                break;
        }

        $form_token            = Session::temp_token('publication_list_', 300);
        $_SESSION[$form_token] = ['selector' => $selector];

        $publications = Q($selector);

        $pagination = Lab::pagination($publications, (int) $form['st'], 15);

        $content = V('publications/list', [
            'publications' => $publications,
            'pagination'   => $pagination,
            'form'         => $form,
            'lab'          => $lab,
            'tag'          => $tag,
            'tag_root'     => $tag_root,
            'sort_by'      => $sort_by,
            'sort_asc'     => $sort_asc,
            'form_token'   => $form_token,
        ]);

        $this->layout->body->primary_tabs
            ->select('publications')
            ->set('content', $content);

    }

}
