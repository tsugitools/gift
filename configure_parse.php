<?php
function parse_configure_post() {
  $questions = array();
  $q_num = 1;
  $question_details = array("Not Null");
  while ($question_details != Null) {
    $question_details = get_question($q_num);
    if ($question_details != Null) {
      array_push($questions, create_gift_format($question_details));
    }
    $q_num++;
  }
  return implode("\n\n", $questions);
}

function get_question($num) {
  $question_details = array('answer'=>array());
  foreach ($_POST as $key => $value) {
    if ((strpos($key, "question".$num) > 0) &&($value != Null)) {
      // Trim off the "_questionX" part of the key and make that the key name
      $key_name = implode("_", explode("_", $key, -1));
      // Is this an answer option?
      if (strpos($key_name, "answer") !== false) {
        // Get the number for this answer from the key name (format "answerX" or "answerX_iscorrect")
        $key_parts = explode("_", $key_name);
        $answer_index = substr($key_parts[0], -1);
        // is this the text of the answer or an indicator that this is the correct answer?
        if (sizeof($key_parts) > 1 && $key_parts[1] == 'iscorrect') {
          $question_details['answer'][$answer_index]['iscorrect'] = true;
        } else {
          $question_details['answer'][$answer_index] = array('text'=>$value, 'iscorrect'=>false);
        }
      } else {
        // It's not an answer option, so jus save the k-v pair
        $question_details[$key_name] = $value;
      }
    }
  }

  if (sizeof($question_details) > 1) {
    return $question_details;
  } else {
    return Null;
  }
}

function create_gift_format($question) {
  $answers = Null;
  if ($question['type'] == "true_false_question") {
    $answers = (($question['answer'][1]['text'] == 'true') ? "T" : "F");
  } elseif (($question['type'] == "multiple_choice_question") || ($question['type'] == "multiple_answers_question")) {
    $answers = array();
    foreach ($question['answer'] as $answer) {
      if ($answer['iscorrect']) {
        array_push($answers, "={$answer['text']}");
      } else {
        array_push($answers, "~{$answer['text']}");
      }
    }
    $answers= implode(" ", $answers);
  } elseif ($question['type'] == "short_answer_question") {
    $answers = array();
    foreach ($question['answer'] as $answer) {
      array_push($answers, "={$answer['text']}");
    }
    $answers= implode(" ", $answers);
  }
  return "::{$question['title']}:: {$question['text']} {{$answers}}";
}
