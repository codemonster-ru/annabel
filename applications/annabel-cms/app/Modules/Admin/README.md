# Admin Module

The Admin module owns the CMS administration interface and its backend routes.

## Structure

```text
Admin/
├── Controllers/
├── resources/
│   ├── css/
│   └── js/
├── routes/
├── views/
├── module.php
└── vite.config.js
```

The PHP view in `views/app.php` is the server-rendered shell. Vue renders both
the login screen and the authenticated administration interface.

Frontend source stays inside the module. The build publishes hashed browser
assets and a Vite manifest to `public/admin/assets`, which is the application's
shared public web root.

From the project root:

```bash
npm run build:admin
npm run dev:admin
```
