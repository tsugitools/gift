// Set the cursor focus on the last text box in the given div
// Used when the user presses the "+" button to add a new answer option
function set_focus_on_lastinput(sender) {
  $(sender + " div:last-child input[type=text]").focus();
}
