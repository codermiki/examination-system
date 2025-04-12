let loginForm = document.getElementById("loginForm");


function formHandler(e) {
   e.preventDefault();
   let error = "";

   let email = document.getElementsByName("email")[0].value;
   let password = document.getElementsByName("email")[0].value;


   if (isEmpty(email) || isEmpty(password)) {
      error = "Please fill required field";
   } else if (!isValidPass(password)) {
      error = "password must be at least 8 character";
   }

   if (error) {
      console.log(error);
   } else {
      console.log(" valid input ");
   }
}

function isEmpty(str) {
   return str.length === 0;
}

function isValidPass(pass) {
   if (pass.length < 8) {
      return false;
   }
   return true;
}

loginForm.addEventListener("submit", formHandler);
