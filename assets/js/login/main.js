document.addEventListener("DOMContentLoaded", () => {
   const loginForm = document.getElementById("login_form");
   const resetForm = document.getElementById("reset_form");
   const forgotLink = document.getElementById("forgot_link");
   const signinLink = document.getElementById("signin_link");

   function toggleForms(showForm, hideForm) {
      hideForm.style.opacity = "1";
      hideForm.style.transform = "translateX(0)";

      // Animate hiding
      hideForm.style.transition =
         "opacity 0.3s ease-out, transform 0.3s ease-out";
      hideForm.style.opacity = "0";
      hideForm.style.transform = "translateX(-20px)";

      setTimeout(() => {
         hideForm.classList.add("d-none"); // Hide after animation
         showForm.classList.remove("d-none");

         // Animate showing
         showForm.style.opacity = "0";
         showForm.style.transform = "translateX(20px)";
         setTimeout(() => {
            showForm.style.transition =
               "opacity 0.3s ease-out, transform 0.3s ease-out";
            showForm.style.opacity = "1";
            showForm.style.transform = "translateX(0)";
         }, 10);
      }, 300);
   }

   forgotLink.addEventListener("click", () => {
      toggleForms(resetForm, loginForm);
   });

   signinLink.addEventListener("click", () => {
      toggleForms(loginForm, resetForm);
   });
});
