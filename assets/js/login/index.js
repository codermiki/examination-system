let show_span = document.getElementsByClassName("show_password")[0];
let icon = document.getElementById("icon");
let input_pass = document.getElementById("password");

let login_form = document.getElementById("login_form");

let reset_form = document.getElementById("reset_form");
let forgot_link = document.getElementById("forgot_link");

let signin_link = document.getElementById("signin_link");

let reset_msg = document.getElementById("reset_msg");
let send_reset = document.getElementById("send_reset");

function show_password() {
   if (icon.classList.contains("fa-eye-slash")) {
      icon.classList.remove("fa-eye-slash");
      icon.classList.add("fa-eye");
      input_pass.setAttribute("type", "text");
   } else {
      icon.classList.remove("fa-eye");
      icon.classList.add("fa-eye-slash");
      input_pass.setAttribute("type", "password");
   }
}

show_span.addEventListener("click", show_password);
