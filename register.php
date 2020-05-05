<?php

$REGISTER_LTI2 = array(
"name" => "Quizzes II",
"FontAwesome" => "fa-question-circle",
"short_name" => "QuizzesII",
"description" => "This tool provides a quiz engine that supports the GIFT format.
GIFT is a line-oriented plain text question format that is simple to understand
and easily edited by hand or even stored in a repository like github.",
"messages" => array("launch", "launch_grade"),
 "privacy_level" => "anonymous",  // anonymous, name_only, public
    "license" => "Apache",
    "languages" => array(
        "English"
    ),
    "analytics" => array(
        "internal"
    ),
    "source_url" => "https://github.com/tsugitools/gift",
    // For now Tsugi tools delegate this to /lti/store
    "placements" => array(
        /*
        "course_navigation", "homework_submission",
        "course_home_submission", "editor_button",
        "link_selection", "migration_selection", "resource_selection",
        "tool_configuration", "user_navigation"
        */
    ),
    "screen_shots" => array(
        "store/screen-01.png",
        "store/screen-02.png",
        "store/screen-03.png",
        "store/screen-04.png",
        "store/screen-05.png",
        "store/screen-06.png",
        "store/screen-analytics.png"
    )
);

