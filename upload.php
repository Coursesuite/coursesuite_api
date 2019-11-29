<?php
// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * repo upload form
 * requires authorization header to be passed through
 * i.e. nginx  = fastcgi_param HTTP_AUTHORIZATION $http_authorization;
 *      apache = SetEnvIf Authorization "(.*)" HTTP_AUTHORIZATION=$1
 *
 * @package    respository_coursesuite
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// come one, come all
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: PUT, POST, GET');
header('Access-Control-Allow-Headers: X-Requested-With, X-Filename, Authorization');

require_once('../../config.php');

require_once($CFG->libdir.'/moodlelib.php');
require_once($CFG->libdir.'/filestorage/zip_packer.php');
require_once($CFG->dirroot.'/backup/util/includes/restore_includes.php');
// require_once($CFG->dirroot.'/mod/scorm/locallib.php');

defined('MOODLE_INTERNAL') || die();

$apikey = get_config('coursesuite_api', 'apikey');
$apisecret = get_config('coursesuite_api', 'secretkey');
$expected_bearer_token = md5($apikey . $apisecret);

$categoryid = optional_param('categoryid', 1, PARAM_INT);
$ping = optional_param('ping', 0, PARAM_INT);

if ($ping === 1) die("pong"); // for externally testing to see if this file exists

$bearer = null;
$method = 0;
if (isset($_SERVER['Authorization'])) {
    $bearer = trim($_SERVER["Authorization"]);
    $method = 1;
} else if (isset($_SERVER['HTTP_AUTHORIZATION'])) { //Nginx or fast CGI
    $bearer = trim($_SERVER["HTTP_AUTHORIZATION"]);
    $method = 2;
} else if (isset($_SERVER['HTTP_BEARER'])) { // Apache
    $bearer = trim($_SERVER["HTTP_BEARER"]);
    $method = 3;
} elseif (function_exists('apache_request_headers')) {
    $requestHeaders = apache_request_headers();
    $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
    $method = 4;
    if (isset($requestHeaders['Authorization'])) {
        $bearer = trim($requestHeaders['Authorization']);
        $method = 5;
    }
}

// if there is no bearer token then stop now
if (empty($bearer)) die("-1");

// is the bearer specified the correct format?
$bearer = str_ireplace(['Bearer: ','Bearer '], '', $bearer);
if (!preg_match('/^[a-f0-9]{32}$/', $bearer)) die("-2");

// does the bearer match the expected value?
if (strcasecmp($bearer,$expected_bearer_token) !== 0) die("-3");

// save the incoming file into a temporary folder
$api_folder = $CFG->dataroot . '/temp/coursesuite_api/'; // kinda unneccesary
if (!file_exists($api_folder)) mkdir($api_folder, 0777, true);
$method = $_SERVER['REQUEST_METHOD'];
$uploaded_file = '';

// debug:
// $raw = print_r($_SERVER, true);
// $files = print_r($_FILES, true);

if ($method == 'POST') { // direct from app

    foreach ($_FILES as $file) { // should only be 1 anyway
        $out = $api_folder . basename($file["name"]) . ".zip";
        if (file_exists($out)) unlink($out); // overwrite
        move_uploaded_file($file["tmp_name"], $out);
        $uploaded_file = $out;
        // $uploads = "post " . $file["tmp_name"] . " to " . $out;
    }

} elseif ($method == 'PUT') { // generally from curl proxy, e.g. publish.php

    $filename = basename($_SERVER['HTTP_X_FILENAME']); // don't accept paths
    $dest = $api_folder . $filename . ".zip";
    if (file_exists($dest)) unlink($dest); // overwrite
    // $uploads = "put " . $filename . " to " . $dest;
    $in = fopen('php://input','r');
    $out = fopen($dest,'w');
    $uploaded_file = $out;
    stream_copy_to_stream($in,$out);
    // file_put_contents($dest, file_get_contents('php://input'));
}

// extract the manifest from the package
// designed for coursesuite course manifests in mind; may not work for general scorm
$zipname = basename($out);
$temp_folder = $api_folder . md5(mt_rand() . time());
mkdir($temp_folder,0777);
$zip = new ZipArchive;
$zip->open($out);
$zip->extractTo($temp_folder, 'imsmanifest.xml');
$zip->close();
$manifestObj = loadManifestObject($temp_folder);
delTree($temp_folder);
$title = $manifestObj["manifest"]["organizations"]["organization"]["title"];
$launch = $manifestObj["manifest"]["resources"]["resource"]["@href"];
$identifier = str_replace("ORG-", "", $manifestObj["manifest"]["organizations"]["@default"]);
$version = substr($manifestObj["manifest"]["@version"],"2004") !== false ? "2004" : "1.2";

// ensure title is a unique course name
list($fullname, $shortname) = \restore_dbops::calculate_course_names(1,$title,$title);

// now pick up the backup mbz file from the config filearea and decompress it into a temporary file area
// $backup_filename = get_config('coursesuite_api', 'coursebackup');
// $fs = get_file_storage();
// if (($backup_file_fs = $fs->get_file(
//     context_system::instance()->id,
//     'coursesuite_api',
//     'backup',
//     0,
//     '/',
//     $backup_filename
// ))) {
//     if (file_exists($api_folder . $backup_filename)) unlink($api_folder . $backup_filename);
//     if ($backup_file_fs->copy_content_to($api_folder . $backup_filename)) {
// // $temp_folder = $api_folder . md5(mt_rand() . time());
// $phar = new PharData($api_folder . $backup_filename);
// $phar->extractTo($temp_folder);
// unlink($api_folder . $backup_filename);

$backup_folder_name = md5(mt_rand() . time());
$temp_folder = $CFG->dataroot . '/temp/backup/' . $backup_folder_name;
extract_backup("./db/backup.mbz", $temp_folder);

//delTree($temp_folder . "/files/f1");
//delTree($temp_folder . "/files/da");

// move the package into the files area
$contenthash = sha1_file($out);
$contentsize = filesize($out);
$timestamp = time();
$filespath = $temp_folder . "/files/" . substr($contenthash, 0, 2) . "/";
if (!file_exists($filespath)) mkdir($filespath,0777);
rename($uploaded_file, $filespath . $contenthash);

// build a new files.xml
$files_xml = <<<EOT
<?xml version="1.0" encoding="UTF-8"?>
<files>
  <file id="1">
    <contenthash>{$contenthash}</contenthash>
    <contextid>180</contextid>
    <component>mod_scorm</component>
    <filearea>package</filearea>
    <itemid>0</itemid>
    <filepath>/</filepath>
    <filename>{$zipname}.zip</filename>
    <userid>2</userid>
    <filesize>{$contentsize}</filesize>
    <mimetype>application/zip</mimetype>
    <status>0</status>
    <timecreated>1574638284</timecreated>
    <timemodified>{$timestamp}</timemodified>
    <source>{$zipname}.zip</source>
    <author>Coursesuite API</author>
    <license>allrightsreserved</license>
    <sortorder>0</sortorder>
    <repositorytype>$@NULL@$</repositorytype>
    <repositoryid>$@NULL@$</repositoryid>
    <reference>$@NULL@$</reference>
   </file>
  <file id="2">
    <contenthash>da39a3ee5e6b4b0d3255bfef95601890afd80709</contenthash>
    <contextid>80</contextid>
    <component>mod_scorm</component>
    <filearea>content</filearea>
    <itemid>0</itemid>
    <filepath>/</filepath>
    <filename>.</filename>
    <userid>$@NULL@$</userid>
    <filesize>0</filesize>
    <mimetype>$@NULL@$</mimetype>
    <status>0</status>
    <timecreated>1574936340</timecreated>
    <timemodified>{$timestamp}</timemodified>
    <source>$@NULL@$</source>
    <author>$@NULL@$</author>
    <license>$@NULL@$</license>
    <sortorder>0</sortorder>
    <repositorytype>$@NULL@$</repositorytype>
    <repositoryid>$@NULL@$</repositoryid>
    <reference>$@NULL@$</reference>
  </file>
</files>
EOT;
file_put_contents($temp_folder . "/files.xml", $files_xml);

// update references inside xml packages
$xml = file_get_contents($temp_folder . "/activities/scorm_1/scorm.xml");
$xml = replace_between($xml, "sha1hash", $contenthash);
$xml = replace_between($xml, "name", $fullname);
$xml = replace_between($xml, "title", $title);
$xml = replace_between($xml, "manifest", $identifier);
$xml = replace_between($xml, "reference", "{$zipname}.zip");
$xml = replace_between($xml, "version", "SCORM_{$version}");
$xml = str_replace("<launch>dummySCO.htm</launch>", "<launch>{$launch}</launch>", $xml);
$xml = replace_between($xml, "updatefreq", 3); // means we don't have to do a scorm_parse here
file_put_contents($temp_folder ."/activities/scorm_1/scorm.xml", $xml);

$xml = file_get_contents($temp_folder . "/course/course.xml");
$xml = str_replace(['COURSENAME','SHORTNAME'], [$fullname, $shortname], $xml);
$xml = replace_between($xml, "timemodified", $timestamp);
file_put_contents($temp_folder ."/course/course.xml", $xml);

$xml = file_get_contents($temp_folder . "/activities/scorm_1/grades.xml");
$xml = replace_between($xml, "itemname", $title);
$xml = replace_between($xml, "timemodified", $timestamp);
file_put_contents($temp_folder ."/activities/scorm_1/grades.xml", $xml);

$xml = file_get_contents($temp_folder . "/gradebook.xml");
$xml = replace_between($xml, "timemodified", $timestamp);
file_put_contents($temp_folder ."/gradebook.xml", $xml);

$xml = file_get_contents($temp_folder . "/moodle_backup.xml");
$xml = str_replace(['COURSENAME','SHORTNAME','PACKAGENAME'], [$fullname, $shortname, $title], $xml);
file_put_contents($temp_folder ."/moodle_backup.xml", $xml);

// restore the course
// see https://github.com/moodleuulm/moodle-local_sandbox/blob/master/classes/task/restore_courses.php line 156
// https://docs.moodle.org/dev/Restore_2.0_for_developers#Automatically_triggering_restore_in_code
$courseid = restore_course($backup_folder_name, $categoryid);

// delTree($temp_folder); // the controller does this anyway
@unlink($out); // the zip file can be trashed though

$result = [
    "statuscode" => 0,
    "system" => "moodle",
    "version" => $CFG->release, // human readable
    "action" => "redirect",
    "location" => "/course/view.php?id={$courseid}"
];

header("Content-Type: application/json");
echo json_encode($result);

die();



/* -------------------------------------- functions ------------------------------------------- */


function delTree($dir) {
    if (!file_exists($dir)) return;
    $files = array_diff(scandir($dir), array('.', '..'));
    foreach ($files as $file) {
        (is_dir("$dir/$file")) ? delTree("$dir/$file") : unlink("$dir/$file");
    }
    return rmdir($dir);
}

function loadManifestObject($path) {
    $xmlNode = simplexml_load_file($path . '/imsmanifest.xml');
    return xmlToArray($xmlNode);
}

function xmlToArray($xml, $options = array()) {
    $defaults = array(
        'namespaceSeparator' => ':',//you may want this to be something other than a colon
        'attributePrefix' => '@',   //to distinguish between attributes and nodes with the same name
        'alwaysArray' => array(),   //array of xml tag names which should always become arrays
        'autoArray' => true,        //only create arrays for tags which appear more than once
        'textContent' => '$',       //key used for the text content of elements
        'autoText' => true,         //skip textContent key if node has no attributes or child nodes
        'keySearch' => false,       //optional search and replace on tag and attribute names
        'keyReplace' => false       //replace values for above search values (as passed to str_replace())
    );
    $options = array_merge($defaults, $options);
    $namespaces = $xml->getDocNamespaces();
    $namespaces[''] = null; //add base (empty) namespace

    //get attributes from all namespaces
    $attributesArray = array();
    foreach ($namespaces as $prefix => $namespace) {
        foreach ($xml->attributes($namespace) as $attributeName => $attribute) {
            //replace characters in attribute name
            if ($options['keySearch']) $attributeName =
                    str_replace($options['keySearch'], $options['keyReplace'], $attributeName);
            $attributeKey = $options['attributePrefix']
                    . ($prefix ? $prefix . $options['namespaceSeparator'] : '')
                    . $attributeName;
            $attributesArray[$attributeKey] = (string)$attribute;
        }
    }

    //get child nodes from all namespaces
    $tagsArray = array();
    foreach ($namespaces as $prefix => $namespace) {
        foreach ($xml->children($namespace) as $childXml) {
            //recurse into child nodes
            $childArray = xmlToArray($childXml, $options);
            list($childTagName, $childProperties) = each($childArray);

            //replace characters in tag name
            if ($options['keySearch']) $childTagName =
                    str_replace($options['keySearch'], $options['keyReplace'], $childTagName);
            //add namespace prefix, if any
            if ($prefix) $childTagName = $prefix . $options['namespaceSeparator'] . $childTagName;

            if (!isset($tagsArray[$childTagName])) {
                //only entry with this key
                //test if tags of this type should always be arrays, no matter the element count
                $tagsArray[$childTagName] =
                        in_array($childTagName, $options['alwaysArray']) || !$options['autoArray']
                        ? array($childProperties) : $childProperties;
            } elseif (
                is_array($tagsArray[$childTagName]) && array_keys($tagsArray[$childTagName])
                === range(0, count($tagsArray[$childTagName]) - 1)
            ) {
                //key already exists and is integer indexed array
                $tagsArray[$childTagName][] = $childProperties;
            } else {
                //key exists so convert to integer indexed array with previous value in position 0
                $tagsArray[$childTagName] = array($tagsArray[$childTagName], $childProperties);
            }
        }
    }

    //get text content of node
    $textContentArray = array();
    $plainText = trim((string)$xml);
    if ($plainText !== '') $textContentArray[$options['textContent']] = $plainText;

    //stick it all together
    $propertiesArray = !$options['autoText'] || $attributesArray || $tagsArray || ($plainText === '')
            ? array_merge($attributesArray, $tagsArray, $textContentArray) : $plainText;

    //return node as array
    return array(
        $xml->getName() => $propertiesArray
    );
}

function extract_backup($backup, $dest) {
    if (!file_exists($dest)) mkdir($dest, 0777, true);
    $fb = get_file_packer('application/vnd.moodle.backup');
    return $fb->extract_to_pathname($backup, $dest);
}

function restore_course($folder, $categoryid) {
// function restore_course($fullname, $shortname, $folder, $categoryid) {
    global $DB;
    $admin = get_admin();

    $transaction = $DB->start_delegated_transaction();

    // $newcourseid = \restore_dbops::create_new_course($fullname, $shortname, $categoryid);
    $newcourseid = \restore_dbops::create_new_course('','', $categoryid);

    $controller = new \restore_controller(
        $folder, // name of extracted folder, assumbed to exist inside dirroot / temp / backup
        $newcourseid,
        \backup::INTERACTIVE_NO,
        \backup::MODE_SAMESITE,
        $admin->id,
        \backup::TARGET_NEW_COURSE
    );

    //$controller->get_logger()->set_next(new \output_indented_logger(\backup::LOG_INFO, true, true));
    $controller->execute_precheck(true);
    $controller->execute_plan();
    $controller->destroy();

    // ensure format is set (doesn't seem to come through with non-interactive restore)
    $data = $DB->get_record('course_format_options', array('courseid'=>$newcourseid,'sectionid'=>0,'name'=>'activitytype'), '*', MUST_EXIST);
    $data->value = 'scorm';
    $DB->update_record('course_format_options',$data);
    // course_get_format($data)->update_course_format_options($data);

    // parse the scorm package (extracts it to generate the mdl_files representing the package)
    // normally this is done by editing the package, but we are restoring a new manifest
    // not required if scorm.xml's "updatefreq" = 3
    // scorm_parse($scorm, $full)

    $transaction->allow_commit();
    return $newcourseid;
}


function replace_between($str, $tag, $replacement) {
    $needle_start = "<{$tag}>";
    $needle_end = "</{$tag}>";
    $pos = strpos($str, $needle_start);
    $start = $pos === false ? 0 : $pos + strlen($needle_start);

    $pos = strpos($str, $needle_end, $start);
    $end = $pos === false ? strlen($str) : $pos;

    return substr_replace($str, $replacement, $start, $end - $start);
}


// debug:
// $log = implode(PHP_EOL, ["method=$method", "apikey=$apikey", "bearer=$bearer", "raw=$raw", "files=$files",  "uploads=$uploads","-----",""]);
// $log = PHP_EOL . "CategoryId=$categoryid, ContentHash=$contenthash, backup-filename=$backup_filename, log=$logg" . PHP_EOL;
// file_put_contents($dest . "upload_log.txt", $log, FILE_APPEND);
