<?php

// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * This is a test file, which contain all the function that I tried....
 *
 * @package     mod_wemessage
 * @copyright   2022 aVr0Ra <ysmormichael@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

//echo "Hello? anybody here?";
/*
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
*/

$DATEinfo = getdate();
$date = $DATEinfo['mday'];
$month = $DATEinfo['mon'];
$year = $DATEinfo['year'];
$hour = $DATEinfo['hours'];
$min = $DATEinfo['minutes'];
$sec = $DATEinfo['seconds'];



require_once('../../config.php');

global $CFG , $DB;
require_once($CFG->dirroot . '/calendar/lib.php');
require_once($CFG->dirroot . '/mod/assign/lib.php');
require_once($CFG->dirroot . '/enrol/externallib.php');
require_once($CFG->dirroot . '/mod/assign/locallib.php');
require_once($CFG->dirroot . '/lib/accesslib.php');
require('wework/api/messageSending/Messager.php');

include_once("wework/api/src/CorpAPI.class.php");
include_once("wework/api/src/ServiceCorpAPI.class.php");
include_once("wework/api/src/ServiceProviderAPI.class.php");

echo time() . "<br />";
$nowTime = time();


$config = require('wework/api/messageSending/config.php');


$agentId = $config['APP_ID'];
$api = new CorpAPI($config['CORP_ID'], $config['APP_SECRET']);
$list = $api->UserList(3, 0); // you can change the departmen_id here, which is the 1st variable.
$sz = count($list);
$arrayMoodleID2Userid = array ();

for ($i = 0 ; $i < $sz ; $i ++) {
    $attrsSZ = count($list[$i]->extattr->attrs);
    for ($j = 0 ; $j <= $attrsSZ ; $j ++) {
        if ($list[$i]->extattr->attrs[$j]->name == "moodleID") {  //you can change the extaatr var name here
            $arrayMoodleID2Userid[$list[$i]->extattr->attrs[$j]->value] = $list[$i]->userid;
            break;
        }
    }
}


//var_dump($arrayMoodleID2Userid);

//get all courses data
$allcourses = get_courses();
//print_object($allcourses);


foreach ($allcourses as $I => $course) {
    if ($course->id == 1) {
        //default course needs to be skipped
        continue;
    }

    $allassigns = get_all_instances_in_course('assign' , $course);

    //print_object($allassigns);
    //echo "<br /> <br />";

    $context = get_context_instance(CONTEXT_COURSE, $course->id);
    $allUsersOfCurrentCourse =  get_enrolled_users($context);

    //print_object($allUsersOfCurrentCourse);

    foreach ($allUsersOfCurrentCourse as $userI => $user) {
        $roles = get_user_roles($context, $user->id, true);
        $role = key($roles);
        $rolename = $roles[$role]->shortname;
        //user's role

        if ($rolename == "student") {
            foreach($allassigns as $assignid => $assign) {
                $cm = get_coursemodule_from_id('assign' , $assign->coursemodule);
                $result = assign_get_completion_state($course, $cm, $user->id, false);

                if ($result == false && $nowTime + 86400 >= $assign->duedate && $assign->duedate > $nowTime + 82800) {
                    $weworkMessage = $user->firstname . " " . $user->lastname .
                        "你好，目前距离Moodle上的" . $assign->name ."作业的结束还有不到24小时的时间。请尽快提交！";
                    $weworkUserID = $arrayMoodleID2Userid[$user->username];

                    //var_dump($weworkUserID); echo "<br /> <br />";
                    //var_dump($weworkMessage); echo "<br /> <br />";

                    echo "<br />" . $weworkUserID . " and the message is " . $weworkMessage . "<br />";
                    $weworkMessageSend = new myMessage($weworkUserID, $weworkMessage);
                    //var_dump($now);
                    $weworkMessageSend->sendMessage();
                }
            }
        }
        else {
            continue;
        }
    }
}

//$course = $DB->get_record('course', array('id' => 2));
//$assign = get_all_instances_in_course('assign' , $course);

//print_object($course);


//$context = get_context_instance(CONTEXT_COURSE, 2);
//$allusers =  get_enrolled_users($context);

//print_object($allusers);

//var_dump($assign);

//foreach ($assign as $assignid => $nowassign) {
    //if ($nowassign->duedate <= $nowTime + 86400) {
     //   continue;
    //}//already notified


    /*echo "assign id = " . $assignid . ": <br />";
    var_dump($nowassign);
    echo "<br/ >";*/


    /*
    $cm = get_coursemodule_from_id('assign' , $nowassign->coursemodule);
    $userid = 4;
    $result = assign_get_completion_state($course, $cm, $userid, false);
    echo "<br /> this is the sub result of student " . 4 . " <br />";
    var_dump($result);*/
//}

//assign_get_completion_state($course, $assign->get_course_module(), $student->id, false);
/*
$info = get_fast_modinfo($course);
print_object($info);*/


//$cm = get_coursemodule_from_id('assign' , );
//var_dump($course);
//$assign = ;

/*
foreach ($arr as $key => $value) {
    echo "<br /> i = " . $key . "<br />";

    var_dump($value);

    echo "<br/>courseid = ";

    var_dump($value->courseid);

    $intCourseId = (integer) $value->courseid;

    echo "<br /> <br />";

    //var_dump($nowCourseArr);

    echo "<br /> <br />";
}*/
