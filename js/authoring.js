// Add a new question when the dropdown is changed
$("#question_type_select").change(function() {
  var selected_value = $("#question_type_select").val();
  if (selected_value != "") { // As long as the selected value isn't the placeholder
    // Create a new context for the templates
    var context = {};
    context.count = ++question_number;
    context.type = selected_value;
    addQuestion(context);
    $("#question_type_select").val(""); // reset the dropdown
  }
});

// Add a question to the form with the given context
function addQuestion(context) {
  $('#quiz_content').append(tsugiHandlebarsRender('common', context))
  switch (context.type) {
    case "true_false_question": addTrueFalse(context); break;
    case "multiple_choice_question": addMultipleChoice(context); break;
    case "multiple_answers_question": addMultipleChoice(context); break;
    case "short_answer_question": addShortAnswer(context); break;
    default: console.log("unrecognized question type: " + context.type);
  }
  lti_frameResize();
}

// Add a True/False question to the form. If the context has an answer, fill it out
function addTrueFalse(context) {
  if (context.answer == "T") {
    context.answer_true = true;
  } else if (context.answer == "F") {
    context.answer_false = true;
  }
  $('#content_question'+context.count).append(tsugiHandlebarsRender('tf_authoring', context))
}

// Add a Multiple Choice/Multiple Answer Question to the form. If there are answers in the context, add them
function addMultipleChoice(context) {
  if ("parsed_answer" in context) {
    for (var a=0; a<context.parsed_answer.length; a++) {
      var answer_context = {};
      answer_context.isCorrect = context.parsed_answer[a][0];
      answer_context.value = context.parsed_answer[a][1];
      answer_context.count = context.count;
      answer_context.num = a + 1;
      $('#content_question'+context.count).append(tsugiHandlebarsRender('mc_authoring', context))
    }
    var answer_number = context.parsed_answer.length
  } else {
    var answer_number = 1;
  }
  // Always add one empty answer field
  context.num = answer_number;
  $('#content_question'+context.count).append(tsugiHandlebarsRender('mc_authoring', context))
}

// Add a Short Answer Question to the form. If there are answers in the context, add them
function addShortAnswer(context) {
  if ("parsed_answer" in context) {
    for (var a=0; a<context.parsed_answer.length; a++) {
      var answer_context = {};
      answer_context.value = context.parsed_answer[a][1];
      answer_context.count = context.count;
      answer_context.num = a + 1;
      $('#content_question'+context.count).append(tsugiHandlebarsRender('sa_authoring', context))
    }
    var answer_number = context.parsed_answer.length
  } else {
    var answer_number = 1;
  }
  // Always add one empty answer field
  context.num = answer_number;
  $('#content_question'+context.count).append(tsugiHandlebarsRender('sa_authoring', context))
}

// In the event a question is deleted, run through the form and re-number all of the items
function renumber_questions() {
  var question_headers = $("h1");
  for (var i=0;i<question_headers.length;i++) {
    $(question_headers[i]).html("Question " + (i+1));
  }
}
