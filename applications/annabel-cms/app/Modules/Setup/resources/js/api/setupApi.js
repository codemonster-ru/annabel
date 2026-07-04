export async function loadRequirements() {
  const response = await fetch('/setup/requirements', {
    method: 'GET',
    headers: { Accept: 'application/json' },
    credentials: 'same-origin',
  });

  return jsonResponse(response);
}

export async function validateDatabase(csrfToken, database) {
  const body = new FormData();
  body.append('_token', csrfToken);
  body.append('db_host', database.host);
  body.append('db_port', database.port);
  body.append('db_database', database.database);
  body.append('db_username', database.username);
  body.append('db_password', database.password);

  const response = await fetch('/setup/database', {
    method: 'POST',
    headers: { Accept: 'application/json' },
    body,
    credentials: 'same-origin',
  });

  return jsonResponse(response);
}

export async function installCms(csrfToken, data) {
  const body = new FormData();
  body.append('_token', csrfToken);
  body.append('license_accepted', data.licenseAccepted ? '1' : '0');
  body.append('db_host', data.database.host);
  body.append('db_port', data.database.port);
  body.append('db_database', data.database.database);
  body.append('db_username', data.database.username);
  body.append('db_password', data.database.password);
  body.append('admin_username', data.admin.username);
  body.append('admin_email', data.admin.email);
  body.append('admin_password', data.admin.password);
  body.append('admin_password_confirmation', data.admin.passwordConfirmation);

  const response = await fetch('/setup', {
    method: 'POST',
    headers: { Accept: 'application/json' },
    body,
    credentials: 'same-origin',
  });

  return jsonResponse(response);
}

async function jsonResponse(response) {
  const payload = await response.json();

  return {
    ok: response.ok,
    status: response.status,
    payload,
  };
}
