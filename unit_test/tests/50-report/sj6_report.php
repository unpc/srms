<?php 

/*
 * @file sj6_report.php
 * @author Jia Huang <jia.huang@geneegroup.com>
 * @date 2012-07-02
 *
 * @brief 针对单一实验室，进行 获奖-教师国家级、获奖-教师省部级、教师获奖数、学生获奖数、三大检索收录和教学教材数、三大检索收录和科研教材数、核心刊物和教学教材数、实验教材数、科研项目数-省部级以上、科研项目数-其他、教研项目数-省部级以上、教研项目数-其他、社会服务项目数、专科生人数、本科生人数、研究生人数 等相关数据统计测试脚本
 * @usage SITE_ID=cf LAB_ID=ut Q_ROOT_PATH=~/lims2/ php test.php ../tests/50-report/sj6_report
 */

//指定一个ID为56的lab
$lab = O('lab',54);

//设定开始时间段	2010							
//设定结束时间段	2011
$start = mktime(0,0,0,1,1,2010);												
$end = mktime(0,0,0,1,1,2011);	

//年度时间选择范围之内该仪器的所有使用记录
$awards = Q("user[lab=$lab] ac_author award.achievement[date={$start}~{$end}]");
$publications = Q("user[lab=$lab] ac_author publication.achievement[date={$start}~{$end}]");
$patents = Q("user[lab=$lab] ac_author patent.achievement[date={$start}~{$end}]");


//-------------------------------教师获奖与成果------------------------------------
$awards_teacher_national = 0;
$awards_teacher_provincial = 0;
$patents_teacher = 0;
$awards_student = 0;
foreach ($awards as $award) {
	$achievments_award = Q("ac_author[achievement=$award]");
	foreach ($achievments_award as $val ) {
		if($val->user->member_type >= 10 && $val->user->member_type <20 ) {
			$root = Tag_Model::root('achievements_award');
			$tags = Q("$award tag[root=$root]")->to_assoc();
			foreach ($tags as $tag) {
				if($tag == '国家级') {
					$awards_teacher_national++;									//1.教师国家级
				}
				if($tag == '省部级') {
					$awards_teacher_provincial++;								//2.教师省部级
				}
			}
		}
		if($val->user->member_type >= 0 && $val->user->member_type < 10 ) {
			$awards_student++;													//4.学生获奖数
		}
	}
} 

foreach ($patents as $patent) {
	$achievments_patent = Q("ac_author[achievement=$patent]");
	foreach ($achievments_patent as $val ) {
		if($val->user->member_type >= 10 && $val->user->member_type <20 ) {
			$patents_teacher++;													//3.教师发明专利
		}
	}
}

//------------------------------------------------------------------------------

echo '获奖-教师国家级(awards_teacher_national)=>'.$awards_teacher_national."\n";
echo '获奖-教师省部级(awards_teacher_provincial)=>'.$awards_teacher_provincial."\n";
echo '教师获奖数(patents_teacher)=>'.$patents_teacher."\n";
echo '学生获奖数(patents_student)=>'.$awards_student."\n";

//--------------------------------论文和教材情况-----------------------------------
$pubs_top3index_teaching = 0;
$pubs_top3index_research = 0;
$pubs_core_teaching = 0;
$pubs_core_research = 0;
foreach ($publications as $publication) {
	$root = Tag_Model::root('achievements_publication');
	$tags = Q("$publication tag[root=$root]")->to_assoc();
	foreach ($tags as $tag) {
		if($tag == '三大检索') {
			$top3 = true;
		}
		if($tag == '教学') {
			$teaching = true;
		}
		if($tag == '科研') {
			$research = true;
		}
		if($tag == '核心刊物') {
			$core = true;
		}
		if($top3 && $teaching) {
			$pubs_top3index_teaching++;											//5.三大检索收录和教学
		}
		if($top3 && $research) {
			$pubs_top3index_research++;											//6.三大检索收录和科研
		}
		if($core && $teaching ) {
			$pubs_core_teaching++;												//7.核心刊物和教学
		}
		if($core && $research) {		
			$pubs_core_research++;												//8.核心刊物和科研
		}
	}
} 
$projects_teaching_selfmade_book = 0;
$lab_projects = Q("lab_project[lab=$lab][type='0']");
foreach($lab_projects as $project) {
	if($project->book_type == '1') {
		$projects_teaching_selfmade_book++;										//9.实验教材
	}
}
//------------------------------------------------------------------------------

echo '三大检索收录和教学(pubs_top3index_teaching)=>'.$pubs_top3index_teaching."\n";
echo '三大检索收录和科研(pubs_top3index_research)=>'.$pubs_top3index_research."\n";
echo '核心刊物和教学(pubs_core_teaching)=>'.$pubs_core_teaching."\n";
echo '核心刊物和科研(pubs_core_research)=>'.$pubs_core_research."\n";
echo '实验教材(projects_teaching_selfmade_book)=>'.$projects_teaching_selfmade_book."\n";


//-----------------------------------科研及社会服务--------------------------------
$projects_provincial_research = 0;
$projects_other_research = 0;
$projects_public_service = 0;
$projects_provincial_teaching = 0;
$projects_other_teaching = 0;
$research_projects = Q("lab_project[lab=$lab][type=1]");
foreach ($research_projects as $research_project){
	$award_projects = Q("{$research_project} award");
	foreach($award_projects as $award_project) {
		$root = Tag_Model::root('achievements_award');
		$tags = Q("$award_project tag[root=$root]")->to_assoc();
		foreach ($tags as $tag ) {
			if($tag == '省部级' || $tag == '国家级') {
				$projects_provincial_research++;								//10.科研项目数-省部级以上
			}
			else {
				$projects_other_research++;										//11.科研项目数-其他
			}
		}
	}
}
$projects_public_service = count(Q("lab_project[lab=$lab][type=2]"));			//14.社会服务项目数

//教研项目数
foreach($lab_projects as $project) {
	$award_projects = Q("{$project} award");
	foreach($award_projects as $award_project) {
		$root = Tag_Model::root('achievements_award');
		$tags = Q("$award_project tag[root=$root]")->to_assoc();
		foreach ($tags as $tag ) {
			if($tag == '省部级' || $tag == '国家级') {
				$projects_provincial_teaching++;								//12.教研项目数-省部级以上
			}
			else {		
				$projects_other_teaching++;										//13.教研项目数-其他
			}
		}
	}
}
//------------------------------------------------------------------------------

echo '科研项目数-省部级以上(projects_provincial_research)=>'.$projects_provincial_research."\n";
echo '科研项目数-其他(projects_other_research)=>'.$projects_other_research."\n";
echo '教研项目数-省部级以上(projects_provincial_teaching)=>'.$projects_provincial_teaching."\n";
echo '教研项目数-其他(projects_other_teaching)=>'.$projects_other_teaching."\n";
echo '社会服务项目数(projects_public_service)=>'.$projects_public_service."\n";

//---------------------------------毕业设计和论文人数------------------------------
$junior_colleges_pubs = 0;
$bachelors_pubs = 0;
$graduates_pubs = 0;
$pubs = Q("publication[date={$start}~{$end}]");
foreach ($pubs as $publication) {
	$root = Tag_Model::root('achievements_publication');
	$tags = Q("$publication tag[root=$root]")->to_assoc();
	foreach ($tags as $tag ) {
		if ($tag == '毕业论文') {			
			$ac_authors = Q("ac_author[achievement=$publication]");
			$user_arr = [];
			foreach ($ac_authors as $author) {
				if(!in_array($author->user->id,$user_arr)) {
					$user_arr[] = $author->user->id;
					$student_type = $author->user->member_type;
					if($student_type >= 0 && $student_type <9 ) {
						if($student_type == '0') {
							$bachelors_pubs++;									//16.本科生人数
						}
						elseif($student_type == '1' || $student_type == '2') {
							$graduates_pubs++;									//17.研究生人数
						}
						else {
							$junior_colleges_pubs++;							//15.专科生人数
						}
					}
				}
				
			}
		}
	}	
}	
//------------------------------------------------------------------------------
echo '专科生人数(junior_colleges_pubs)=>'.$junior_colleges_pubs."\n";
echo '本科生人数(bachelors_pubs)=>'.$bachelors_pubs."\n";
echo '研究生人数(graduates_pubs)=>'.$graduates_pubs."\n";
