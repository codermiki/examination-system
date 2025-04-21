document.addEventListener("DOMContentLoaded", function () {
  const modal = document.getElementById("updateModal");
  const closeBtn = document.querySelector(".close");
  const form = document.getElementById("updateForm");
  const updateButtons = document.querySelectorAll(".update-btn");

  const fields = [
    "fullname",
    "gender",
    "birthdate",
    "course",
    "yearLevel",
    "email",
    "password",
    "status",
  ];

  updateButtons.forEach((btn) => {
    btn.addEventListener("click", () => {
      const row = btn.closest("tr").children;
      document.getElementById("modalName").textContent = row[0].textContent;

      fields.forEach((field, index) => {
        document.getElementById(field).value = row[index].textContent.trim();
      });

      modal.style.display = "block";
    });
  });

  closeBtn.onclick = () => {
    modal.style.display = "none";
  };

  window.onclick = (e) => {
    if (e.target == modal) modal.style.display = "none";
  };

  form.onsubmit = (e) => {
    e.preventDefault();
    alert("Updated successfully (simulate backend here)");
    modal.style.display = "none";
  };
});
