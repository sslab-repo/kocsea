<?php
// Copy to config.php and update these for your environment
define('DB_HOST', 'db.example.com');
define('DB_NAME', 'kocsea_news');
define('DB_USER', 'kocsea_news');
define('DB_PASS', 'CHANGE_ME');

// Base URL of this app (no trailing slash). Adjust if using a subfolder.
// Example: https://www.touchingheartssoftware.com/ecamsnews
define('BASE_URL', 'https://app.datapot.net/kocsea/news');

// Optional: base URL used for <img src> in the newsletter preview (copy-paste to email).
// Defaults to BASE_URL with http:// when not defined. Set to https once the host has a
// valid certificate for this domain.
// define('ASSET_BASE_URL', 'https://app.datapot.net/kocsea/news');

// Where to save generated PDFs (ensure webserver can write here)
define('PDF_OUTPUT_DIR', __DIR__ . '/newsletters');

// Session cookie hardening (optional but recommended)
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Lax');

// Editor options
define('SITE_NAME', 'KOCSEA News');
