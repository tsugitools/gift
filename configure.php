<?php
require_once "../config.php";
require_once "parse.php";
require_once "configure_parse.php";

use \Tsugi\Core\Cache;
use \Tsugi\Core\LTIX;

// Sanity checks
$LAUNCH = LTIX::requireData();
if ( ! $USER->instructor ) die("Requires instructor role");

// Model
$p = $CFG->dbprefix;

// check to see if there are results from this link already
$results_rows = $PDOX->allRowsDie("SELECT result_id, R.link_id AS link_id, R.user_id AS user_id, M.role as role,
                sourcedid, service_id, grade, note, R.json AS json, R.note AS note
            FROM lti_result AS R
            JOIN lti_link AS L ON L.link_id = R.link_id AND R.link_id = :LI
            JOIN lti_context AS C ON L.context_id = C.context_id AND C.context_id = :CI
            JOIN lti_membership AS M ON R.user_id = M.user_id AND C.context_id = M.context_id
            WHERE L.link_id = :LI AND M.role = 0 AND R.json IS NOT NULL",
            array(':LI'=>$LINK->id, ':CI'=>$CONTEXT->id));

if (!empty($_POST)) {

  $gift = parse_configure_post();

  // Sanity check
  $retval = check_gift($gift);
  if ( ! $retval ) {
      header( 'Location: '.addSession('configure.php') ) ;
      return;
  }

  $LINK->setJson($gift);
  $_SESSION['success'] = 'Quiz updated';
  if ($_POST['save_quiz'] == "Save and Return") {
    header( 'Location: '.addSession('index.php') ) ;
  } else {
    header( 'Location: '.addSession('configure.php') ) ;
  }
  return;
}

// View
$OUTPUT->header();
?>
<link rel="stylesheet" type="text/css" href="css/authoring.css">
<?php
$OUTPUT->bodyStart();
$OUTPUT->topNav();
echo('<div class="right">');
echo('<a href="index.php" class="btn btn-default">Cancel</a> ');
echo('<a href="old_configure.php" class="btn btn-default">Input GIFT Quiz Format</a> ');
echo('</div>');
$OUTPUT->flashMessages();
?>
<form method="post">
<?php
// If we found results rows that weren't empty earlier, show a warning and disable the entire form
if ($results_rows) {
?>
  <div id="warning_for_edit_with_results" class="error-list warning">
    <p>WARNING: Results have already been recorded for this quiz - are you sure you want to make changes?</p>
    <div>
      <input type="button" class="btn btn-danger" id="confirm_edit_with_results" value="Yes, I want to make changes...">
    </div>
  </div>
  <fieldset disabled="disabled">
<?php
} else {
  // We didn't find any results, so just make the form normally
?>
  <fieldset>
<?php
}
?>
    <div id="quiz_content"></div>
    <div id="validation-error-list" class="error-list warning" style="display:none"></div>
    <div class="quiz-controls">
      <select class="form-control question-type-select" id="question_type_select">
        <option value=""> -- Add a New Question -- </option>
        <option value="true_false_question">True/False Question</option>
        <option value="multiple_choice_question">Multiple Choice/Multiple Answer Question</option>
        <option value="short_answer_question">Short Answer Question</option>
      </select>
      <div class="save-buttons">
        <input type="submit" class="btn btn-default" name="save_quiz" value="Save">
        <input type="submit" class="btn btn-default" name="save_quiz" value="Save and Return">
      </div>
      <input type=submit name=doCancel class="btn btn-default" onclick="location='<?php echo(addSession('index.php'));?>'; return false;" value="Cancel"></p>
      <input type=submit name=view onclick="location='<?php echo(addSession('quiz_data.php'));?>'; return false;" value="View JSON"></p>
    </div>
  </fieldset>
</form>

<?php
$OUTPUT->footerStart();
$OUTPUT->templateInclude(array('common', 'tf_authoring', 'mc_authoring', 'sa_authoring'));
?>
<script type="text/javascript" src="js/authoring.js"></script>
<script type="text/javascript" src="js/validation.js"></script>
<script>
$(document).ready(()=> {
  // see if there's already a quiz saved in the JSON
  $.getJSON("<?= addSession('quiz_data.php') ?>", function(quizData) {
    if (!quizData) {
      console.log("No quiz is configured");
    } else {
      for (var q=0; q<quizData.length;q++) {
        var context = quizData[q];
        // decode htmlentities - from https://stackoverflow.com/a/10715834
        context.question = $('<textarea/>').html(context.question).text();
        context.count = $("#quiz_content").children().length+1;
        addQuestion(context);
      }
    }
  });

  $(".save-buttons").mouseenter(function() {
    validate_quiz();
  })

  $("#quiz_content").change(function() {
    validate_quiz();
  });

  // In the event the confirmation div appears at the top of the form
  // Pressing the button will hide the div and enable the form
  $("#confirm_edit_with_results").click(function() {
    $("#warning_for_edit_with_results").hide();
    $("fieldset").removeAttr("disabled");
  });
})
</script>
<?php
$OUTPUT->footerEnd();
