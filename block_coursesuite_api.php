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
 * main block renderer for coursesuite_api block.
 *
 * @package    block_coursesuite_api
 * @copyright  2020 tim st.clair
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class block_coursesuite_api extends block_list { // base {

    public function init() {
        $this->title = get_string('coursesuite_api', 'block_coursesuite_api');
    }

    public function instance_allow_multiple() {
      return false;
    }

    function has_config() {
        return true;
    }

    public function applicable_formats() {
      return array(
        'mod' => false,
        'site-index' => false,
        'course-view' => false,
        'my-index' => false,
        'course-index-category' => true
      );
    }

    public function get_content() {
        global $OUTPUT;
        if ($this->content !== null) {
          return $this->content;
        }

        if (!isloggedin()) {
            return null;
        }

        $cache = $this->get_cache();

        $this->content         =  new stdClass;
        $this->content->items  = array();
        $this->content->icons  = array();

        if (!$cache) { // false or empty
            $this->content->items[] = 'An API error occurred';
            $this->content->icons[] = $OUTPUT->pix_icon('i/risk_xss', get_string('error')); // html_writer::empty_tag('img', array('src' => 'about:blank', 'class' => 'icon'));
        } else {
            $lightbox = 'function e(){return Array.from(document.querySelectorAll("body *")).map(function(a){return parseFloat(window.getComputedStyle(a).zIndex)}).filter(function(a){return!isNaN(a)}).sort(function(a,b){return a-b}).pop()}function f(a){a&&a.preventDefault();var b=document.createElement("div"),d=document.createElement("iframe"),c=document.createElement("div");o="cs-overlay";if(x=document.querySelector("#"+o))return document.body.style.overflow="auto",document.body.removeChild(x),location.href=location.href,!1;b.id=o;b.style="position:fixed;top:0;width:100%;height:100%;background:rgba(0,0,0,.5);z-index:"+e()+1;b.appendChild(d);d.style="position:absolute;width:90%;height:90%;left:5%;top:5%";d.src=a.target.href;d.setAttribute("allow","microphone; camera; fullscreen;");d.setAttribute("allowfullscreen",true);c.style="position:absolute;top:calc(5% - 24px);left:96%;width:24px;height:24px;cursor:pointer";c.innerHTML="<svg xmlns=\"http://www.w3.org/2000/svg\" viewBox=\"0 0 24 24\"><path stroke=\"white\" d=\"M0 0l24 24M0 24L24 0\"/></svg>";c.onclick=f;b.appendChild(c);document.body.appendChild(b);document.body.style.overflow="hidden";return 1};return f(event);';
            $cache = json_decode($cache);

            $categoryid = optional_param('categoryid', 1, PARAM_INT);

            foreach ($cache as $index => $app) {
                if ($app->app_key==='scormninja') continue; // not appropriate for moodle
                // $url = str_replace('{token}', $token, $app->launch) . 'moodle/';
                $url = new moodle_url("/blocks/coursesuite_api/launch.php", array("categoryid" => $categoryid, "app" => $app->app_key));
                $this->content->items[] = html_writer::tag('a', $app->name, array('href' => $url, 'target' => $app->app_key, 'onclick' => $lightbox));
                $this->content->icons[] = html_writer::empty_tag('img', array('src' => 'data:image/svg+xml;base64,' . base64_encode($app->glyph), 'class' => 'icon')); // utf8,' . $app->glyph -> leads to misquoting and image breaks
            }
        }

        return $this->content;
    }

    // add an unconfigured classname if no cache data is present
    public function html_attributes() {
        $attributes = parent::html_attributes(); // Get default values
        $cache = get_config('coursesuite_api', 'cache');
        if (empty($cache)) {
            $attributes['class'] .= ' block_'. $this->name() . '_unconfigured';
        }
        return $attributes;
    }

    // customise the block title
    public function specialization() {
        if (isset($this->config)) {
            if (empty($this->config->title)) {
                $this->title = get_string('blocktitle', 'block_coursesuite_api');
            } else {
                $this->title = $this->config->title;
            }
        }
    }

    /*
    *   Phone Home to coursesuite api site and gather launch information based on apikey
    */
    private function get_cache() {
        global $CFG;

        $cache = get_config('coursesuite_api', 'cache');
        $debug_mode = (get_config('coursesuite_api', 'debug') === '1');

        if (empty($cache) || $debug_mode) {

            $apikey = get_config('coursesuite_api', 'apikey');
            if (empty($apikey)) return false;

            $apihost = "https://www.coursesuite.ninja";
            $host = $_SERVER['HTTP_HOST'];
            $c = new curl(["debug" => $debug_mode, "cache"=>true]);

            $headers = array();
            $headers[] = "Authorization: Bearer: {$apikey}";
            $c->setHeader($headers);

            $options = array();
            $options["CURLOPT_RETURNTRANSFER"] = true;

            if (strpos($host, ".test") !== false) { // override endpoint in test environment
                $apihost = "https://coursesuite.ninja.test";
                $options["CURLOPT_SSL_VERIFYHOST"] = false;
                $options["CURLOPT_SSL_VERIFYPEER"] = false;
            }

            $c->setopt($options);

            $response = $c->post($apihost . "/api/info/");
            $info = $c->get_info();
            if (!empty($info['http_code']) && $info['http_code'] === 200) {

                $cache = $response;
                set_config("cache", $cache, "coursesuite_api");

            } else {
                if ($debug_mode) {
                    debugging(print_r($info,true));
                }
                return false;
            }
        }

        return $cache;

    }

    // configure the block -> save
    public function instance_config_save($data,$nolongerused =false) {

        // reset cache
        unset_config('cache', 'coursesuite_api');

        // And now forward to the default implementation defined in the parent class
        return parent::instance_config_save($data,$nolongerused);
    }

}