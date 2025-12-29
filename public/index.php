<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
// header('Content-Type: application/json');

try {
    require_once __DIR__ . '/../config/config.php';
    require_once __DIR__ . '/../config/error-config.php';
    require_once __DIR__ . '/../config/autoload.php';
    require_once __DIR__ . '/../vendor/autoload.php';
    // require_once __DIR__ . '/../config/2fa-config.php';
    // require_once __DIR__ . '/../config/error-config.php';
    
    $request = $_SERVER['REQUEST_URI'] ?? '/';
    if (strpos($request, '?') !== false) {
        $request = substr($request, 0, strpos($request, '?'));
    }
    $method = strtoupper($_SERVER['REQUEST_METHOD']);
    
    // === ROUTING ===
    
    // GET / - API Root
    // if ($request === '/' && $method === 'GET') {
    //     echo json_encode([
    //         'service' => 'Global Login API',
    //         'status' => 'online',
    //         'endpoints' => [
    //             'GET /' => 'API Root',
    //             'GET /health' => 'Health check',
    //             'POST /register' => 'User registration',
    //             'POST /login' => 'User login',
    //             'GET /verify-email' => 'Email verification',
    //             'GET /validate-token' => 'Validate JWT token',
    //             'POST /request-password-reset' => 'Request password reset',
    //             'GET /reset-password' => 'Validate reset token',
    //             'POST /reset-password' => 'Set new password',
    //             'POST /enable-2fa' => 'Enable 2FA for user',
    //             'POST /disable-2fa' => 'Disable 2FA for user',
    //             'POST /request-2fa' => 'Request 2FA code',
    //             'POST /verify-2fa' => 'Verify 2FA code'
    //         ]
    //     ]);
    //     exit;
    // }

    // GET / - Landing Page
    if ($request === '/' && $method === 'GET') {
        // HTML Landing Page anzeigen
        readfile(__DIR__ . '/welcome.html');
        exit;
    }
    
    // GET /health - Health check
    if ($request === '/health' && $method === 'GET') {
        echo json_encode(['status' => 'online', 'timestamp' => date('Y-m-d H:i:s')]);
        exit;
    }
    
    // POST /register - User registration
    if ($request === '/register' && $method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (empty($data['username']) || empty($data['email']) || empty($data['password'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'All fields are required']);
            exit;
        }
        
        require_once __DIR__ . '/../src/Services/AuthService.php';
        $result = App\Services\AuthService::register(
            $data['username'],
            $data['email'],
            $data['password']
        );
        
        echo json_encode($result);
        exit;
    }
    
    // POST /login - User login with optional 2FA
    if ($request === '/login' && $method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (empty($data['email']) || empty($data['password'])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Email and password required']);
            exit;
        }
        
        require_once __DIR__ . '/../src/Services/AuthService.php';
        $result = App\Services\AuthService::login(
            $data['email'],
            $data['password']
        );
        
        echo json_encode($result);
        exit;
    }
    
    // GET /verify-email - Email verification
    if ($request === '/verify-email' && $method === 'GET') {
        $code = $_GET['code'] ?? '';
        
        if (empty($code)) {
            header('Content-Type: text/html; charset=utf-8');
            readfile(__DIR__ . '/verification-error.html');
            exit;
        }
        
        require_once __DIR__ . '/../src/Services/EmailVerificationService.php';
        $result = App\Services\EmailVerificationService::verifyCode($code);
        
        if ($result) {
            header('Content-Type: text/html; charset=utf-8');
            readfile(__DIR__ . '/verification-success.html');
        } else {
            header('Content-Type: text/html; charset=utf-8');
            readfile(__DIR__ . '/verification-error.html');
        }
        exit;
    }
    
    // GET /validate-token - Validate JWT token
    if ($request === '/validate-token' && $method === 'GET') {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? '';
        
        if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            require_once __DIR__ . '/../src/Utils/JWTHandler.php';
            $valid = App\Utils\JWTHandler::validateToken($matches[1]);
            echo json_encode($valid ? ['valid' => true] : ['valid' => false]);
        } else {
            echo json_encode(['error' => 'No token provided']);
        }
        exit;
    }

        // GET /api/user/{id}/applications - Get user's active applications
    if (preg_match('#^/api/user/(\d+)/applications$#', $request, $matches) && $method === 'GET') {
        $userId = (int)$matches[1];
        
        // Token validieren
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? '';
        
        if (!preg_match('/Bearer\s+(.*)$/i', $authHeader, $tokenMatches)) {
            http_response_code(401);
            echo json_encode(['error' => 'No token provided']);
            exit;
        }
        
        require_once __DIR__ . '/../src/Utils/JWTHandler.php';
        $tokenData = App\Utils\JWTHandler::decodeToken($tokenMatches[1]);
        
        if (!$tokenData || $tokenData->user_id != $userId) {
            http_response_code(403);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }
        
        // Datenbank abfragen
        require_once __DIR__ . '/../src/Models/Database.php';
        $db = App\Models\Database::getInstance()->getConnection();
        
        $stmt = $db->prepare("
            SELECT app_id, initialized_at, last_accessed, is_active 
            FROM user_applications 
            WHERE user_id = ? AND is_active = 1
            ORDER BY initialized_at DESC
        ");
        $stmt->execute([$userId]);
        $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'user_id' => $userId,
            'applications' => $applications
        ]);
        exit;
    }

        // POST /api/applications/register - Register user application
    if ($request === '/api/applications/register' && $method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        $appId = $data['app_id'] ?? '';
        $userId = $data['user_id'] ?? 0;
        
        if (empty($appId) || empty($userId)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'app_id and user_id required']);
            exit;
        }
        
        // Valide App IDs prüfen
        $validApps = ['wusler3', 'lernapp', 'chat'];
        if (!in_array($appId, $validApps)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid app_id']);
            exit;
        }
        
        // Token validieren (nur App selbst darf registrieren)
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? '';
        
        if (!preg_match('/Bearer\s+(.*)$/i', $authHeader, $tokenMatches)) {
            http_response_code(401);
            echo json_encode(['error' => 'No token provided']);
            exit;
        }
        
        require_once __DIR__ . '/../src/Utils/JWTHandler.php';
        $tokenData = App\Utils\JWTHandler::decodeToken($tokenMatches[1]);
        
        if (!$tokenData) {
            http_response_code(403);
            echo json_encode(['error' => 'Invalid token']);
            exit;
        }
        
        // Datenbank: UPSERT (INSERT or UPDATE)
        require_once __DIR__ . '/../src/Models/Database.php';
        $db = App\Models\Database::getInstance()->getConnection();
        
        $stmt = $db->prepare("
            INSERT INTO user_applications (user_id, app_id, is_active) 
            VALUES (?, ?, 1)
            ON DUPLICATE KEY UPDATE 
                is_active = 1,
                last_accessed = CURRENT_TIMESTAMP
        ");
        
        $success = $stmt->execute([$userId, $appId]);
        
        echo json_encode([
            'success' => $success,
            'message' => $success ? 'Application registered' : 'Registration failed'
        ]);
        exit;
    }


    
    // POST /request-password-reset - Request password reset
    if ($request === '/request-password-reset' && $method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        $email = $data['email'] ?? '';
        
        if (empty($email)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Email is required']);
            exit;
        }
        
        require_once __DIR__ . '/../src/Services/PasswordResetService.php';
        $result = App\Services\PasswordResetService::requestReset($email);
        
        echo json_encode($result);
        exit;
    }
    
    // GET /reset-password - Validate reset token
    if ($request === '/reset-password' && $method === 'GET') {
        $token = $_GET['token'] ?? '';
        
        if (empty($token)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Token is required']);
            exit;
        }
        
        require_once __DIR__ . '/../src/Services/PasswordResetService.php';
        $valid = App\Services\PasswordResetService::validateToken($token);
        
        if ($valid) {
            echo json_encode([
                'success' => true,
                'message' => 'Token is valid. Submit new password.',
                'email' => $valid['email']
            ]);
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid or expired token']);
        }
        exit;
    }
    
    // POST /reset-password - Set new password
    if ($request === '/reset-password' && $method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        $token = $data['token'] ?? '';
        $newPassword = $data['password'] ?? '';
        
        if (empty($token) || empty($newPassword)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Token and new password required']);
            exit;
        }
        
        if (strlen($newPassword) < 8) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Password must be at least 8 characters']);
            exit;
        }
        
        require_once __DIR__ . '/../src/Services/PasswordResetService.php';
        $result = App\Services\PasswordResetService::resetPassword($token, $newPassword);
        
        echo json_encode($result);
        exit;
    }
    
    // POST /enable-2fa - Enable 2FA for user
    if ($request === '/enable-2fa' && $method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        $userId = $data['user_id'] ?? 0;
        
        if (empty($userId)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'User ID required']);
            exit;
        }
        
        require_once __DIR__ . '/../src/Services/TwoFactorService.php';
        $result = App\Services\TwoFactorService::toggle2FA($userId, true);
        
        echo json_encode([
            'success' => $result,
            'message' => $result ? '2FA enabled successfully' : 'Failed to enable 2FA'
        ]);
        exit;
    }
    
    // POST /disable-2fa - Disable 2FA for user
    if ($request === '/disable-2fa' && $method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        $userId = $data['user_id'] ?? 0;
        
        if (empty($userId)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'User ID required']);
            exit;
        }
        
        require_once __DIR__ . '/../src/Services/TwoFactorService.php';
        $result = App\Services\TwoFactorService::toggle2FA($userId, false);
        
        echo json_encode([
            'success' => $result,
            'message' => $result ? '2FA disabled successfully' : 'Failed to disable 2FA'
        ]);
        exit;
    }
    
    // POST /request-2fa - Request 2FA code
    if ($request === '/request-2fa' && $method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        $userId = $data['user_id'] ?? 0;
        $email = $data['email'] ?? '';
        
        if (empty($userId) || empty($email)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'User ID and email required']);
            exit;
        }
        
        require_once __DIR__ . '/../src/Services/TwoFactorService.php';
        $result = App\Services\TwoFactorService::generateAndSendCode($userId, $email);
        
        echo json_encode($result);
        exit;
    }
    
    // POST /verify-2fa - Verify 2FA code
    if ($request === '/verify-2fa' && $method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        $userId = $data['user_id'] ?? 0;
        $code = $data['code'] ?? '';
        
        if (empty($userId) || empty($code)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'User ID and code required']);
            exit;
        }
        
        require_once __DIR__ . '/../src/Services/TwoFactorService.php';
        require_once __DIR__ . '/../src/Models/User.php';
        require_once __DIR__ . '/../src/Utils/JWTHandler.php';
        
        $valid = App\Services\TwoFactorService::verifyCode($userId, $code);
        
        if ($valid) {
            $user = App\Models\User::findById($userId);
            
            if ($user) {
                $token = App\Utils\JWTHandler::generateToken(
                    $user['id'], 
                    $user['email'], 
                    $user['username']
                );
                
                echo json_encode([
                    'success' => true, 
                    'message' => '2FA verification successful',
                    'token' => $token,
                    'user' => [
                        'id' => $user['id'],
                        'username' => $user['username'],
                        'email' => $user['email']
                    ]
                ]);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'User not found']);
            }
        } else {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid or expired 2FA code']);
        }
        exit;
    }
    
    // Endpoint not found
    http_response_code(404);
    echo json_encode(['error' => 'Endpoint not found']);
    
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Server error',
        'message' => $e->getMessage()
    ]);
}