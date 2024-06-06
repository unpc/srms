<?php 

/*
 * @file sj3_report.php
 * @author Jia Huang <jia.huang@geneegroup.com>
 * @date 2012-07-02
 *
 * @brief 单一仪器进行教学使用机时、科研使用机时、社会服务使用机时、教学项目数、科研项目数、社会服务项目数、学生培训人数、教师培训人数、其他培训人数、获奖情况-国家级、获奖情况-省部级、发明专利-学生、发明专利-老师、论文情况-三大检索、论文情况-核心刊物 等相关数据统计的测试脚本
 * @usage SITE_ID=cf LAB_ID=ut Q_ROOT_PATH=~/lims2/ php test.php ../tests/50-report/sj3_report
 */

//获取到一个指定ID为76的equipment对象
$equipment = O("equipment",76);	

//设定开始时间段	2010							
//设定结束时间段	2011
$start = mktime(0,0,0,1,1,2010);												
$end = mktime(0,0,0,1,1,2011);	

//选定时间段内该equipment所有使用记录
$records = Q("eq_record[equipment={$equipment}][dtstart={$start}~{$end}][dtend={$start}~{$end}]");



//-------------------------使用机时测试-------------------------------------------
$time_teaching = 0;
$time_research = 0;
$time_public_service = 0;
$time_total = 0;
$time_admin = 0;
$time_open = 0;

//项目初值
$projects_teaching = 0 ;
$projects_research = 0 ;
$projects_public_service = 0 ;
foreach($records as $record) {
	if ($record->project->type == '0') {
		$projects_teaching++;   												//5.教学实验项目数  
		$time_teaching += ($record->dtend - $record->dtstart); 					//1.教学使用机时              
	}
	if ($record->project->type == '1') {
		
		$projects_research++; 													//6.科研项目数和科研使用机时
		$time_research += ($record->dtend - $record->dtstart); 					//2.科研使用机时
	}
	if ($record->project->type == '2') {
		$projects_public_service++; 											//7.社会服务项目数
		$time_public_service += ($record->dtend - $record->dtstart); 			//3.社会服务使用机时
	}
	if (Equipments::user_is_eq_incharge($record->user, $record->equipment) || $record->agent->id) {
		$time_admin += ($record->dtend - $record->dtstart);
	}
	$time_total += ($record->dtend - $record->dtstart);
}
$time_open = $time_total - $time_admin;											//4.开放机时

//------------------------------------------------------------------------------



//---------------------------培训人员数-------------------------------------------
$students_ue_training = 0; 
$students_ge_training = 0; 
$students_training = 0;		
$teachers_ue_training = 0;
$others_training = 0;

$ue_trainings = Q("{$equipment} ue_training[ctime={$start}~{$end}][mtime={$start}~{$end}]");
$students_ge_training = (int)Q("{$equipment} ge_training[date={$start}~{$end}]")->sum(napproved);
	
foreach($ue_trainings as $ue_training) {
	if ($ue_training->user->member_type >= 0 && $ue_training->user->member_type < 10) {
		$students_ue_training++;    
	}
	if ($ue_training->user->member_type >= 10  && $ue_training->user->member_type < 20) {
		$teachers_ue_training++;    											//10.教师培训人数
	}
	if ($ue_training->user->member_type >= 20) {
		$others_training++;														//9.其他培训人数
	}
}

$students_training = $students_ue_training + $students_ge_training;   			//8.学生培训人数 						
//------------------------------------------------------------------------------



//--------------------------获奖情况,发明专利,论文情况-------------------------------
$awards_national= 0;
$awards_provincial = 0;
$patents_student = 0;
$patents_teacher = 0;
$pubs_top3index = 0;
$pubs_core = 0;

$awards = Q("{$equipment} award[date={$start}~{$end}]");
$patents = Q("{$equipment} patent[date={$start}~{$end}]");
$publications = Q("{$equipment} publication[date={$start}~{$end}]");
if(count($awards) > 0 ) {
	foreach($awards as $award) {
		$root = Tag_Model::root('achievements_award');
		$tags = Q("$award tag[root=$root]")->to_assoc();
		
		foreach($tags as $tag) {
			if($tag == '国家级') 	{
				$awards_national ++;    										//11.获奖情况-国家级
			}
			elseif($tag == '省部级') {
				$awards_provincial ++;  										//12.获奖情况-省部级
			}
		}
	}
}
if (count($patents) > 0 ) {
	foreach($patents as $patent) {
		$root = Tag_Model::root('achievements_patent');
		$tags = Q("$patent tag[root=$root]")->to_assoc();
			
		foreach($tags as $tag) {
			if($tag == '教师') {
				$patents_student++;   											//13.发明专利-教师
			}
			elseif($tag == '学生') {
				$patents_teacher++;   											//14.发明专利-学生
			}
		}
	}
}	
if (count($publications) > 0 ) {
	foreach($publications as $publication) {
		$root = Tag_Model::root('achievements_publication');
		$tags = Q("$publication tag[root=$root]")->to_assoc();
		foreach($tags as $tag) {
			if($tag == '三大检索') {
				$pubs_top3index++;												//15.论文情况-三大检索
			}
			if($tag == '核心刊物') {
				$pubs_core++;													//16.论文情况-核心刊物
			}
		}
	}
}	
//------------------------------------------------------------------------------

echo '教学使用机时(time_teaching)=>'.ceil($time_teaching/3600)."\n";
echo '科研使用机时(time_research)=>'.ceil($time_research/3600)."\n";
echo '社会服务使用机时(time_public_service)=>'.ceil($time_public_service/3600)."\n";
echo '开放机时(time_open)=>'.ceil($time_open/3600)."\n";
echo '教学项目数(projects_teaching)=>'.$projects_teaching."\n";
echo '科研项目数(projects_research)=>'.$projects_research."\n";
echo '社会服务项目数(projects_public_service)=>'.$projects_public_service."\n";
echo '学生培训人数(students_training)=>'.$students_training."\n";
echo '教师培训人数(teachers_ue_training)=>	'.$teachers_ue_training."\n";
echo '其他培训人数(others_training)=>'.$others_training."\n";
echo '获奖情况-国家级(awards_national)=>'.$awards_national."\n";
echo '获奖情况-省部级(awards_provincial)=>	'.$awards_provincial."\n";
echo '发明专利-学生(patents_student)=>	'.$patents_student."\n";
echo '发明专利-老师(patents_teacher)=>	'.$patents_teacher."\n";
echo '论文情况-三大检索(pubs_top3index)=>'.$pubs_top3index."\n";
echo '论文情况-核心刊物(pubs_core)=>'.$pubs_core."\n";



