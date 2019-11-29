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

require_once('../../config.php');
require_once($CFG->libdir.'/filelib.php');

defined('MOODLE_INTERNAL') || die();

$apikey 	= get_config('coursesuite_api', 'apikey');
$apisecret 	= get_config('coursesuite_api', 'secretkey');
$cache 		= get_config('coursesuite_api', 'cache');

$app_key 	= required_param('app', PARAM_ALPHAEXT);
$apihost	= "https://www.coursesuite.ninja";
$host 		= $_SERVER['HTTP_HOST'];
$categoryid = required_param('categoryid', PARAM_INT);

if (empty($apikey) || empty($apisecret) || empty($cache)) die();

$c = new curl(["debug"=>false,"cache"=>true]);

$options = array();
$options["CURLOPT_HTTPAUTH"] = CURLAUTH_DIGEST;
$options["CURLOPT_USERPWD"] = $apikey . ":" . $apisecret;
$options["CURLOPT_FOLLOWLOCATION"] = true;
$options["CURLOPT_RETURNTRANSFER"] = true;

if (strpos($host, ".test")!==false) {
	$apihost = "https://coursesuite.ninja.test";
	$options["CURLOPT_SSL_VERIFYHOST"] = false;
	$options["CURLOPT_SSL_VERIFYPEER"] = false;
}

$c->setopt($options);

// when creating the token you need to tell the api the publishing url, which incorporates the category id
$url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://{$host}/blocks/coursesuite_api/upload.php?categoryid={$categoryid}";
$postdata = ["publish_url" => $url];
$postbody = http_build_query($postdata);

// create a new launch token
$response =  $c->post($apihost . "/api/createToken/", $postbody);
$info = $c->get_info();
if (!empty($info['http_code']) && $info['http_code'] === 200) {
    $auth = json_decode($response);
    $token = $auth->token;
    unset($auth,$response);
} else if ($CFG->debugdisplay) {
	debugging(print_r($info,true));
} else {
	die("bad token");
}

$cache = json_decode($cache);
$launch_url = "";

foreach ($cache as $index => $app) {
	if ($app->app_key === $app_key) {
		$launch_url = str_replace('{token}', $token, $app->launch) . 'moodle/';
	}
}

if (empty($launch_url)) {
	die("bad key");
}

header("Location: $launch_url");
