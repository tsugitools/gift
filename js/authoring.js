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
  addAnswer('#content_question'+context.count, 'tf_authoring', context)
}

// Add a Multiple Choice/Multiple Answer Question to the form. If there are answers in the context, add them
function addMultipleChoice(context) {
  if ("parsed_answer" in context) {
    for (var a=0; a<context.parsed_answer.length; a++) {
      var answer_context = {};
      answer_context.isCorrect = context.parsed_answer[a][0];
      answer_context.value = context.parsed_answer[a][1];
      addAnswer('#content_question'+context.count, 'mc_authoring', answer_context)
    }
    var answer_number = context.parsed_answer.length
  } else {
    var answer_number = 1;
  }
  // Always add one empty answer field
  context.num = ++answer_number;
  addAnswer('#content_question'+context.count, 'mc_authoring', context)
}

// Add a Short Answer Question to the form. If there are answers in the context, add them
function addShortAnswer(context) {
  if ("parsed_answer" in context) {
    for (var a=0; a<context.parsed_answer.length; a++) {
      var answer_context = {};
      answer_context.value = context.parsed_answer[a][1];
      addAnswer('#content_question'+context.count, 'sa_authoring', answer_context)
    }
    var answer_number = context.parsed_answer.length
  } else {
    var answer_number = 1;
  }
  // Always add one empty answer field
  context.num = ++answer_number;
  addAnswer('#content_question'+context.count, 'sa_authoring', context)
}

// Adds an answer option to a given div
// requires a div ID, a template
// optional object answer_context (only for loading quizes)
function addAnswer(div, template_name, answer_context={}) {
  answer_context.num = $(div).children().length + 1;
  answer_context.count = div.split("question")[1];
  $(div).append(tsugiHandlebarsRender(template_name, answer_context))
}

function repurposeButton(btn_id) {
  // TODO: This feels really gross and messy - look at it again later
  // get the numbers for the answer and question by parsing the button id
  var answer_num = btn_id.split('_')[2];
  answer_num = answer_num.charAt(answer_num.length-1);
  var question_num = btn_id.split('_')[3];
  question_num = question_num.charAt(question_num.length-1);

  // re-assign the on-click value of the button and change the value it displays
  $("#"+btn_id).attr("onclick",
    "$('#mc_possible_answer"+answer_num+"_question"+question_num+"').remove(); renumber_answers("+question_num+");"
  );
  $("#"+btn_id).val("-");
}

function renumber_answers(question_number) {
  var answers = $("#content_question"+question_number).children();
  for (var i = 0; i < answers.length; i++) {
    // get the number that this answer currently has
    var to_replace = answers[i].id.split('_')[2];
    // var to_replace = getAnswerNumberFromString(answers[i].id);
    // update the id of the div for this with the new answer
    answers[i].id = answers[i].id.replace(to_replace, "answer" + (i+1));
    // update the entirety of the html for this div with the new answer
    var html = $(answers[i]).html();
    var new_html = html.replace(new RegExp(to_replace, 'g'), "answer" + (i+1));
    $(answers[i]).html(new_html);
  }
}

// In the event a question is deleted, run through the form and re-number all of the items
function renumber_questions() {
  var question_headers = $("h1");
  for (var i=0;i<question_headers.length;i++) {
    $(question_headers[i]).html("Question " + (i+1));
  }
}
