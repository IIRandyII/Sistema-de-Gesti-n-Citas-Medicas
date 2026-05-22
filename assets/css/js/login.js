// ============================================================
//  login.js — Lógica del formulario de inicio de sesión
//  Depende de: auth.js  (setError, isValidEmail)
// ============================================================

function handleLogin() {
  const email = document.getElementById('login-email').value.trim();
  const pass  = document.getElementById('login-pass').value;
  let ok = true;

  const emailOk = isValidEmail(email);
  setError('err-login-email', !emailOk); if (!emailOk) ok = false;
  setError('err-login-pass',  !pass);    if (!pass)    ok = false;

  if (!ok) return;

  // TODO: enviar datos al servidor (fetch / submit del form PHP)
  console.log('Login con:', email);
}