# Global-Login System

## 📋 Überblick
Zentraler Authentifizierungsdienst für alle trinkbecherdepot.de Anwendungen (Wusler3, Lernapp, Chat, etc.).

**Live URL**: https://auth.trinkbecherdepot.de

## 🚀 Schnellstart

### Test-Zugänge
| Email | Passwort | User-ID | Status |
|-------|----------|---------|--------|
| `marcobeigfn@kibali.de` | `TestPass123!` | 10 | ✅ Verifiziert |
| `newtest@example.com` | `SicheresPW2024!` | 7 | ✅ Verifiziert |

### API Basis
```bash
curl -X POST https://auth.trinkbecherdepot.de/login \
  -H "Content-Type: application/json" \
  -d '{"email":"marcobeigfn@kibali.de","password":"TestPass123!"}'
```

## 🔌 API Endpoints

### Authentifizierung
| Methode | Endpoint | Beschreibung |
|---------|----------|-------------|
| `POST` | `/register` | User-Registrierung |
| `POST` | `/login` | Login mit optionaler 2FA |
| `GET` | `/validate-token` | JWT Token validieren |
| `GET` | `/verify-email` | Email-Verifikation |
| `POST` | `/request-password-reset` | Passwort-Reset anfordern |
| `GET`/`POST` | `/reset-password` | Passwort zurücksetzen |

### Beispiele

**Login:**
```json
POST /login
{
  "email": "user@example.com",
  "password": "securepassword"
}

Response:
{
  "success": true,
  "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
  "user": {
    "id": 10,
    "username": "testuser",
    "email": "user@example.com",
    "email_verified": true,
    "two_factor_enabled": false
  }
}
```

**Token validieren:**
```bash
curl -H "Authorization: Bearer YOUR_TOKEN" \
  https://auth.trinkbecherdepot.de/validate-token
```

## 🔐 JWT Tokens

### Secret
```php
define('JWT_SECRET', 'dein_festes_geheimnis_hier_mindestens_32_zeichen');
```

### Token Claims
```json
{
  "user_id": 10,
  "email": "user@example.com",
  "username": "testuser",
  "iat": 1735260000,
  "exp": 1735346400
}
```

### Gültigkeit
- **Standard**: 24 Stunden
- **Algorithmus**: HS256

## 🗄️ Datenbank

### Connection
```php
Host: k1u2.your-database.de
Datenbank: trinkb_db1_spiele (global_login Schema)
User: trinkb_1
Passwort: w9[LDQxo)ooB
```

### Tabellen

#### `users` (Kern-Tabelle)
| Spalte | Typ | Beschreibung |
|--------|-----|-------------|
| `id` | INT | Primärschlüssel |
| `username` | VARCHAR(50) | Eindeutiger Username |
| `email` | VARCHAR(100) | Eindeutige Email |
| `password_hash` | VARCHAR(255) | BCrypt Hash |
| `is_active` | TINYINT(1) | Account aktiv (0/1) |
| `email_verified` | TINYINT(1) | Email verifiziert |
| `two_factor_enabled` | TINYINT(1) | 2FA aktiviert |
| `created_at` | TIMESTAMP | Erstellungsdatum |
| `updated_at` | TIMESTAMP | Letzte Änderung |

#### Weitere Tabellen
- `email_verifications` - Email-Verifikationscodes
- `password_resets` - Passwort-Reset Tokens
- `two_factor_codes` - 2FA Codes
- `known_devices` - Bekannte Geräte
- `login_attempts` - Login-Versuche
- `api_tokens` - API Tokens
- `roles` & `user_roles` - Rollenverwaltung

## 🛠️ Integration in andere Anwendungen

### PHP Beispiel
```php
class AuthClient {
    private $apiUrl = 'https://auth.trinkbecherdepot.de';
    
    public function login($email, $password) {
        $data = ['email' => $email, 'password' => $password];
        $response = $this->post('/login', $data);
        
        if ($response['success']) {
            $_SESSION['global_token'] = $response['token'];
            $_SESSION['global_user_id'] = $response['user']['id'];
        }
        
        return $response;
    }
    
    public function validateToken($token) {
        return $this->get('/validate-token', [
            'headers' => ['Authorization: Bearer ' . $token]
        ]);
    }
}
```

### JavaScript/React Beispiel
```javascript
const API_URL = 'https://auth.trinkbecherdepot.de';

async function login(email, password) {
    const response = await fetch(`${API_URL}/login`, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({email, password})
    });
    
    const data = await response.json();
    if (data.success) {
        localStorage.setItem('global_token', data.token);
    }
    return data;
}
```

## 📡 User Applications Registry

### Tabelle `user_applications`
```sql
CREATE TABLE user_applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    app_id ENUM('wusler3', 'lernapp', 'chat', '...') NOT NULL,
    initialized_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_accessed TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT TRUE,
    UNIQUE KEY unique_user_app (user_id, app_id)
);
```

### API Endpoints (geplant)
- `GET /api/user/{id}/applications` - User's aktive Apps
- `POST /api/user/applications/register` - App-Registrierung

## 🔒 Sicherheit

### Implementiert
- ✅ BCrypt Password Hashing
- ✅ JWT mit 24h Expiry
- ✅ Rate Limiting (Login Attempts)
- ✅ 2FA per Email
- ✅ Email-Verifikation
- ✅ CSRF Protection

### Konfiguration
- **Email Limit**: 500 Emails/Stunde (Hetzner)
- **Session Timeout**: 24 Stunden
- **Password Policy**: Mindestens 8 Zeichen

## 🖥️ Server Infrastruktur

### Server 1: Hetzner VPS (142.132.170.102)
```
Services: Global-Login API, Mosquitto MQTT
OS: Debian 12
IPs: IPv4 + IPv6 (2a01:4f8:1c1a:98d5::1)
Zugang: SSH (Port 22), SFTP
```

### Server 2: Hetzner Webspace
```
Services: Anwendungs-Frontends (Wusler3, Lernapp, etc.)
Domains: *.trinkbecherdepot.de
DB: MySQL 5.5 (10 Datenbanken Limit)
Email: mail.your-server.de
```

### Datenbank-Server
```
Host: k1u2.your-database.de
MySQL: 8.0
phpMyAdmin: https://auth.trinkbecherdepot.de/phpmyadmin
```

## 🚨 Notfall/Wartung

### Admin Zugänge
| Service | User | Passwort |
|---------|------|----------|
| SSH/SFTP | root | TNJ |
| MySQL Root | root | heut67zur%Post..nach41 |
| phpMyAdmin | pma | regen717&traum.._.bal7 |
| Email SMTP | authenticator@trinkbecherdepot.de | cJ90NH4rk35HfGyiheulsuse |

### Backup
- **Datenbank**: Tägliche Backups via phpMyAdmin
- **Code**: Git Repository
- **Konfiguration**: In `config/` Verzeichnis

## 🔗 Verwandte Projekte

### Abhängigkeiten
- **Wusler3 Spiel**: Nutzt Global-Login für Authentifizierung
- **Chat-Service**: Nutzt Global-Login + MQTT
- **Lernapp**: Nutzt Global-Login

### Integration Flow
```
Anwendung → Global-Login (Auth) → JWT Token
Token → Anwendung (Session) → API Calls
Token → Andere Services (Chat, etc.)
```

## 📞 Support & Kontakt

### Bei Problemen
1. **Login Issues**: Passwort-Reset Funktion nutzen
2. **API Probleme**: Token validieren `/validate-token`
3. **Server Down**: Hetzner Console prüfen

### Entwicklung
- **Code Location**: `/var/www/html/global-login/`
- **Live Testing**: Test-Accounts oben nutzen
- **Debug Mode**: `debug.php` im public Verzeichnis

---

*Letzte Aktualisierung: 27.12.2025*  
*Dokumentation für Wusler3 Ecosystem*