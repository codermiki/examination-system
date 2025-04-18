<html>

<head>
  <link rel="stylesheet" href="../assets/css/manage_exam.css" />
</head>

<body>
  <!-- Manage Exam Page -->
  <div class="page active" id="manageExamPage">
    <div class="container">
      <!-- Exam Information -->
      <div class="card">
        <h3>Exam Information</h3>
        <label>Course</label>
        <select>
          <option></option>
          <option value=""></option>
          <option value=""></option>
        </select>

        <label>Exam Title</label>
        <input type="text" value="" />

        <label>Exam Description</label>
        <input type="text" value="" />

        <label>Exam Time Limit</label>
        <select>
          <option>10 Minutes</option>
        </select>

        <label>Display Limit</label>
        <input type="number" value="3" />

        <button class="btn">Update</button>
      </div>

      <!-- Exam Questions -->
      <div class="card">
        <h3>Exam Questions</h3>
        <div class="question-box">
          <strong>1.) 2+2=?</strong>
          <div>A = 3</div>
          <div>B = 6</div>
          <div>C = 7</div>
          <div>D = 4</div>
          <div class="question-actions">
            <button class="update-btn" id="updateBtn">Update</button>
            <button class="delete-btn">Delete</button>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Update Question Page -->
  <div class="page" id="updateQuestionPage">
    <div class="card update-card">
      <h3>Update Question</h3>
      <label>Question</label>
      <textarea rows="2">2+2=?</textarea>

      <label>Choice A</label>
      <input type="text" value="3" />

      <label>Choice B</label>
      <input type="text" value="6" />

      <label>Choice C</label>
      <input type="text" value="7" />

      <label>Choice D</label>
      <input type="text" value="4" />

      <label class="correct-label">Correct Answer</label>
      <input type="text" value="D" />

      <button class="btn" id="backBtn">Update Now</button>
    </div>
  </div>

  <script>
    document.addEventListener("DOMContentLoaded", function () {
      const updateBtn = document.getElementById("updateBtn");
      const backBtn = document.getElementById("backBtn");

      const managePage = document.getElementById("manageExamPage");
      const updatePage = document.getElementById("updateQuestionPage");

      updateBtn.addEventListener("click", () => {
        managePage.classList.remove("active");
        updatePage.classList.add("active");
      });

      backBtn.addEventListener("click", () => {
        updatePage.classList.remove("active");
        managePage.classList.add("active");
      });
    });

  </script>
</body>

</html>