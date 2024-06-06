<?php

class API_Achievements {

	private function _get_publication($p) {

		$ac_authors = Q("ac_author[achievement=$p]:sort(position ASC)");
		$authors = [];
		foreach ($ac_authors as $a) {
			$authors[] = [
				'name' => $a->name,
				'uid' => $a->user->id,
				'position' => $a->position,
			];
		}

		$info = [
			'title' =>  $p->title,
			'journal' => $p->journal,
			'date' => $p->date,
			'volume' => $p->volume,
			'issue' => $p->issue,
			'page' => $p->page,
			'content' => $p->content,
			'notes' => $p->notes,
			'impact' => $p->impact,
			'authors' => $authors,
		];

		return $info;
	}

	function publications($st=0, $pp=25) {

		$items = [];
		foreach(Q("publication") as $publication) {
			$items[] = $this->_get_publication($publication);
		}

		return ['items'=>$items];
	}

}
