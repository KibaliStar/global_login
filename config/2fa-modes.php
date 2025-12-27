<?php
/*
// MODUS 1: 2FA deaktiviert
define('2FA_MODE', 'disabled'); // Niemals 2FA

// MODUS 2: 2FA optional (User-Entscheidung)
define('2FA_MODE', 'optional'); // User kann 2FA aktivieren

// MODUS 3: 2FA immer für alle
define('2FA_MODE', 'always'); // Immer 2FA erforderlich

// MODUS 4: Intelligentes 2FA (empfohlen)
define('2FA_MODE', 'suspicious'); // 2FA bei verdächtiger Aktivität
define('2FA_FAILED_ATTEMPTS_THRESHOLD', 3); // Nach 3 Fehlversuchen
define('2FA_NEW_DEVICE_GRACE_PERIOD', 24); // 24h ohne 2FA bei neuem Gerät
*/

// Aktuelle Konfiguration (ändere diese Werte):
define('2FA_MODE', 'suspicious');
define('2FA_FAILED_ATTEMPTS_THRESHOLD', 3);
define('2FA_CODE_EXPIRE_MINUTES', 5);
define('2FA_NEW_DEVICE_GRACE_PERIOD', 24);
