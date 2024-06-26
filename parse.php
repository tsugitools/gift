<?php

// We can't use U::strlen because of a weird interaction between config.php and XML parsing...

use \Tsugi\Util\Mersenne_Twister;

function parse_gift($text, &$questions, &$errors) {
    $raw_questions = array();
    $question = "";
    $lines = explode("\n", $text);
    foreach ( $lines as $line ) {
        $line = rtrim($line);
        // print $line."\n";
        if ( strpos($line, "//") === 0 ) continue;
        if ($line == "" ) {
            if ( strlen($question ?? '') > 0 ) {
                $raw_questions[] = $question;
                $question = "";
            }
            continue;
        }
        if ( strlen($question ?? '') > 0 ) $question .= "\n";
        $question .= $line;
    }

    if ( strlen($question ?? '') > 0 ) {
        $raw_questions[] = $question;
    }

    // var_dump_pre($raw_questions);

    $quesno = 0;
    foreach ( $raw_questions as $raw ) {
        $pieces = explode('::', $raw, 3);
        if ( count($pieces) != 3 ) {
            $errors[] = "Mal-formed question: ".$raw;
            continue;
        }
        // print_r($pieces);
        $name = trim($pieces[1]);
        $text = trim($pieces[2]);
        $spos = false;
        $epos = false;
        $escape = false;
        // echo("<pre>\n");echo("==================\n".$text."\n");echo("</pre>\n");
        // Determine the beginning and end of the question and answer
        // An unescaped open and close curly brace
        for ( $i=0; $i < strlen($text); $i++ ) {
            $ch = $text[$i];
            if ( $escape ) {
                $escape = false;
                continue;
            }

            if ( $ch == '\\' && ! $escape ) {
                $escape = true;
                continue;
            }

            if ( $ch == '{' && $spos === false ) {
                $spos = $i;
            }

            if ( $ch == '}' && $epos === false  && $spos !== false ) {
                $epos = $i;
            }
        }

        if ( $spos === false || $epos === false || $spos >= $epos) {
            $errors[] = "Could not find answer spos=$spos epos=$epos\n".$raw;
            continue;
        }

        $question = trim(substr($text,0,$spos-1));
        $sa_question = trim(substr($text,0,$spos-1)) . " [_____] " . trim(substr($text,$epos+1));
        $answer = trim(substr($text, $spos+1, $epos-$spos-1));

        /// echo("<pre>\n");echo("spos $spos epos $epos\n");echo("== Q ==\n".$question."\n");echo("== A ==\n".$answer."====\n");echo("</pre>\n");

        if ( strpos($answer, "->" ) > 0 ) {
            $type = 'matching_question'; // CHECK THIS
            $errors[] = "Matching questions not yet supported: ".$raw;
            continue;
        } else if ( strpos($answer,"T") === 0 || strpos($answer, "F") === 0 ) {
            $type = 'true_false_question';
        } else if ( strlen($answer ?? '') < 1 ) {
            $type = 'essay_question';
        } else if ( strpos($answer, '#') === 0 ) {
            $type = 'numerical_question';
            $errors[] = "Numerical questions not yet supported: ".$raw;
            continue;
        }  else if ( strpos($answer,"=") === 0 || strpos($answer, "~") === 0 ) {
            $type = 'multiple_choice_question';  // Also will be multiple_answer and short_answer
        } else { 
            $errors[] = "Could not determine question type: ".$raw;
            continue;
        }

        $quesno = $quesno + 1;
        $ansno = 0;
        $answers = array();
        $parsed_answer = false;
        $correct_answers = 0;
        $incorrect_answers = 0;
        // Also will be multiple_answer_question and short_answer_question
        if ( $type == 'multiple_choice_question') {
            $parsed_answer = array();
            $correct = null;
            $answer_text = false;
            $feedback = false;
            $in_feedback = false;

            // Over scan by 1 so we can handle the last entry inside the loop
            // with a middle exit
            for($i=0;$i<strlen($answer)+1; $i++ ) {
                $prevch = $i > 0 ? $answer[$i-1] : ' ';
                $ch = $i < strlen($answer) ? $answer[$i] : -1;

                // echo("<pre>\n$i $ch\n</pre>\n");
                // Finish up the previous entry
                if ( strlen($answer_text ?? '') > 0 && ($ch == -1 || ($prevch != "\\" && ($ch == '=' || $ch == "~" )) ) && strlen($answer_text ?? '') > 0 ) {
                    if ( $correct === null || $answer_text === false ) {
                        $errors[] = "Mal-formed answer sequence: ".$raw;
                        $parsed_answer = array();
                        break;
                    }
                    if ( $correct ) {
                        $correct_answers++;
                    } else {
                        $incorrect_answers++;
                    }
                    $ansno = $ansno + 1;
                    // echo("<pre>BEFORE\n".($correct? "C" : "X")." $answer_text -- $feedback\n</pre>\n");

                    // Escape the answer text - This will go through htmlentities
                    // Note - \n is only in question text, not answers
                    $answer_text = str_replace("\\\\","&#92;", $answer_text);
                    $answer_text = str_replace("\\","", $answer_text);
                    $answer_text = str_replace("&#92;", "\\", $answer_text);
                    $code = substr($quesno.':'.$ansno.':'.md5(trim($answer_text)),0,10);

                    // echo("<pre>\n".($correct? "C" : "X")." $code $answer_text -- $feedback\n</pre>\n");

                    $parsed_answer[] = array($correct, trim($answer_text), trim($feedback), $code);
                    // Set up for the next one
                    $correct = null;
                    $answer_text = false;
                    $feedback = false;
                    $in_feedback = false;
                }

                // We are done...
                if ( $ch == -1 ) break;

                // right or wrong?
                if ( $prevch != "\\" && $ch == '=' ) {
                    $correct = true;
                    continue;
                }
                if ( $prevch != "\\" && $ch == '~' ) {
                    $correct = false;
                    continue;
                }

                // right or wrong?
                if ( $prevch != "\\" && $ch == '#' && $in_feedback === false ) {
                    $in_feedback = true;
                    continue;
                }

                if ( $in_feedback ) {
                    $feedback .= $ch;
                } else {
                    $answer_text .= $ch;
                }

            }
            if ( count($parsed_answer) < 1 ) {
                $errors[] = "Mal-formed answer sequence: ".$raw;
                continue;
            }
            if ( $correct_answers < 1 ) {
                $errors[] = "No correct answers found: ".$raw;
                continue;
            } else if ( $correct_answers == 1 && $incorrect_answers > 0 ) {
                $type = 'multiple_choice_question';
            } else if ( $correct_answers > 1 && $incorrect_answers > 0 ) {
                $type = 'multiple_answers_question';
            } else if ( $correct_answers > 0 && $incorrect_answers == 0 ) {
                $type = 'short_answer_question';
                $question = $sa_question;
            } else {
                $errors[] = "Could not determine question type: ".$raw;
                continue;
            }
        }

        // var_dump_pre($parsed_answer, true);
        // echo "\nN: ",$name,"\nQ: ",$question,"\nA: ",$answer,"\nType:",$type,"\n";
        $qobj = new stdClass();
        $qobj->name = $name;
        if ( strpos($question,'[html]') === 0 ) {
            $question = de_escape(ltrim(substr($question,6)));
            $qobj->html = true;
        } else {
            $question = htmlentities($question);
            $question = str_replace("\\\\","&#92;", $question);
            $question = str_replace("\\n","<br>", $question);
            $question = de_escape($question);
        }
        $qobj->question = $question;
        $qobj->code = $quesno.':'.substr(md5($question),0,9);
        $qobj->answer = $answer;
        $qobj->type = $type;
        $qobj->parsed_answer = $parsed_answer;
        $qobj->correct_answers = $correct_answers;
        $questions[] = $qobj;
    }

    // var_dump_pre($questions, true);
}


function make_quiz($submit, $questions, $errors, $seed=-1) {

    $retval = array("status" => "failure", "errors" => $errors);
    if ( count($questions) < 1 ) {
        $retval["message"] = "No questions found";
        return $retval;
    }

    // Load the gift submission
    $submit = isset($_SESSION['gift_submit']) ? $_SESSION['gift_submit'] : array();
    $doscore = count($submit) > 0;

    $retval['status'] = 'success';
    $retval['scored'] = $doscore;
    $safe = array();
    $count = 1;
    $cumulative_score = 0;
    $cumulative_total = 0;
    $mt = new Mersenne_Twister($seed);
    // Filter out questions for the user-visible stuff
    foreach($questions as $question) {
        $nq = new stdClass();
        if ( ! isset($question->question) ) continue;
        if ( ! isset($question->type) ) continue;
        if ( ! isset($question->code) ) continue;
        $nq->question = $question->question;
        $nq->scored = $doscore;
        $q_code = $question->code;
        $nq->code = $q_code;
        $t = $question->type;
        $nq->type = $t;
        if ( isset($question->name) ) $nq->name = $question->name;

        if ( $t == 'short_answer_question' ) {
            if ( isset($submit[$q_code]) ) {
                $nq->value = $submit[$q_code];
            }
        }

        // Because Handlebars can't tell the difference between not set and false
        if ( $t == 'true_false_question' ) {
            if ( isset($submit[$q_code]) ) {
                $nq->value_true = $submit[$q_code] == 'T';
                $nq->value_false = $submit[$q_code] == 'F';
            }
        }

        // Score the questions that don't have answers
        $score = null;
        $correct = null;
        if ( $doscore && $t == 'short_answer_question' ) {
            if ( isset($submit[$q_code]) ) {
                $nq->value = $submit[$q_code];
                foreach($question->parsed_answer as $answer ) {
                    $ans = preg_replace('/\s+/', '', $answer[1]);
                    $sub = preg_replace('/\s+/', '', $submit[$q_code]);
                    if ( strcasecmp($sub, $ans) == 0 ) {
                        $score = 1;
                        $correct = true;
                        break;
                    }
                }
            }
            if ( $score === null ) {
                $score = 0;
                $correct = false;
            }
        } else if ( $doscore && $t == 'true_false_question' ) {
            if ( isset($submit[$q_code]) ) {
                $ans = substr($question->answer, 0, 1);
                $score = (strtolower($submit[$q_code]) == strtolower($ans) ) ? 1 : 0;
                $correct = ($score == 1);
            } else {
                $score = 0;
                $correct = false;
            }
        } 

        if ( $t == 'multiple_choice_question' &&
                isset($question->parsed_answer) && is_array($question->parsed_answer) ) {
            $answers = array();
            $value = false;
            if ( $doscore ) {
                $score = 0;
                $correct = false;
            }
            if ( isset($submit[$q_code]) ) {
                $value = $submit[$q_code];
                $nq->value = $submit[$q_code];
            }
            foreach($question->parsed_answer as $answer ) {
                $ans = new stdClass();
                if ( ! is_array($answer) ) continue;
                if ( count($answer) != 4 ) continue;

                $expected = $answer[0];  // An actual boolean
                $ans->text = $answer[1];
                $a_code = $answer[3];
                $ans->code = $a_code;
                if ( $value == $a_code ) {
                    $ans->checked = true;
                    if ( $doscore && $expected ) {
                        $correct = true;
                        $score = 1;
                    }
                }
                $answers[] = $ans;
            }
            // $answers = $mt->shuffle($answers);
            $nq->answers = $mt->shuffle($answers);
        } 

        if ( $t == 'multiple_answers_question'  &&
                isset($question->parsed_answer) && is_array($question->parsed_answer) ) {
            $answers = array();
            $got = 0;
            $need = 0;
            $oneanswer = false;
            foreach($question->parsed_answer as $answer ) {
                $ans = new stdClass();
                if ( ! is_array($answer) ) continue;
                if ( count($answer) != 4 ) continue;
                $ans->text = $answer[1];
                $a_code = $answer[3];
                $expected = $answer[0];  // An actual boolean
                $oneanswer = $oneanswer || isset($submit[$a_code]);
                $ans->checked = isset($submit[$a_code]);

                $actual = false;
                if (isset($submit[$a_code])) {  // If the user checked the box for this answer...
                  if ($expected){               // And the answer was supposed to be checked
                    $actual = true;             // Then the user should get a point towards the score
                  }
                } else {                        // If the user did NOT check this box...
                  if (!$expected){              // And the answer was not supposed to be checked
                    $actual = true;             // Then the user should get a point
                  }
                }

                if ( $actual ) $got++;          // $actual is true if the user gave the correct option
                $need++;
                $ans->code = $a_code;
                if ( $doscore ) {
                    $ans->correct = $actual == $expected;
                }
                $answers[] = $ans;
            }
            if ( $doscore ) {
                $correct = $got == $need;
                if ( $correct || $oneanswer ) {
                    $score = $correct + 0;
                } else {
                    $score = 0;
                }
            }
            $nq->answers = $mt->shuffle($answers);
        }

        if ( $correct !== null ) $nq->correct = $correct;
        if ( $score !== null ) {
            $nq->score = $score;
            $cumulative_score += $score;
            $cumulative_total += 1;
            // $nq->cumulative_total = $cumulative_total;
            // $nq->cumulative_score = $cumulative_score;
        }
        $nq->count = $count;
        $count++;
        $safe[] = $nq;
    }

    $retval["questions"] = $safe;
    $retval["submit"] = $submit;
    if ( $doscore ) {
        if ( $cumulative_total == 0 ) {
            $retval["score"] = 0;
        } else {
            $retval["score"] = $cumulative_score / $cumulative_total;
        }
    }

    return $retval;
}

// Some POST sanity checking...
function check_gift($gift) {
    $questions = array();
    $errors = array();
    parse_gift($gift, $questions, $errors);

    if ( count($questions) < 1 ) {
        $_SESSION['error'] = "No valid questions found in input data";
        return false;
    }

    if ( count($errors) > 0 ) {
        $msg = "Errors in GIFT data: ";
        $i = 1;
        foreach ( $errors as $error ) {
            $msg .= " ($i) ".$error;
        }
        $_SESSION['error'] = $msg;
        return false;
    }
    return true;
}

function de_escape($str) {
    $retval = "";
    for ($i = 0; $i < strlen($str); $i++){
        $ch = $str[$i];
        if ( $ch == '\\' && $i < strlen($str)-1) {
            $ch = $str[++$i];
        }
        $retval .= $ch;
    }
    return $retval;
}

