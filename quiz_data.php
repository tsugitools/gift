<?php
require_once "../config.php";
require_once "parse.php";

require_once "strlen.php";

header("Content-type: application/json; charset=utf-8");

use \Tsugi\Core\LTIX;

$LTI = LTIX::session_start();

// Load the quiz
$gift = $LINK->getJson();

$questions = false;
$errors = array("No questions found");
if ( U__strlen($gift) > 0 ) {
    $questions = array();
    $errors = array();
    parse_gift($gift, $questions, $errors);
}

echo json_encode($questions, JSON_PRETTY_PRINT);
