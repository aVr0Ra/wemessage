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
 * Library of interface functions and constants.
 *
 * @package     mod_wemessage
 * @copyright   2022 aVr0Ra <ysmormichael@gmail.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Return if the plugin supports $feature.
 *
 * @param string $feature Constant representing the feature.
 * @return true | null True if the feature is supported, null otherwise.
 */
function wemessage_supports($feature) {
    switch ($feature) {
        case FEATURE_MOD_INTRO:
            return true;
        default:
            return null;
    }
}

/**
 * Saves a new instance of the mod_wemessage into the database.
 *
 * Given an object containing all the necessary data, (defined by the form
 * in mod_form.php) this function will create a new instance and return the id
 * number of the instance.
 *
 * @param object $moduleinstance An object from the form.
 * @param mod_wemessage_mod_form $mform The form.
 * @return int The id of the newly inserted record.
 */
function wemessage_add_instance($moduleinstance, $mform = null) {
    global $DB;

    $moduleinstance->timecreated = time();

    $id = $DB->insert_record('wemessage', $moduleinstance);

    return $id;
}

/**
 * Updates an instance of the mod_wemessage in the database.
 *
 * Given an object containing all the necessary data (defined in mod_form.php),
 * this function will update an existing instance with new data.
 *
 * @param object $moduleinstance An object from the form in mod_form.php.
 * @param mod_wemessage_mod_form $mform The form.
 * @return bool True if successful, false otherwise.
 */
function wemessage_update_instance($moduleinstance, $mform = null) {
    global $DB;

    $moduleinstance->timemodified = time();
    $moduleinstance->id = $moduleinstance->instance;

    return $DB->update_record('wemessage', $moduleinstance);
}

/**
 * Removes an instance of the mod_wemessage from the database.
 *
 * @param int $id Id of the module instance.
 * @return bool True if successful, false on failure.
 */
function wemessage_delete_instance($id) {
    global $DB;

    $exists = $DB->get_record('wemessage', array('id' => $id));
    if (!$exists) {
        return false;
    }

    $DB->delete_records('wemessage', array('id' => $id));

    return true;
}


function wemessage_cron() {

    echo time() . "<br />";
    $nowTime = time();

    $DATEinfo = getdate();
    $min = $DATEinfo['minutes'];
    $sec = $DATEinfo['seconds'];

    if ($min == 0 && $sec == 0) { // will run every 86400 seconds which is 1 hour
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
    }
}
