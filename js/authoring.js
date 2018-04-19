function addQuestion(context) {
  tsugiHandlebarsToDiv_noEmpty('quiz_content', 'common', context);
  switch (context.type) {
    case "true_false_question": addTrueFalse(context); break;
    case "multiple_choice_question": addMultipleChoice(context); break;
    case "multiple_answers_question": addMultipleChoice(context); break;
    case "short_answer_question": addShortAnswer(context); break;
    default: console.log("unrecognized question type: " + context.type);
  }
  lti_frameResize();
}

function addTrueFalse(context) {
  if (context.answer == "T") {
    context.answer_true = true;
  } else if (context.answer == "F") {
    context.answer_false = true;
  }
  tsugiHandlebarsToDiv_noEmpty("content_question"+context.count, 'tf_authoring', context);
}

function addMultipleChoice(context) {
  if ("parsed_answer" in context) {
    for (var a=0; a<context.parsed_answer.length; a++) {
      var answer_context = {};
      answer_context.isCorrect = context.parsed_answer[a][0];
      answer_context.value = context.parsed_answer[a][1];
      answer_context.count = context.count;
      answer_context.num = a + 1;
      tsugiHandlebarsToDiv_noEmpty("content_question"+context.count, 'mc_authoring', answer_context);
    }
  }
  // Always add one empty answer field
  tsugiHandlebarsToDiv_noEmpty("content_question"+context.count, 'mc_authoring', null);
}

function addShortAnswer(context) {
  if ("parsed_answer" in context) {
    for (var a=0; a<context.parsed_answer.length; a++) {
      var answer_context = {};
      answer_context.value = context.parsed_answer[a][1];
      answer_context.count = context.count;
      answer_context.num = a + 1;
      tsugiHandlebarsToDiv_noEmpty("content_question"+context.count, 'sa_authoring', answer_context);
    }
  }
  // Always add one empty answer field
  tsugiHandlebarsToDiv_noEmpty("content_question"+context.count, 'sa_authoring', null);
}

$("#question_type_select").change(function() {
  var selected_value = $("#question_type_select").val();
  if (selected_value != "") {
    var context = {};
    context.count = ++question_number;
    context.type = selected_value;
    addQuestion(context);
    $("#question_type_select").val("");
  }
});

function tsugiHandlebarsToDiv_noEmpty(div, name, context) {
  $('#'+div).append(tsugiHandlebarsRender(name, context));
}

function renumber_questions() {
  var question_headers = $("h1");
  for (var i=0;i<question_headers.length;i++) {
    $(question_headers[i]).html("Question " + (i+1));
  }
}
