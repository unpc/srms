<?php

class Publication_Model extends Presentable_Model {

	protected $object_page = [
		'view'=>'!achievements/publications/publication/index.%id',
		'edit' => '!achievements/publications/publication/edit.%id[.%arguments]',
		'delete' => '!achievements/publications/publication/delete.%id',
		'add' => '!achievements/publications/publication/add[.%arguments]'
	];

	//权限设置（未定义）
	function & links($mode = 'index') {
		$links = new ArrayIterator;
		$me = L('ME');
		/*
		NO.TASK#274(guoping.zhang@2010.11.27)
		成果管理模块应用权限判断新规则
		*/
		switch ($mode) {
		case 'read':
			if ($me->is_allowed_to('查看', $this)) {
				$links['view'] = [
					'url'=> $this->url(NULL,NULL,NULL),
					'text'  => I18N::T('achievements','查看'),
					'extra'=>'class="blue"',
				];
			}
			break;
		case 'view':
			if ($me->is_allowed_to('修改', $this)) {
				$links['edit'] = [
					'url' => $this->url(NULL,NULL,NULL,'edit'),
					'text' =>'修改',
					'tip' =>I18N::T('achievements', '编辑'),
					'extra'=>'class="button icon-edit"',
				];
			}
			if ($me->is_allowed_to('删除', $this)) {
				$links['delete'] = [
					'url'=> $this->url(NULL,NULL,NULL,'delete'),
					'text'=>'删除',
					'tip'=>I18N::T('achievements','删除'),
					'extra'=>'class="button icon-trash" confirm="'.I18N::T('achievements','你确定要删除吗? 删除后不可恢复!').'" style="border: 1px solid #F5222D;color: #F5222D;"',
				];
			}
			break;
		case 'index':
		default:
			if ($me->is_allowed_to('修改', $this)) {
				$links['edit'] = [
					'url' => $this->url(NULL,NULL,NULL,'edit'),
					'text' =>I18N::T('achievements', '修改'),
					'tip' =>I18N::T('achievements', '编辑'),
					'extra'=>'class="blue"',
				];
			}
            if ($me->is_allowed_to('查看', $this)) {
                $links['view'] = [
                    'url'=> $this->url(NULL,NULL,NULL),
                    'tip'=>I18N::T('achievements','查看'),
                    'text'  => I18N::T('achievements','查看'),
                    'extra'=>'class="blue"',
                ];
            }
			if ($me->is_allowed_to('删除', $this)) {
				$links['delete'] = [
					'url'=> $this->url(NULL,NULL,NULL,'delete'),
					'tip'=>I18N::T('achievements','删除'),
					'text'=>I18N::T('achievements','删除'),
					'extra'=>'class="red" confirm="'.I18N::T('achievements','你确定要删除吗? 删除后不可恢复!').'"',
				];
			}
		}
		return (array) $links;
	}

	function get_info_by_pmid($id=null) {
		$pubmed_id = $id ?: $this->pmid;
		$url="https://eutils.ncbi.nlm.nih.gov/entrez/eutils/efetch.fcgi?db=pubmed&id=".$pubmed_id."&retmode=xml";

		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		$data = curl_exec($ch);
		curl_close($ch);

		$xml = simplexml_load_string($data);
		$PMID=$xml->PubmedArticle->MedlineCitation->PMID;
		$return = [];
		if ($PMID) {
			$authors = [];
			foreach($xml->PubmedArticle->MedlineCitation->Article->AuthorList->Author as $author){
				$ForeName=$author->ForeName;
				if (!$ForeName) {
					$ForeName = $author->FirstName;
				}
				if (!$ForeName) {
					$ForeName = $author->Initials;
				}
				$LastName=$author->LastName;
				$name=$LastName.', '.$ForeName;
				$authors[]=$name;
			}
			$adate = $xml->PubmedArticle->MedlineCitation->Article->Journal->JournalIssue->PubDate;
			if(!$adate)$adate=$xml->PubmedArticle->MedlineCitation->Article->ArticleDate;
			if(!$adate)$adate=reset($xml->PubmedArticle->xpath("PubmedData/History/PubMedPubDate[@PubStatus='aheadofprint']"));
			if(!$adate)$adate=reset($xml->PubmedArticle->xpath("PubmedData/History/PubMedPubDate[@PubStatus='pubmed']"));
			if(!$adate)$adate=reset($xml->PubmedArticle->xpath("PubmedData/History/PubMedPubDate[@PubStatus='entrez']"));
			if(!$adate)$adate=$xml->PubmedArticle->MedlineCitation->DateCreated;
			if(!$adate)$adate=$xml->PubmedArticle->MedlineCitation->DateCompleted;
			if($adate){
				if (!is_numeric((string)$adate->Month)) {
					$date = strtotime("$adate->Day $adate->Month $adate->Year");
				}
				else {
					$date = mktime(0,0,0,(int)$adate->Month,(int)$adate->Day,(int)$adate->Year);
				}
			}else{
				$date=time();
			}
			$title = (string) $xml->PubmedArticle->MedlineCitation->Article->ArticleTitle;
			$content = (string) $xml->PubmedArticle->MedlineCitation->Article->Abstract->AbstractText;
			$journal = (string) $xml->PubmedArticle->MedlineCitation->Article->Journal->Title;
			$volume =  (int) $xml->PubmedArticle->MedlineCitation->Article->Journal->JournalIssue->Volume;
			$issue = (int) $xml->PubmedArticle->MedlineCitation->Article->Journal->JournalIssue->Issue;
			$page = (string) $xml->PubmedArticle->MedlineCitation->Article->Pagination->MedlinePgn;

            $return = [
                'authors'=>$authors,
                'date'=>$date,
                'title'=>$title,
                'content'=>$content,
                'journal'=>$journal,
                'volume'=>$volume,
                'issue'=>$issue,
                'page'=>$page
            ];
		}
		return $return;
	}

	function save($overwrite=FALSE) {
		if (!$this->lab->id) {
			$this->lab = Lab_Model::default_lab();
		}

		// 由于 timestamp 不记录时区, 所以为防止不同时区年月信息不准:
		// 1. 如果 date 在当月 1 日, 就放到当月 2 日;
		// 2. 如果 date 在当月最后一日, 就放到当月倒数第二日;
		if ( date('j', $this->date) == 1 ) {
			$this->date += 86400;
		}
		else if ( date('j', $this->date + 86400) == 1 ) {
			$this->date -= 86400;
		}

		return parent::save($overwrite);
	}
}
