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
