<?php
// 2FA Betriebsmodi:
// 'disabled'  - 2FA niemals erforderlich
// 'optional'  - User kann 2FA selbst aktivieren (Standard)
// 'always'    - 2FA immer für alle User erforderlich
// 'suspicious'- 2FA nur bei verdächtigen Logins (neues Gerät, Fehlversuche)
define('TFA_MODE', 'optional');

// Bei 'suspicious' Mode: Wie viele fehlgeschlagene Login-Versuche lösen 2FA aus?
define('TFA_FAILED_ATTEMPTS_THRESHOLD', 3);

// Bei 'suspicious' Mode: Wie viele Stunden ohne 2FA bei neuem Gerät? (Grace Period)
define('TFA_NEW_DEVICE_GRACE_HOURS', 24);

// Wie lange sind 2FA-Codes gültig (in Minuten)?
define('TFA_CODE_EXPIRE_MINUTES', 5);

// Welche User-Gruppen sind von 2FA ausgenommen? (Komma-getrennte Role-IDs)
define('TFA_EXEMPT_ROLES', '3'); // Role ID 3 = 'guest'
