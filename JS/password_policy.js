function setupPasswordValidation(passwordId, confirmPasswordId, activateButtonIfPasses) {
    function validatePassword() {
      let password = document.getElementById(passwordId).value;
      let confirmPassword = document.getElementById(confirmPasswordId).value;
      let passwordField = document.getElementById(passwordId);
      let confirmPasswordField = document.getElementById(confirmPasswordId);
  
      let hasLowercase = /[a-z]/.test(password);
      let hasUppercase = /[A-Z]/.test(password);
      let hasNumber = /[0-9]/.test(password);
      let hasSpecial = /[^a-zA-Z0-9]/.test(password);
      let isValidLength = password.length >= 8;
      let criteriaMet = [hasLowercase, hasUppercase, hasNumber, hasSpecial].filter(Boolean).length >= 2;
  
      if (password === confirmPassword && isValidLength && criteriaMet) {
        passwordField.style.borderColor = 'green';
        confirmPasswordField.style.borderColor = 'green';
        document.getElementById(activateButtonIfPasses).disabled = false;
      } else {
        passwordField.style.borderColor = 'red';
        confirmPasswordField.style.borderColor = 'red';
        document.getElementById(activateButtonIfPasses).disabled = true;
      }
    }
  
    document.getElementById(passwordId).addEventListener('input', validatePassword);
    document.getElementById(confirmPasswordId).addEventListener('input', validatePassword);
  }
  
// Implementation:  /////////////////////////////////////////////////////////
//   document.addEventListener('DOMContentLoaded', function() {
//     setupPasswordValidation('passwordfield', 'confirmPasswordfield');
//   });
/////////////////////
  