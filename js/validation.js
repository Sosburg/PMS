const form = document.getElementById('signup-form');
const errorMessage = document.getElementById('error-message');
const emailInput = document.getElementById('email');
const emailError = document.getElementById('email-error');

form.addEventListener('submit', (event) => {
    let isValid = true;

    // Validate each field
    if (!validateFirstName()) isValid = false;
    if (!validateLastName()) isValid = false;
    if (!validateUsername()) isValid = false;
    if (!validateEmail()) isValid = false;
    if (!validatePassword()) isValid = false;
    if (!validateConfirmPassword()) isValid = false;

    // If any field is invalid, prevent form submission
    if (!isValid) {
        event.preventDefault();
        errorMessage.textContent = 'Please fill in all fields correctly.';
    } else {
        errorMessage.textContent = '';
    }
});

emailInput.addEventListener('input', validateEmail);
emailInput.addEventListener('blur', validateEmail);

function validateEmail() {
    const emailValue = emailInput.value.trim();
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    if (!emailRegex.test(emailValue)) {
        emailError.style.display = 'inline';
        emailError.textContent = 'Please enter a valid email address';
        emailInput.classList.remove('valid');
        return false;
    } else {
        emailError.style.display = 'none';
        emailError.textContent = '';
        emailInput.classList.add('valid');
        return true;
    }
}

// Add similar validation functions for other fields
const firstNameInput = document.getElementById('first-name');
const lastNameInput = document.getElementById('last-name');
const usernameInput = document.getElementById('username');
const passwordInput = document.getElementById('password');
const confirmPasswordInput = document.getElementById('confirm-password');

firstNameInput.addEventListener('input', validateFirstName);
firstNameInput.addEventListener('blur', validateFirstName);

lastNameInput.addEventListener('input', validateLastName);
lastNameInput.addEventListener('blur', validateLastName);

usernameInput.addEventListener('input', validateUsername);
usernameInput.addEventListener('blur', validateUsername);

passwordInput.addEventListener('input', validatePassword);
passwordInput.addEventListener('blur', validatePassword);

confirmPasswordInput.addEventListener('input', validateConfirmPassword);
confirmPasswordInput.addEventListener('blur', validateConfirmPassword);

function validateFirstName() {
    const firstNameValue = firstNameInput.value.trim();
    const firstNameError = document.getElementById('first-name-error');
    const firstNameRegex = /^[A-Za-z]+$/;

    if (!firstNameRegex.test(firstNameValue)) {
        firstNameError.style.display = 'inline';
        firstNameError.textContent = 'First name should only contain letters.';
        firstNameInput.classList.remove('valid');
        return false;
    } else {
        firstNameError.style.display = 'none';
        firstNameError.textContent = '';
        firstNameInput.classList.add('valid');
        return true;
    }
}

function validateLastName() {
    const lastNameValue = lastNameInput.value.trim();
    const lastNameError = document.getElementById('last-name-error');
    const lastNameRegex = /^[A-Za-z]+$/;

    if (!lastNameRegex.test(lastNameValue)) {
        lastNameError.style.display = 'inline';
        lastNameError.textContent = 'Last name should only contain letters.';
        lastNameInput.classList.remove('valid');
        return false;
    } else {
        lastNameError.style.display = 'none';
        lastNameError.textContent = '';
        lastNameInput.classList.add('valid');
        return true;
    }
}

function validateUsername() {
    const usernameValue = usernameInput.value.trim();
    const usernameError = document.getElementById('username-error');
    const usernameRegex = /^[A-Za-z0-9]+$/;

    if (!usernameRegex.test(usernameValue)) {
        usernameError.style.display = 'inline';
        usernameError.textContent = 'Username should only contain letters and numbers.';
        usernameInput.classList.remove('valid');
        return false;
    } else {
        usernameError.style.display = 'none';
        usernameError.textContent = '';
        usernameInput.classList.add('valid');
        return true;
    }
}

function validatePassword() {
    const passwordValue = passwordInput.value.trim();
    const passwordError = document.getElementById('password-error');

    if (passwordValue.length < 8) {
        passwordError.style.display = 'inline';
        passwordError.textContent = 'Password should be at least 8 characters long.';
        passwordInput.classList.remove('valid');
        return false;
    } else {
        passwordError.style.display = 'none';
        passwordError.textContent = '';
        passwordInput.classList.add('valid');
        return true;
    }
}

function validateConfirmPassword() {
    const confirmPasswordValue = confirmPasswordInput.value.trim();
    const passwordValue = passwordInput.value.trim();
    const confirmPasswordError = document.getElementById('confirm-password-error');

    if (confirmPasswordValue !== passwordValue) {
        confirmPasswordError.style.display = 'inline';
        confirmPasswordError.textContent = 'Passwords do not match.';
        confirmPasswordInput.classList.remove('valid');
        return false;
    } else {
        confirmPasswordError.style.display = 'none';
        confirmPasswordError.textContent = '';
        confirmPasswordInput.classList.add('valid');
        return true;
    }
}

function togglePasswordVisibility() {
    const passwordInput = document.getElementById('password');
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
    } else {
        passwordInput.type = 'password';
    }
}
