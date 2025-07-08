function togglePassword(inputId = "password") {
  const input = document.getElementById(inputId);
  const button = input.parentElement.querySelector(".toggle-password");
  const icon = button.querySelector("i");

  if (input.type === "password") {
    input.type = "text";
    icon.classList.remove("fa-eye");
    icon.classList.add("fa-eye-slash");
  } else {
    input.type = "password";
    icon.classList.remove("fa-eye-slash");
    icon.classList.add("fa-eye");
  }
}

function validateForm(form) {
  const inputs = form.querySelectorAll("input[required]");
  let isValid = true;

  inputs.forEach((input) => {
    if (!input.value.trim()) {
      showFieldError(input, "Field ini wajib diisi");
      isValid = false;
    } else {
      clearFieldError(input);
    }
  });

  const emailInput = form.querySelector('input[type="email"]');
  if (emailInput && emailInput.value) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(emailInput.value)) {
      showFieldError(emailInput, "Format email tidak valid");
      isValid = false;
    }
  }

  const passwordInput = form.querySelector('input[name="password"]');
  const confirmInput = form.querySelector('input[name="confirm_password"]');

  if (
    passwordInput &&
    confirmInput &&
    passwordInput.value !== confirmInput.value
  ) {
    showFieldError(confirmInput, "Konfirmasi password tidak cocok");
    isValid = false;
  }

  if (passwordInput && passwordInput.value.length < 6) {
    showFieldError(passwordInput, "Password minimal 6 karakter");
    isValid = false;
  }

  return isValid;
}

function showFieldError(input, message) {
  clearFieldError(input);

  input.style.borderColor = "#dc3545";

  const errorDiv = document.createElement("div");
  errorDiv.className = "field-error";
  errorDiv.textContent = message;
  errorDiv.style.cssText = `
    color: #dc3545;
    font-size: 12px;
    margin-top: 5px;
    display: flex;
    align-items: center;
    gap: 5px;
  `;

  const icon = document.createElement("i");
  icon.className = "fas fa-exclamation-circle";
  errorDiv.insertBefore(icon, errorDiv.firstChild);

  input.parentElement.parentElement.appendChild(errorDiv);
}

function clearFieldError(input) {
  input.style.borderColor = "";
  const errorDiv =
    input.parentElement.parentElement.querySelector(".field-error");
  if (errorDiv) {
    errorDiv.remove();
  }
}

function setupRealTimeValidation() {
  const inputs = document.querySelectorAll("input");

  inputs.forEach((input) => {
    input.addEventListener("blur", () => {
      if (input.hasAttribute("required") && !input.value.trim()) {
        showFieldError(input, "Field ini wajib diisi");
      } else {
        clearFieldError(input);
      }

      if (input.type === "email" && input.value) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(input.value)) {
          showFieldError(input, "Format email tidak valid");
        } else {
          clearFieldError(input);
        }
      }

      if (input.name === "confirm_password") {
        const passwordInput = document.querySelector('input[name="password"]');
        if (passwordInput && input.value !== passwordInput.value) {
          showFieldError(input, "Konfirmasi password tidak cocok");
        } else {
          clearFieldError(input);
        }
      }
    });

    input.addEventListener("input", () => {
      if (input.style.borderColor === "rgb(220, 53, 69)") {
        clearFieldError(input);
      }
    });
  });
}

function setupPasswordStrength() {
  const passwordInput = document.querySelector('input[name="password"]');
  if (!passwordInput) return;

  const strengthIndicator = document.createElement("div");
  strengthIndicator.className = "password-strength";
  strengthIndicator.innerHTML = `
    <div class="strength-bar">
      <div class="strength-fill"></div>
    </div>
    <div class="strength-text">Kekuatan password</div>
  `;

  strengthIndicator.style.cssText = `
    margin-top: 8px;
    font-size: 12px;
  `;

  const strengthBar = strengthIndicator.querySelector(".strength-bar");
  strengthBar.style.cssText = `
    height: 4px;
    background-color: #eee;
    border-radius: 2px;
    overflow: hidden;
    margin-bottom: 5px;
  `;

  const strengthFill = strengthIndicator.querySelector(".strength-fill");
  strengthFill.style.cssText = `
    height: 100%;
    width: 0%;
    transition: all 0.3s ease;
    border-radius: 2px;
  `;

  passwordInput.parentElement.parentElement.appendChild(strengthIndicator);

  passwordInput.addEventListener("input", () => {
    const password = passwordInput.value;
    const strength = calculatePasswordStrength(password);

    strengthFill.style.width = strength.percentage + "%";
    strengthFill.style.backgroundColor = strength.color;
    strengthIndicator.querySelector(".strength-text").textContent =
      strength.text;
  });
}

function calculatePasswordStrength(password) {
  let score = 0;

  if (password.length >= 6) score += 20;
  if (password.length >= 10) score += 20;
  if (/[a-z]/.test(password)) score += 20;
  if (/[A-Z]/.test(password)) score += 20;
  if (/[0-9]/.test(password)) score += 10;
  if (/[^A-Za-z0-9]/.test(password)) score += 10;

  if (score < 30) {
    return { percentage: score, color: "#dc3545", text: "Lemah" };
  } else if (score < 60) {
    return { percentage: score, color: "#ffc107", text: "Sedang" };
  } else if (score < 90) {
    return { percentage: score, color: "#28a745", text: "Kuat" };
  } else {
    return { percentage: 100, color: "#28a745", text: "Sangat Kuat" };
  }
}

document.addEventListener("DOMContentLoaded", () => {
  setupRealTimeValidation();
  setupPasswordStrength();

  const forms = document.querySelectorAll(".auth-form");
  forms.forEach((form) => {
    form.addEventListener("submit", (e) => {
      if (!validateForm(form)) {
        e.preventDefault();
        return false;
      }

      const submitBtn = form.querySelector('button[type="submit"]');
      const originalText = submitBtn.innerHTML;
      submitBtn.innerHTML =
        '<i class="fas fa-spinner fa-spin"></i> Memproses...';
      submitBtn.disabled = true;

      setTimeout(() => {
        submitBtn.innerHTML = originalText;
        submitBtn.disabled = false;
      }, 5000);
    });
  });

  const socialBtns = document.querySelectorAll(".btn-social");
  socialBtns.forEach((btn) => {
    btn.addEventListener("click", () => {
      alert("Fitur login sosial akan segera tersedia!");
    });
  });

  const firstInput = document.querySelector("input");
  if (firstInput) {
    firstInput.focus();
  }
});

document.addEventListener("keydown", (e) => {
  if (e.key === "Enter" && e.target.tagName !== "TEXTAREA") {
    const form = e.target.closest("form");
    if (form) {
      const submitBtn = form.querySelector('button[type="submit"]');
      if (submitBtn && !submitBtn.disabled) {
        submitBtn.click();
      }
    }
  }
});

const style = document.createElement("style");
style.textContent = `
  .auth-card {
    transform: translateY(20px);
    opacity: 0;
    animation: slideInUp 0.6s ease forwards;
  }
  
  @keyframes slideInUp {
    to {
      transform: translateY(0);
      opacity: 1;
    }
  }
  
  .input-group input:focus {
    transform: translateY(-1px);
  }
  
  .btn-auth:active {
    transform: translateY(0) !important;
  }
  
  .field-error {
    animation: fadeInDown 0.3s ease;
  }
  
  @keyframes fadeInDown {
    from {
      opacity: 0;
      transform: translateY(-10px);
    }
    to {
      opacity: 1;
      transform: translateY(0);
    }
  }
`;
document.head.appendChild(style);
