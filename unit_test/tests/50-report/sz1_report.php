<?php 

/*
 * @file sz1_report.php
 * @author Jia Huang <jia.huang@geneegroup.com>
 * @date 2012-07-02
 *
 * @brief 针对某时段内时间进行实验室个数、仪器设备个数、仪器设备总金额、贵重仪器设备总金额、教学实验数、教学任务本科生时数、教学任务硕士研究生时数、教学任务博士生研究生时数、教许任务专科生时数、所有学生教学任务时数、论文数、学生获奖数、教师获奖与成果数 等相关数据统计的测试脚本
 * @usage SITE_ID=cf LAB_ID=ut Q_ROOT_PATH=~/lims2/ php test.php ../tests/sz1_report
 */

//设置时间为2010-2011年
$start = mktime(0,0,0,1,1,2010);
$end = mktime(0,0,0,1,1,2011);

//1.实验室个数
$labs = Q("lab[ctime={$start}~{$end}][mtime={$start}~{$end}]");
echo '实验室个数(labs)=>'.count($labs)."\n";

//2.仪器设备
$eqs = Q("equipment[purchased_date={$start}~{$end}]");
$equipments = count($eqs);														//6.仪器设备总台件
echo '仪器设备(equipments)=>'.$equipments."\n";

//3.仪器设备总金额(万元)
$moneys_equipments = 0;
foreach($eqs as $eq) {
	$moneys_equipments += $eq->price;
	
}

echo '仪器设备总金额(moneys_equipments)=>'.($moneys_equipments/10000)."\n";

//4.贵重仪器设备总台件
$precious_equipments = 0;
$moneys_precious_equipments = 0;
$precious_eqs = Q("equipment[purchased_date={$start}~{$end}][price>=400000]");
$precious_equipments = count($precious_eqs);
echo '贵重仪器设备总台件(precious_equipments)=>'.$precious_equipments."\n";

//5.贵重仪器设备总金额
foreach ($precious_eqs as $eq) {
	$moneys_precious_equipments += $eq->price;
}
echo '贵重仪器设备总金额(moneys_precious_equipments)=>'.($moneys_precious_equipments/10000)."\n";

//教学任务
$projects  = Q("lab_project[type='0']");
$projects_teaching = count($projects);											//6.教学实验项目数

$time_projects_teaching = 0;
$time_teaching_doctor = 0;
$time_teaching_master = 0;
$time_teaching_bachelor = 0;
$time_teaching_junior_college = 0;
$time_teaching_total = 0;

$records = Q("eq_record[dtstart={$start}~{$end}][dtend={$start}~{$end}]");
foreach ($records as $record) {
	if ($record->project->type == '0') {
		$time_projects_teaching += ($record->dtend - $record->dtstart);			//7.教学实验时数
		if ($record->user->member_type == '0') {
			$time_teaching_bachelor += ($record->dtend - $record->dtstart);		//8.教学任务本科生时数
		}
		elseif ($record->user->member_type == '1') {
			$time_teaching_master += ($record->dtend - $record->dtstart);		//9.教学任务硕士研究生时数
		}
		elseif ($record->user->member_type == '2') {
			$time_teaching_doctor += ($record->dtend - $record->dtstart);		//10.教学任务博士研究生时数
		}
		else {
			$time_teaching_junior_college += ($record->dtend - $record->dtstart);		//11.教学任务专科生时数
		}			
	}
}
$time_projects_teaching = ceil($time_projects_teaching/3600);
$time_teaching_doctor = ceil($time_teaching_doctor/3600);
$time_teaching_master = ceil($time_teaching_master/3600);
$time_teaching_bachelor = ceil($time_teaching_bachelor/3600);
$time_teaching_junior_college = ceil($time_teaching_junior_college/3600);
$time_teaching_total = $time_teaching_bachelor 
					+ $time_teaching_master 
					+ $time_teaching_doctor
					+ $time_teaching_junior_college	;							//12.合计	
echo '教学实验数(time_projects_teaching)=>'.$time_projects_teaching."\n";
echo '教学任务本科生时数(time_teaching_bachelor)=>'.$time_teaching_bachelor."\n";
echo '教学任务硕士研究生时数(time_teaching_master)=>'.$time_teaching_master."\n";
echo '教学任务博士研究生时数(time_teaching_doctor)=>'.$time_teaching_doctor."\n";
echo '教学任务专科生时数(time_teaching_junior_college)=>'.$time_teaching_junior_college."\n";
echo '合计(time_teaching_total)=>'.$time_teaching_total."\n";

//13.科研任务承担课题及服务项目数
$projects_not_teaching  = Q("lab_project[type!=0]");
$projects_research_service = count($projects_not_teaching);	

//成果
$awards_teacher = 0;
$awards_student = 0;
$publications = Q("publication[date={$start}~{$end}]");
$pubs = count($publications);													//14.论文数
$awards = Q("award[date={$start}~{$end}]");
foreach ($awards as $award) {
	$ac_authors = Q("ac_author[achievement=$award]");
	foreach ($ac_authors as $author) {
		$member_type = $author->user->member_type;
		if($member_type >= 10 && $member_type < 20) {
			$awards_teacher++;											//教师获奖数
		}
		if($member_type >= 0 && $member_type < 10) {
			$awards_student++;													//15.学生获奖数
		}
	}
}

$patents_teacher = 0;
$achievements_teacher = 0;
$patents = Q("patent[date={$start}~{$end}]");
foreach ($patents as $patent) {
	$ac_authors = Q("ac_author[achievement=$patent]");
	foreach ($ac_authors as $author) {
		$member_type = $author->user->member_type;
		if($member_type >= 10 && $member_type < 20) {
			$patents_teacher++;											//教师发明专利数
		}
	}
}
$achievements_teacher = $awards_teacher + $patents_teacher;						//16.教师获奖与成果数

echo '论文数(pubs)=>'.$pubs."\n";
echo '学生获奖数(awards_student)=>'.$awards_student."\n";
echo '教师获奖与成果数(achievements_teacher)=>'.$achievements_teacher."\n";

