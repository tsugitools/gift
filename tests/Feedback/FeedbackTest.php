<?php

require_once "parse.php";
require_once "..\..\\vendor\\tsugi\lib\src\Util\Mersenne_Twister.php";

class FeedbackTest extends PHPUnit_Framework_TestCase
{
  public function testQuestionWithFeedback() {
    // Check a valid string
    $gift = "::Q1 T/F:: 1+1=2 {T#You got it wrong.#You got it.}\n
    ::Q2 MA:: One of these are right and three are wrong {=Right#Correct ~Wrong#nope ~Incorrect#opposite ~Not right#single word}\n
    ::Q3 MA:: Two of these are right and two are wrong {=Right#correct =Correct#corretc ~Wrong#not this one ~Incorrect#nope}\n
    ::Q4 Short Answer:: Two plus [_____] equals four. {=two#yes, written =2#the numeral, ~4#close}";
    // $gift = "::Q3 MA:: Two of these are right and two are wrong {=Right#correct =Correct ~Wrong ~Incorrect}";

    $questions = array();
    $errors = array();
    parse_gift($gift, $questions, $errors);
    $this->assertEquals($questions[0]->parsed_answer[0][2], "You got it wrong.");
    $this->assertEquals($questions[0]->parsed_answer[1][2], "You got it.");

    // Check a string without properly formed feedback
    $gift = "::Q1 T/F:: 1+1=2 {T#You got it.}";
    $questions = array();
    $errors = array();
    parse_gift($gift, $questions, $errors);
    // we should get an error indicating that there was an issue with the feedback...
    $this->assertTrue(strpos($errors[0], "malformed True/False feedback") === 0);

    // but the rest of the question should still parse
    $this->assertEquals($questions[0]->question, "1+1=2");
    $this->assertEquals($questions[0]->type, "true_false_question");
  }
}
