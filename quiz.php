<?php
require_once "../config.php";
require_once "parse.php";

use \Tsugi\Util\U;
use \Tsugi\Core\LTIX;
use \Tsugi\UI\Output;

$LAUNCH = LTIX::requireData();
$gift = $LINK->getJson();

Output::headerJson();

// Check if we got any GIFT
if ( $gift === false || U::strlen($gift) < 1 ) {
    echo ( json_encode(array("status" => "failure", "message" => "This quiz has not yet been configured")));
    return;
}

// parse the GIFT questions
$questions = array();
$errors = array();
parse_gift($gift, $questions, $errors);

// Both reduce the visible bits and score the quiz if a submission is present
$submit = isset($_SESSION['gift_submit']) ? $_SESSION['gift_submit'] : array();
$seed = $USER->id+$LINK->id+$CONTEXT->id;
$retval = make_quiz($submit, $questions, $errors, $seed);

echo(json_encode($retval));
