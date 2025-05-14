<?php
include __DIR__ . "/../../includes/functions/Exam_function.php";

// Check if user ID is available
if (!isset($_SESSION['user_id'])) {
    echo "<div class='full-screen'><h2>❌ User session expired. Please log in again.</h2></div>";
    exit;
}
?>

<?php if ((!isset($_POST['start']) || $_POST['start'] !== "true") && isset($_POST['exam_id'])):
    $exam_id = htmlspecialchars($_POST['exam_id']);
    ?>
    <div class="back">
        <button onclick="window.history.back()">← Back</button>
    </div>

    <div class="full-screen">
        <div class="instructions-box">
            <h1>📖 Exam Instructions</h1>
            <ul>
                <li>Use only one device to complete the exam.</li>
                <li>Do not refresh the page or open new tabs/windows.</li>
                <li>The exam is timed; it will auto-submit when time expires.</li>
                <li>Your webcam may be monitored during the test.</li>
                <li>Do not navigate away from the exam window.</li>
                <li>Read all questions carefully before answering.</li>
                <li>Click "Start Exam" to begin — no going back once started.</li>
            </ul>
            <form action="?page=take_exam" method="post">
                <div class="agreement">
                    <input type="hidden" name="exam_id" value="<?= $exam_id ?>" />
                    <input type="checkbox" id="agree" name="start" value="true" required />
                    <label for="agree">I have read and agree to the exam rules.</label>
                </div>
                <button class="btn-start" type="submit">Start Exam</button>
            </form>
        </div>
    </div>

<?php else:
    $exam_id = $_POST['exam_id'] ?? null;

    $examStartStr = Exam_function::examStart($exam_id);
    $durationMinutes = Exam_function::examDuration($exam_id);

    // $examStartStr = "2025-05-13 15:18:00";
    // $durationMinutes = 90;

    if (!$examStartStr || !$durationMinutes) {
        echo `<div class='full-screen'>
                     <button onclick="window.location.href='/softexam/student'">← Back</button>;
                     <h2>⚠️ Invalid exam configuration.</h2>
              </div>`;
        exit;
    }

    $startTimestamp = new DateTime($examStartStr);
    $endTimestamp = clone $startTimestamp;
    $endTimestamp->modify("+{$durationMinutes} minutes");

    $now = new DateTime();

    if ($now < $startTimestamp):
        $startMS = $startTimestamp->getTimestamp() * 1000;
        ?>
        <div class="full-screen">
            <h2>⏳ Exam hasn't started yet.</h2>
            <p>Time remaining until exam begins:</p>
            <h1 id="countdown">--:--:--</h1>
        </div>

        <script>
            const examStartTime = <?= $startMS ?>;
            function startCountdownToExam() {
                const countdownEl = document.getElementById('countdown');
                const interval = setInterval(() => {
                    const now = Date.now();
                    const remaining = examStartTime - now;
                    if (remaining <= 0) {
                        clearInterval(interval);
                        location.reload(); // Reload page to load exam
                    } else {
                        const hrs = Math.floor(remaining / 3600000);
                        const mins = Math.floor((remaining % 3600000) / 60000);
                        const secs = Math.floor((remaining % 60000) / 1000);
                        countdownEl.innerText = `${hrs.toString().padStart(2, '0')}:${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
                    }
                }, 1000);
            }
            startCountdownToExam();
        </script>

        <?php
        exit;
    endif;

    if ($now > $endTimestamp):
        echo "<div class='full-screen'><h2>⏰ Exam time has passed. You can no longer take this exam.</h2></div>";
        exit;
    endif;

    $endMS = $endTimestamp->getTimestamp() * 1000;
    ?>
    <!-- Main Exam Page -->
    <div class="quiz-container">
        <div class="quiz-header">
            <div class="header-left">
                <p class="subtitle exam-title"></p>
            </div>
            <div class="header-right">
                <div class="timer">Remaining Time: <span id="time">--:--</span></div>
            </div>
        </div>
        <div id="question-area" class="quiz-main">
            <!-- Questions will load here -->
        </div>
    </div>

    <script>
        const user_id = <?= json_encode($_SESSION['user_id']) ?>;
        const exam_id = <?= json_encode($exam_id) ?>;
        const examEndTime = <?= $endMS ?>;

        let currentQuestion = 0;
        let questions = [];

        function startCountdown() {
            const timerEl = document.getElementById('time');
            const interval = setInterval(() => {
                const now = Date.now();
                const remaining = examEndTime - now;
                if (remaining <= 0) {
                    clearInterval(interval);
                    timerEl.innerText = '00:00';
                    autoSubmitExam();
                } else {
                    const mins = Math.floor(remaining / 60000);
                    const secs = Math.floor((remaining % 60000) / 1000);
                    timerEl.innerText = `${mins.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
                }
            }, 1000);
        }

        async function autoSubmitExam() {
            const response = await fetch('/softexam/api/submitExam', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    student_id: user_id,
                    exam_id: exam_id
                })
            });
            const data = await response.json();

            return data;
        }

        async function loadQuestions() {
            document.getElementById('question-area').innerHTML = '<p>Loading questions...</p>';
            try {
                const res = await fetch('/softexam/api/getQuestions', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        student_id: user_id,
                        exam_id
                    })
                });
                const response = await res.json();
                if (response?.error) {
                    document.querySelector(".exam-title").innerHTML = `
                     <button onclick="window.location.href='/softexam/student'">← Back</button>`;
                    document.getElementById('question-area').innerHTML = `
                            <h2>${response?.error}</h2>`;
                }

                if (response?.message) {
                    const data = autoSubmitExam();
                    if (data?.success) {
                        document.querySelector(".exam-title").innerHTML = `
                     <button onclick="window.location.href='/softexam/student'">← Back</button>`;
                        document.getElementById('question-area').innerHTML = `<h2>🎉${response?.message} And ${data?.success}!</h2>`;
                    }
                    else if (data?.error) {
                        document.querySelector(".exam-title").innerHTML = `
                     <button onclick="window.location.href='/softexam/student'">← Back</button>`;
                        document.getElementById('question-area').innerHTML = `<h2>${response?.message} But ${data?.error}</h2>`;
                    }
                }

                if (response?.exam_title) {
                    document.querySelector(".exam-title").textContent = response.exam_title;
                }

                if (response?.questions?.length) {
                    questions = response?.questions;
                    showQuestion();
                }

            } catch (err) {
                document.querySelector(".exam-title").innerHTML = `
                     <button onclick="window.location.href='/softexam/student'">← Back</button>`;
                document.getElementById('question-area').innerHTML = '<p>❌ Failed to load questions.</p>';
            }
        }

        function showQuestion() {
            const question = questions[currentQuestion];
            const container = document.getElementById('question-area');
            const isLast = currentQuestion === questions.length - 1;

            let html = `<form id="question-form">
            <section class="question-block">
                <h2>${currentQuestion + 1}.) ${question.question_text}</h2>
                <div class="options-grid">`;

            switch (question?.question_type) {
                case "multiple_choice":
                    question.options.forEach((opt, i) => {
                        html += `<div class="option">
                        <input type="radio" id="opt${i}" name="answer" value="${opt}" required>
                        <label for="opt${i}">${opt}</label>
                    </div>`;
                    });
                    break;
                case "fill_blank":
                    html += `<div class="option"><input type="text" name="answer" placeholder="write your answer" required></div>`;
                    break;
                case "true_false":
                    html += `<div class="option"><input type="radio" id="true" name="answer" value="True" required><label for="true">True</label></div>
                         <div class="option"><input type="radio" id="false" name="answer" value="False" required><label for="false">False</label></div>`;
                    break;
            }

            html += `</div></section>
            <footer class="quiz-footer">
                <button type="submit" class="submit-btn">${isLast ? 'Submit Exam' : 'Next'}</button>
            </footer>
        </form>`;

            container.innerHTML = html;
            document.getElementById('question-form').addEventListener('submit', submitAnswer);
        }

        async function submitAnswer(e) {
            e.preventDefault();
            const currentQ = questions[currentQuestion];
            let answer = '';
            switch (currentQ.question_type) {
                case "multiple_choice":
                case "true_false":
                    const selected = document.querySelector('input[name="answer"]:checked');
                    if (!selected) return alert("Please select an answer.");
                    answer = selected.value;
                    break;
                case "fill_blank":
                    const input = document.querySelector('input[name="answer"]');
                    if (!input || input.value.trim() === "") return alert("Please enter an answer.");
                    answer = input.value.trim();
                    break;
            }

            const response = await fetch('/softexam/api/postAnswer', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    student_id: user_id,
                    question_id: currentQ.question_id,
                    answer_text: answer,
                    exam_id: exam_id
                })
            });
            const data = await response.json();

            if (data?.success) {
                currentQuestion++;
            }

            if (currentQuestion < questions.length) {
                showQuestion();
            } else {
                const data = await autoSubmitExam();
                if (data?.success) {
                    document.querySelector(".exam-title").innerHTML = `
                      <button onclick="window.location.href='/softexam/student'">← Back</button>`;
                    document.getElementById('question-area').innerHTML = `<h2>🎉 ${data?.success}!</h2>`;
                }
                else if (data?.error) {
                    document.querySelector(".exam-title").innerHTML = `
                      <button onclick="window.location.href='/softexam/student'">← Back</button>`;
                    document.getElementById('question-area').innerHTML = `<h2>${data?.error}</h2>`;
                }
                else {
                    document.querySelector(".exam-title").innerHTML = `
                      <button onclick="window.location.href='/softexam/student'">← Back</button>`;
                    document.getElementById('question-area').innerHTML = `<h2>Exam not submitted</h2>`;
                }
            }
        }

        startCountdown();
        loadQuestions();
    </script>

<?php endif; ?>