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
 * Display keywords in a forum
 *
 * @package   report_forumkeywords
 * @copyright 2018 Andy Chan <ctchan.andy@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
ini_set('memory_limit', '1024M');
require_once(dirname(__FILE__) . '/../../config.php');
require_once(dirname(__FILE__) . '/locallib.php');

// Require Jieba-PHP files
require_once(dirname(__FILE__) . '/jieba-php/vendor/multi-array/MultiArray.php');
require_once(dirname(__FILE__) . '/jieba-php/vendor/multi-array/Factory/MultiArrayFactory.php');
require_once(dirname(__FILE__) . '/jieba-php/class/Jieba.php');
require_once(dirname(__FILE__) . '/jieba-php/class/Finalseg.php');
require_once(dirname(__FILE__) . '/jieba-php/class/Posseg.php');
require_once(dirname(__FILE__) . '/jieba-php/class/JiebaAnalyse.php');

// Use Jieba classes
use Fukuball\Jieba\Jieba;
use Fukuball\Jieba\Finalseg;
use Fukuball\Jieba\Posseg;
use Fukuball\Jieba\JiebaAnalyse;

$id     = required_param('id', PARAM_INT);
$forum  = optional_param('forum', 0, PARAM_INT);
$poscontent  = optional_param_array('poscontent', array(), PARAM_TEXT);
$posfunction  = optional_param_array('posfunction', array(), PARAM_TEXT);
$posspecial  = optional_param_array('posspecial', array(), PARAM_TEXT);
$filterwords = optional_param('filterwords', '', PARAM_TEXT);
$course = $DB->get_record('course', array('id' => $id), '*', MUST_EXIST);

require_login($course);

// Setup page.
$PAGE->set_url('/report/forumkeywords/index.php', array('id' => $id));
$PAGE->set_pagelayout('report');
$returnurl = new moodle_url('/course/view.php', array('id' => $id));

// Check permissions.
$coursecontext = context_course::instance($course->id);
require_capability('report/forumkeywords:view', $coursecontext);

// Finish setting up page.
$PAGE->set_title($course->shortname .': '. get_string('forumkeywords' , 'report_forumkeywords'));
$PAGE->set_heading($course->fullname);
$PAGE->requires->css('/report/forumkeywords/css/index.css');

if (!$poscontent && !$posfunction && !$posspecial) {
    $poscontent = array('n','t','s','f','v','a','b','z','r','m','q','g','i','j','l','eng');
}

$button_content = report_forumkeywords_output_action_buttons($id, $forum, array_merge($poscontent, $posfunction, $posspecial), $filterwords, $PAGE->url);
$wordcloud_content = '';
$table_content = '';
$forum_intro = '';

// Get messages of forum posts and process them
$params = array();
if ($forum !== 0) {
    // Trigger a report viewed event.
    $event = \report_forumkeywords\event\report_viewed::create(array('context' => $coursecontext, 'other' => array('course' => $id, 'forum' => $forum)));
    $event->trigger();

    $forum_detail = report_forumkeywords_get_forum_detail($forum);
    if (trim($forum_detail->intro) != "") {
      $forum_intro .= html_writer::tag('h3', get_string('forumintro', 'forum'));
      $forum_intro .= html_writer::tag('div', $forum_detail->intro, array('class'=>'alert alert-info', 'id'=>'forum-intro'));
    }
    
    $messages = report_forumkeywords_get_forum_posts($forum);
    if (!empty($messages)) {
        // HTML output
        $wordcloud_content .= html_writer::tag('h3', get_string('wordcloud', 'report_forumkeywords'));
        $info_text = html_writer::tag('span', '', array('class'=>'fa fa-info-circle')).' '.get_string('clickonword', 'report_forumkeywords');
        $wordcloud_content .= html_writer::tag('p', $info_text, array('class'=>'alert alert-warning'));
        $wordcloud_content .= html_writer::div('', '', array('id' => 'wordcloud'));
        $wordcloud_content .= html_writer::tag('button', get_string('downloadpng', 'report_forumkeywords'), array('id'=>'downloadpng', 'class'=>'btn btn-primary', 'title'=>get_string('downloadpng', 'report_forumkeywords')));
        $wordcloud_content .= html_writer::tag('canvas', '', array('width'=>'800', 'height'=>'600', 'style'=>'display:none'));
        $table_content .= html_writer::tag('h3', get_string('weighttable', 'report_forumkeywords'));
        
        // Create the keywords/weights table
        $tfidf_table = new html_table();
        $tfidf_table->id = "weight_table";
        $tfidf_table->head = array(get_string('keywords', 'report_forumkeywords'), get_string('tfidfweights', 'report_forumkeywords'));
        $tfidf_table->align = array('center', 'center');
        
        // Strip HTML from all messages and merge them into single string
        $all_messages = '';
        foreach ($messages as $pid => $m) {
            $all_messages .= "\n".str_replace("&nbsp;", "", strip_tags($m->message));
        }
        
        $minwords = 50; // default minimum words
        if (isset($CFG->report_forumkeywords_minwords)) {
            $minwords = $CFG->report_forumkeywords_minwords;
        }
        
        if (strlen($all_messages) > $minwords) {
            // Jieba settings
            Jieba::init(array('mode'=>'default','dict'=>'big'));
            Finalseg::init();
            Posseg::init();
            JiebaAnalyse::init();
            $top_k = 20;
            
            $stop_words_path = "C:\\Bitnami\\moodle-3.6.0-0\\apps\\moodle\\htdocs\\report\\forumkeywords\\jieba-php\\dict\\stop_words.txt";
            $stop_words = fopen($stop_words_path, "r");
            
            JiebaAnalyse::setStopWords($stop_words_path);
            $allowPOS = array_merge($poscontent, $posfunction, $posspecial);
            $tags = JiebaAnalyse::extractTags($all_messages, $top_k, array('allowPOS'=>$allowPOS));

            // Remove words from results if user entered filter
            if ($filterwords) {
                $fwords = explode(',', $filterwords);
                foreach ($fwords as $fw) {
                    if (isset($tags[$fw])) unset($tags[$fw]);
                }
            }

            // Construct arrays for generating word cloud
            // Add rows to the keywords/weights table
            $word_freq = array();
            foreach ($tags as $word => $freq) {
                $word_freq[] = array("text"=>$word, "size"=>round($freq*100));
                $search_link = html_writer::tag('a', $word, array('href'=>$CFG->wwwroot.'/mod/forum/search.php?id='.$id.'&search='.$word, 'target'=>'_blank', 'title'=>$word));
                $tfidf_table->data[] = array($search_link, round($freq, 2));
            }
            
            // Output the keywords/weights table to HTML
            $table_content .= html_writer::table($tfidf_table);
            
            // Pass the words array to generate the word cloud
            $params[] = $word_freq;
            $params[] = $id;
            $PAGE->requires->js_call_amd('report_forumkeywords/manage', 'init', $params);
        } else {
            $wordcloud_content = html_writer::tag('h3', get_string('lessthanminwords', 'report_forumkeywords'));
        }
    }
}

// Display everything to the user.
echo $OUTPUT->header();
echo $button_content;
echo $forum_intro;
echo $wordcloud_content;
echo $table_content;
echo $OUTPUT->footer();
