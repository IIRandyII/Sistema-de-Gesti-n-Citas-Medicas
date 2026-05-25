// ============================================================
//  register.js — Lógica del formulario de registro
//  Depende de: auth.js  (setError, isValidEmail)
// ============================================================

// Solo permite dígitos en el campo teléfono y máximo 10
document.addEventListener('DOMContentLoaded', function () {
  const telInput = document.getElementById('reg-tel');
  if (telInput) {
    telInput.addEventListener('input', function () {
      this.value = this.value.replace(/\D/g, '').slice(0, 10);
    });
  }
});

function handleRegister() {
  const nombre   = document.getElementById('reg-nombre').value.trim();
  const apellido = document.getElementById('reg-apellido').value.trim();
  const email    = document.getElementById('reg-email').value.trim();
  const tel      = document.getElementById('reg-tel').value.trim();
  const pass     = document.getElementById('reg-pass').value;
  let ok = true;

  setError('err-reg-nombre',   !nombre);   if (!nombre)   ok = false;
  setError('err-reg-apellido', !apellido); if (!apellido) ok = false;

  const emailOk = isValidEmail(email);
  setError('err-reg-email', !emailOk); if (!emailOk) ok = false;

  // Teléfono: opcional, pero si se escribe debe tener 10 dígitos
  const telOk = tel === '' || /^\d{10}$/.test(tel);
  setError('err-reg-tel', !telOk); if (!telOk) ok = false;

  const passOk = pass.length >= 8;
  setError('err-reg-pass', !passOk); if (!passOk) ok = false;

  if (!ok) return;

  document.querySelector('form').submit();
}