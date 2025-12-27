<?php
// Database
define('DB_HOST', 'localhost');
define('DB_NAME', 'global_login');
define('DB_USER', 'root');
define('DB_PASS', 'heut67zur%Post..nach41');

// JWT
define('JWT_SECRET', 'dein_festes_geheimnis_hier_mindestens_32_zeichen');
define('JWT_ALGORITHM', 'HS256');
define('JWT_EXPIRE_HOURS', 24);

// App
define('APP_URL', 'https://auth.trinkbecherdepot.de');
define('APP_NAME', 'Global Login');

// Security
define('BCRYPT_COST', 12);

// Email (Test-Modus)
define('EMAIL_TEST_MODE', true);

// 2FA Configuration
define('2FA_MODE', 'optional'); // 'disabled', 'optional', 'always', 'suspicious'
define('2FA_FAILED_ATTEMPTS_THRESHOLD', 3); // Nach X fehlgeschlagenen Versuchen
define('2FA_CODE_EXPIRE_MINUTES', 5); // Code-Gültigkeit in Minuten
define('2FA_NEW_DEVICE_GRACE_PERIOD', 24); // Stunden ohne 2FA bei neuem Gerät
