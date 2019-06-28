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
 * Local library functions for the forumkeywords report.
 *
 * @package   report_forumkeywords
 * @copyright 2018 Andy Chan <ctchan.andy@gmail.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

// Get all forums in a given course and return a simple array
function report_forumkeywords_get_forum_options($id) {
    global $DB, $USER;
    
    $forums = $DB->get_records_sql("
        SELECT f.*
        FROM {forum} f
            LEFT JOIN {forum_digests} d ON d.forum = f.id AND d.userid = ?
        WHERE f.course = ?
        ", array($USER->id, $id));
    
    $modinfo = get_fast_modinfo($id);
    
    $generalforums = array();
    
    foreach ($modinfo->get_instances_of('forum') as $forumid => $cm) {
      if (!$cm->uservisible or !isset($forums[$forumid])) {
          continue;
      }
      
      $forum = $forums[$forumid];
      
      if (!$context = context_module::instance($cm->id, IGNORE_MISSING)) {
          // Shouldn't happen.
          continue;
      }

      if (!has_capability('mod/forum:viewdiscussion', $context)) {
          // User can't view this one - skip it.
          continue;
      }
      
      $post_count = $DB->count_records_sql('
          SELECT COUNT(*) 
          FROM {forum_posts} p 
          WHERE p.discussion IN 
              (SELECT d.id FROM {forum_discussions} d WHERE d.forum = ?) AND p.deleted = 0
      ', array($forumid));
      
      $generalforums[$forum->id] = $forum->name." [$post_count]";
    }
    return $generalforums;
}

// Output the forum select dropdown and the extract button
function report_forumkeywords_output_action_buttons($id, $forum, $pos, $words, $url) {
    global $OUTPUT;
    $pos_content = array('n', 't', 's', 'f', 'v', 'a', 'b', 'z', 'r', 'm', 'q');
    $pos_function = array('d', 'p', 'c', 'u', 'e', 'y', 'o', 'h', 'k', 'x', 'w');
    $pos_special = array('g', 'i', 'j', 'l', 'eng');
    $forums = report_forumkeywords_get_forum_options($id);
    
    $forumurl = clone $url;
    $forumurl->params(array('forum' => $forum));
    
    $checked = array();
    $html_pos_content = '';
    foreach ($pos_content as $p) {
        $checked[$p] = in_array($p, $pos) ? ' checked' : '';
        $html_pos_content .= '<input class="form-check-input" type="checkbox" name="poscontent[]" id="pos_'.$p.'" value="'.$p.'"'.$checked[$p].' /><label for="pos_'.$p.'">'.get_string('pos:'.$p, 'report_forumkeywords').'</label>';
    }
    $html_pos_function = '';
    foreach ($pos_function as $p) {
        $checked[$p] = in_array($p, $pos) ? ' checked' : '';
        $html_pos_function .= '<input class="form-check-input" type="checkbox" name="posfunction[]" id="pos_'.$p.'" value="'.$p.'"'.$checked[$p].' /><label for="pos_'.$p.'">'.get_string('pos:'.$p, 'report_forumkeywords').'</label>';
    }
    $html_pos_special = '';
    foreach ($pos_special as $p) {
        $checked[$p] = in_array($p, $pos) ? ' checked' : '';
        $html_pos_special .= '<input class="form-check-input" type="checkbox" name="posspecial[]" id="pos_'.$p.'" value="'.$p.'"'.$checked[$p].' /><label for="pos_'.$p.'">'.get_string('pos:'.$p, 'report_forumkeywords').'</label>';
    }
    
    $html_row_start = '<div class="forum-group row">'."\n".'<div class="col-sm-12">'."\n";
    $html_row_end = '</div>'."\n".'</div>'."\n";
    
    $html = '<form class="forumselectform" action="index.php" method="post">'."\n";
        $html .= '<input type="hidden" name="id" value="'.$id.'" />'."\n";
        // Forum select
        $html .= $html_row_start;
            $html .= '<div class="form-check form-check-inline">'."\n";
            $html .= html_writer::label(get_string('forumsincourse', 'report_forumkeywords'), 'menuforum', false, array('class' => 'form-check-label'))." \n";
            $html .= html_writer::select($forums, 'forum', $forum);
            $html .= '</div>'."\n";
        $html .= $html_row_end;
        // POS (content words) checkboxes
        $html .= $html_row_start;
            $html .= '<div class="form-check form-check-inline">'."\n";
            $html .= html_writer::label(get_string('allowposcontent', 'report_forumkeywords'), 'checkboxposcontent', false, array('class' => 'form-check-label'))." \n";
            $html .= $html_pos_content;
            $html .= '</div>'."\n";
        $html .= $html_row_end;
        // POS (function words) checkboxes
        $html .= $html_row_start;
            $html .= '<div class="form-check form-check-inline">'."\n";
            $html .= html_writer::label(get_string('allowposfunction', 'report_forumkeywords'), 'checkboxposfunction', false, array('class' => 'form-check-label'))." \n";
            $html .= $html_pos_function;
            $html .= '</div>'."\n";
        $html .= $html_row_end;
        // POS (special) checkboxes
        $html .= $html_row_start;
            $html .= '<div class="form-check form-check-inline">'."\n";
            $html .= html_writer::label(get_string('allowposspecial', 'report_forumkeywords'), 'checkboxposspecial', false, array('class' => 'form-check-label'))." \n";
            $html .= $html_pos_special;
            $html .= '</div>'."\n";
        $html .= $html_row_end;
        // Filter words
        $html .= $html_row_start;
            $html .= '<div class="form-inline">'."\n";
            $html .= html_writer::label(get_string('filterwords', 'report_forumkeywords'), 'textfilterwords')." \n";
            $html .= '<input type="text" id="filterwords" name="filterwords" value="'.$words.'" />';
            $html .= '</div>'."\n";
        $html .= $html_row_end;
        $html .= $html_row_start;
            $html .= '<input type="submit" value="'.get_string('extractkeywords', 'report_forumkeywords').'" class="btn btn-primary"/>';
        $html .= $html_row_end;
    $html .= "</form>\n";
    
    return $html;
}

// Get detail of a forum
function report_forumkeywords_get_forum_detail($fid) {
    global $DB;
    
    $forum = $DB->get_record('forum', array('id'=>$fid));
    
    return $forum;
}

// Get all posts in a given forum
function report_forumkeywords_get_forum_posts($fid) {
    global $DB;

    $forum_messages = $DB->get_records_sql('
        SELECT p.id, p.userid, p.message, p.messageformat 
        FROM {forum_posts} p 
        WHERE p.discussion IN 
            (SELECT d.id FROM {forum_discussions} d WHERE d.forum = ?) AND p.deleted = 0
        ', array($fid));
    return $forum_messages;
}
