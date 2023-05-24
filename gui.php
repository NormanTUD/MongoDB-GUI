<?php
	define('INCLUDED_FROM_INDEX', true);
	include("functions.php");
?>
<!DOCTYPE html>
<html>
<head>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }

        label {
            display: block;
            margin-top: 10px;
        }

        input[type="text"],
        select {
            width: 300px;
        }

        .button-container {
            margin-top: 20px;
        }

        .button-container button {
            margin-right: 10px;
        }

        #question-groups {
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <h1>Questionnaire Editor</h1>
    <h2>Question Groups</h2>
    <div id="question-groups">
        <!-- Question Groups will be dynamically added here -->
    </div>

    <button id="add-group-btn">Add Question Group</button>

    <script>
        var questions = <?php echo json_encode($questions); ?>;

        function renderQuestionGroups() {
            var questionGroupsContainer = document.getElementById('question-groups');
            questionGroupsContainer.innerHTML = '';

            questions.forEach(function (group, groupIndex) {
                var groupDiv = document.createElement('div');
                groupDiv.classList.add('group');

                var groupNameLabel = document.createElement('label');
                groupNameLabel.textContent = 'Group Name:';
                var groupNameInput = document.createElement('input');
                groupNameInput.type = 'text';
                groupNameInput.value = group.group;
                groupNameInput.setAttribute('data-group-index', groupIndex);
                groupNameInput.addEventListener('change', updateGroupName);

                var questionsContainer = document.createElement('div');
                questionsContainer.classList.add('questions');

                group.questions.forEach(function (question, questionIndex) {
                    var questionDiv = document.createElement('div');
                    questionDiv.classList.add('question');

                    var questionLabel = document.createElement('label');
                    questionLabel.textContent = 'Question:';
                    var questionInput = document.createElement('input');
                    questionInput.type = 'text';
                    questionInput.value = question.question;
                    questionInput.setAttribute('data-group-index', groupIndex);
                    questionInput.setAttribute('data-question-index', questionIndex);
                    questionInput.addEventListener('change', updateQuestionText);

                    var inputTypeLabel = document.createElement('label');
                    inputTypeLabel.textContent = 'Input Type:';
                    var inputTypeSelect = document.createElement('select');
                    inputTypeSelect.setAttribute('data-group-index', groupIndex);
                    inputTypeSelect.setAttribute('data-question-index', questionIndex);
                    inputTypeSelect.addEventListener('change', updateInputType);

                    var inputTypeOptions = ['text', 'number', 'radio', 'checkbox', 'select'];
                    inputTypeOptions.forEach(function (option) {
                        var optionElement = document.createElement('option');
                        optionElement.value = option;
                        optionElement.textContent = option.charAt(0).toUpperCase() + option.slice(1);
                        if (question.input_type === option) {
                            optionElement.selected = true;
                        }
                        inputTypeSelect.appendChild(optionElement);
                    });

                    questionDiv.appendChild(questionLabel);
                    questionDiv.appendChild(questionInput);
                    questionDiv.appendChild(inputTypeLabel);
                    questionDiv.appendChild(inputTypeSelect);

                    if (question.input_type === 'radio' || question.input_type === 'checkbox' || question.input_type === 'select') {
                        var optionsLabel = document.createElement('label');
                        optionsLabel.textContent = 'Options:';
                        var optionsContainer = document.createElement('div');

                        question.options.forEach(function (option, optionIndex) {
                            var optionDiv = document.createElement('div');
                            optionDiv.classList.add('option');

                            var optionInput = document.createElement('input');
                            optionInput.type = 'text';
                            optionInput.value = option;
                            optionInput.setAttribute('data-group-index', groupIndex);
                            optionInput.setAttribute('data-question-index', questionIndex);
                            optionInput.setAttribute('data-option-index', optionIndex);
                            optionInput.addEventListener('change', updateOptionText);

                            var removeOptionBtn = document.createElement('button');
                            removeOptionBtn.textContent = 'Remove Option';
                            removeOptionBtn.setAttribute('data-group-index', groupIndex);
                            removeOptionBtn.setAttribute('data-question-index', questionIndex);
                            removeOptionBtn.setAttribute('data-option-index', optionIndex);
                            removeOptionBtn.addEventListener('click', removeOption);

                            optionDiv.appendChild(optionInput);
                            optionDiv.appendChild(removeOptionBtn);
                            optionsContainer.appendChild(optionDiv);
                        });

                        var addOptionBtn = document.createElement('button');
                        addOptionBtn.textContent = 'Add Option';
                        addOptionBtn.setAttribute('data-group-index', groupIndex);
                        addOptionBtn.setAttribute('data-question-index', questionIndex);
                        addOptionBtn.addEventListener('click', addOption);

                        questionDiv.appendChild(optionsLabel);
                        questionDiv.appendChild(optionsContainer);
                        questionDiv.appendChild(addOptionBtn);
                    }

                    questionsContainer.appendChild(questionDiv);
                });

                var addQuestionBtn = document.createElement('button');
                addQuestionBtn.textContent = 'Add Question';
                addQuestionBtn.setAttribute('data-group-index', groupIndex);
                addQuestionBtn.addEventListener('click', addQuestion);

                var removeGroupBtn = document.createElement('button');
                removeGroupBtn.textContent = 'Remove Group';
                removeGroupBtn.setAttribute('data-group-index', groupIndex);
                removeGroupBtn.addEventListener('click', removeGroup);

                groupDiv.appendChild(groupNameLabel);
                groupDiv.appendChild(groupNameInput);
                groupDiv.appendChild(questionsContainer);
                groupDiv.appendChild(addQuestionBtn);
                groupDiv.appendChild(removeGroupBtn);
                questionGroupsContainer.appendChild(groupDiv);
            });
        }

        function updateGroupName(event) {
            var groupIndex = parseInt(event.target.getAttribute('data-group-index'));
            questions[groupIndex].group = event.target.value;
        }

        function updateQuestionText(event) {
            var groupIndex = parseInt(event.target.getAttribute('data-group-index'));
            var questionIndex = parseInt(event.target.getAttribute('data-question-index'));
            questions[groupIndex].questions[questionIndex].question = event.target.value;
        }

        function updateInputType(event) {
            var groupIndex = parseInt(event.target.getAttribute('data-group-index'));
            var questionIndex = parseInt(event.target.getAttribute('data-question-index'));
            var inputType = event.target.value;
            questions[groupIndex].questions[questionIndex].input_type = inputType;

            if (inputType !== 'radio' && inputType !== 'checkbox' && inputType !== 'select') {
                questions[groupIndex].questions[questionIndex].options = [];
            }

            renderQuestionGroups();
        }

        function updateOptionText(event) {
            var groupIndex = parseInt(event.target.getAttribute('data-group-index'));
            var questionIndex = parseInt(event.target.getAttribute('data-question-index'));
            var optionIndex = parseInt(event.target.getAttribute('data-option-index'));
            questions[groupIndex].questions[questionIndex].options[optionIndex] = event.target.value;
        }

        function addQuestion(event) {
            var groupIndex = parseInt(event.target.getAttribute('data-group-index'));
            questions[groupIndex].questions.push({
                question: '',
                input_type: 'text',
                options: []
            });

            renderQuestionGroups();
        }

        function removeGroup(event) {
            var groupIndex = parseInt(event.target.getAttribute('data-group-index'));
            questions.splice(groupIndex, 1);

            renderQuestionGroups();
        }

        function addOption(event) {
            var groupIndex = parseInt(event.target.getAttribute('data-group-index'));
            var questionIndex = parseInt(event.target.getAttribute('data-question-index'));
            questions[groupIndex].questions[questionIndex].options.push('');

            renderQuestionGroups();
        }

        function removeOption(event) {
            var groupIndex = parseInt(event.target.getAttribute('data-group-index'));
            var questionIndex = parseInt(event.target.getAttribute('data-question-index'));
            var optionIndex = parseInt(event.target.getAttribute('data-option-index'));
            questions[groupIndex].questions[questionIndex].options.splice(optionIndex, 1);

            renderQuestionGroups();
        }

        function addQuestionGroup() {
            questions.push({
                group: '',
                questions: []
            });

            renderQuestionGroups();
        }

        var addGroupBtn = document.getElementById('add-group-btn');
        addGroupBtn.addEventListener('click', addQuestionGroup);

        renderQuestionGroups();
    </script>
</body>
</html>
