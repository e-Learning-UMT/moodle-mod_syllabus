<?php
  
define('CLI_SCRIPT', true);
require('../../../config.php');

/**
    The purpose of this script is to download all of the syllabi in a category
*/

function make_path($newpath) {

    if (!file_exists($newpath)) {
        if (!mkdir ($newpath, 0777, true)) {
            echo "Error making directory $newpath. Exiting\n";
            exit;
        }
    }

}

if ($argc != 3) {
    echo "Requires destination path and category id as command line arguments\n";
    exit;
}

$dest = $argv[1];
make_path($dest);

$catid = $argv[2];

global $CFG, $DB, $USER;

$category = $DB->get_record('course_categories', array('id' => $catid));

if (!$category) {
    echo "Error. Category id $catid does not exist. Exiting.\n";
    exit;
}

$coursecat = \core_course_category::get($category->id);
$courses = $coursecat->get_courses(array('recursive' => true));

$fs = get_file_storage();

foreach ($courses as $course) { 
    $thiscoursecat = \core_course_category::get($course->category);
    $catpath = $thiscoursecat->get_nested_name(false);
    $catpath = preg_replace("/[^A-Za-z0-9\/]/", '', $catpath);

    $newpath = $dest . '/' . $catpath;

    $syllabi = get_all_instances_in_course('syllabus', $course, NULL, true);

    $fn = $course->shortname;
    
    $counter = 1;
    foreach ($syllabi as $syllabus) {
        $modcon = context_module::instance($syllabus->coursemodule);

        $files = $fs->get_area_files($modcon->id, 'mod_syllabus', 'content', 0, 
            'sortorder DESC, id ASC', false);
        
        foreach ($files as $file) {
            $file = reset($files);
    
            $content = $file->get_content();
            //echo $file->get_filename()."\n";  

            make_path($newpath);
            $fn = sprintf("%03d", $counter) . '_' . $fn;
            $fn .= '_' . preg_replace("/[^A-Za-z0-9\.-]/", '', $file->get_filename());
            file_put_contents($newpath . '/' . $fn, $content);
            $counter++;
        }
    
    }



    // If it has a syllabus activity, make the dir
    //make_path($newpath);

}
