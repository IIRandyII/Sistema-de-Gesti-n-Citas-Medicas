// ============================================================
//  register.js — Lógica del formulario de registro
//  Depende de: auth.js  (setError, isValidEmail)
// ============================================================

function handleRegister() {
  const nombre   = document.getElementById('reg-nombre').value.trim();
  const apellido = document.getElementById('reg-apellido').value.trim();
  const email    = document.getElementById('reg-email').value.trim();
  const pass     = document.getElementById('reg-pass').value;
  let ok = true;

  setError('err-reg-nombre',   !nombre);   if (!nombre)   ok = false;
  setError('err-reg-apellido', !apellido); if (!apellido) ok = false;

  const emailOk = isValidEmail(email);
  setError('err-reg-email', !emailOk); if (!emailOk) ok = false;

  const passOk = pass.length >= 8;
  setError('err-reg-pass', !passOk); if (!passOk) ok = false;

  if (!ok) return;

  // TODO: enviar datos al servidor (fetch / submit del form PHP)
  console.log('Registro:', nombre, apellido, email);
}