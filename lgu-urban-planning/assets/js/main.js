// Main JS

// --- 1. GLOBAL UTILITY FUNCTIONS ---

// Password Visibility Toggle
function togglePassword(inputId, iconId) {
    const input = document.getElementById(inputId);
    const icon = document.getElementById(iconId);
    if (input && icon) {
        const isPass = input.type === "password";
        input.type = isPass ? "text" : "password";
        icon.classList.toggle('bi-eye', !isPass);
        icon.classList.toggle('bi-eye-slash', isPass);
    }
}

// Multi-step Form Navigation
function nextStep(step) {
    const currentStep = document.querySelector('.form-step.active');
    const currentStepNum = currentStep ? parseInt(currentStep.id.replace('step', '')) : 0;

    if (step > currentStepNum) {
        const inputs = currentStep.querySelectorAll('input[required], select[required]');
        let allValid = true;

        inputs.forEach(input => {
            if (!input.value.trim()) {
                allValid = false;
                input.classList.add('is-invalid');
            } else {
                input.classList.remove('is-invalid');
            }
        });

        if (!allValid) {
            alert("Please fill in all required fields before proceeding.");
            return;
        }

        // Password Validation for Step 2
        const regPass = document.getElementById('reg_password');
        const confirmPass = document.getElementById('confirm_password');
        if (currentStepNum === 2 && regPass) {
            const pass = regPass.value;
            if (pass.length < 8 || !/[A-Z]/.test(pass) || !/[0-9]/.test(pass)) {
                alert("Password must be 8+ chars, with uppercase and number!");
                return;
            }
            if (pass !== confirmPass.value) {
                alert("Passwords do not match!");
                confirmPass.classList.add('is-invalid');
                return;
            }
        }
    }

    document.querySelectorAll('.form-step').forEach(el => el.classList.remove('active'));
    const target = document.getElementById(`step${step}`);
    if (target) target.classList.add('active');

    document.querySelectorAll('.dot').forEach((dot, idx) => {
        dot.classList.toggle('active', idx + 1 === step);
    });

    document.querySelector('.register-card')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

// ID Upload Toggle
function toggleUploads() {
    const idType = document.getElementById('id_type');
    const uploadSection = document.getElementById('upload-section');
    if (idType && uploadSection) {
        uploadSection.style.display = idType.value ? 'block' : 'none';
    }
}

// File Validation
function validateFileSize(input) {
    if (input.files?.[0] && input.files[0].size / 1024 / 1024 > 2) {
        alert("File is too large! Maximum allowed size is 2MB.");
        input.value = "";
    }
}

// --- 2. CORE LOGIC (ON DOM LOAD) ---
document.addEventListener('DOMContentLoaded', () => {
    
    // --- SELECTORS ---
    const html = document.documentElement;
    const darkModeBtn = document.getElementById('darkModeBtn');
    const btnEn = document.getElementById('btn-en');
    const btnTl = document.getElementById('btn-tl');

    // --- THEME MANAGEMENT ---
    function applyTheme(theme) {
        html.setAttribute('data-bs-theme', theme);
        localStorage.setItem('theme', theme);
        
        if (darkModeBtn) {
            darkModeBtn.innerHTML = theme === 'dark' 
                ? '<i class="bi bi-sun fs-5"></i>' 
                : '<i class="bi bi-moon-stars fs-5"></i>';
        }
        console.log("System Theme:", theme);
    }

    if (darkModeBtn) {
        darkModeBtn.addEventListener('click', (e) => {
            e.preventDefault();
            const currentTheme = html.getAttribute('data-bs-theme');
            applyTheme(currentTheme === 'dark' ? 'light' : 'dark');
        });
    }

    // --- LANGUAGE MANAGEMENT ---
    function changeLanguage(lang) {
        if (btnEn) btnEn.classList.toggle('active', lang === 'en');
        if (btnTl) btnTl.classList.toggle('active', lang === 'tl');

        // Text content translation
        document.querySelectorAll('[data-en]').forEach(el => {
            const trans = el.getAttribute(`data-${lang}`);
            if (trans) el.textContent = trans;
        });

        // Placeholder translation
        document.querySelectorAll('[data-en-placeholder]').forEach(input => {
            const transPlaceholder = input.getAttribute(`data-${lang}-placeholder`);
            if (transPlaceholder) input.setAttribute('placeholder', transPlaceholder);
        });

        localStorage.setItem('preferredLang', lang);
    }

    btnEn?.addEventListener('click', () => changeLanguage('en'));
    btnTl?.addEventListener('click', () => changeLanguage('tl'));

    // --- INITIALIZATION ---
    // Load saved theme
    const savedTheme = localStorage.getItem('theme') || 'light';
    applyTheme(savedTheme);

    // Load saved language
    const savedLang = localStorage.getItem('preferredLang') || 'en';
    changeLanguage(savedLang);

    // Bootstrap Alert Auto-dismiss
    setTimeout(() => {
        document.querySelectorAll('.alert').forEach(alert => {
            if (typeof bootstrap !== 'undefined' && bootstrap.Alert) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }
        });
    }, 5000);

    // Password Strength Meter (Listener)
    const regPassInput = document.getElementById('reg_password');
    if (regPassInput) {
        regPassInput.addEventListener('input', function() {
            const password = this.value;
            const bar = document.getElementById('strength-bar');
            const text = document.getElementById('strength-text');
            if (!bar || !text) return;

            let strength = 0;
            if (password.length >= 8) strength++;
            if (/[A-Z]/.test(password)) strength++;
            if (/[a-z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[^A-Za-z0-9]/.test(password)) strength++;

            const config = [
                { w: "0%", c: "#ef4444", t: "Too Short" },
                { w: "20%", c: "#ef4444", t: "Very Weak" },
                { w: "40%", c: "#f97316", t: "Weak" },
                { w: "60%", c: "#eab308", t: "Good" },
                { w: "80%", c: "#2563eb", t: "Strong" },
                { w: "100%", c: "#22c55e", t: "Very Strong" }
            ];

            bar.style.width = config[strength].w;
            bar.style.backgroundColor = config[strength].c;
            text.innerText = config[strength].t;
        });
    }

        // --- OPTIMIZED OTP BOX LOGIC ---
    const otpInputs = document.querySelectorAll('.otp-box');
    const finalOtpInput = document.getElementById('final_otp');

    if (otpInputs.length > 0) {
        otpInputs.forEach((input, index) => {
            input.addEventListener('input', (e) => {
                const value = e.target.value;
                e.target.value = value.replace(/[^0-9]/g, '');

                // Auto-focus logic
                if (e.target.value.length === 1 && index < otpInputs.length - 1) {
                    otpInputs[index + 1].focus();
                }
                
                updateFinalOTP();
            });

            input.addEventListener('keydown', (e) => {
                if (e.key === "Backspace" && input.value === "" && index > 0) {
                    otpInputs[index - 1].focus();
                }
            });
        });
    }

    function updateFinalOTP() {
        let combined = "";
        const otpInputs = document.querySelectorAll('.otp-box'); 
        const finalOtpInput = document.getElementById('final_otp');
        
        otpInputs.forEach(b => combined += b.value); 
        
        if (finalOtpInput) {
            finalOtpInput.value = combined;
            console.log("OTP so far: " + combined);
            if (combined.length === 6) {
                document.getElementById('otpForm').submit();
            }
        }
    }

    // REQUEST NEW OTP
    const otpForm = document.getElementById('otpForm');
    if (otpForm) {
        otpForm.addEventListener('submit', function(e) {
            let combined = "";
            document.querySelectorAll('.otp-box').forEach(b => combined += b.value);
            document.getElementById('final_otp').value = combined;
            
            if (combined.length < 6) {
                e.preventDefault(); 
                alert("Please complete the 6-digit code.");
            }
        });
    }
});
