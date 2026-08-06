<?php

require_once __DIR__ . '/../config/cors.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../utils/helpers.php';
require_once __DIR__ . '/../middleware/auth.php';

setupCORS();
$db = Database::getConnection();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'register':
        if ($method !== 'POST') jsonError('Method not allowed', 405);
        $data = getJsonInput();
        
        $required = ['email', 'password', 'first_name', 'last_name', 'address', 'bin_size', 'bin_color', 'payment_plan_id'];
        foreach ($required as $field) {
            if (empty($data[$field])) jsonError("Field {$field} is required");
        }
        
        if (!validateEmail($data['email'])) jsonError('Invalid email address');
        $pwErrors = validatePassword($data['password']);
        if ($pwErrors) jsonError(implode('. ', $pwErrors));

        $email = strtolower(trim($data['email']));
        $stmt = $db->prepare(
            'SELECT u.id
             FROM users u
             LEFT JOIN residents res ON res.user_id = u.id
             WHERE LOWER(TRIM(u.email)) = ?
               AND (
                    u.is_active = 1
                    OR COALESCE(res.registration_confirmed, 0) = 1
                    OR u.role_id != (SELECT id FROM roles WHERE name = \'resident\' LIMIT 1)
               )
             LIMIT 1'
        );
        $stmt->execute([$email]);
        if ($stmt->fetch()) jsonError('This email is already registered');

        $pendingStmt = $db->prepare(
            'SELECT u.id
             FROM users u
             JOIN roles r ON r.id = u.role_id AND r.name = \'resident\'
             LEFT JOIN residents res ON res.user_id = u.id
             WHERE LOWER(TRIM(u.email)) = ?
               AND u.is_active = 0
               AND COALESCE(res.registration_confirmed, 0) = 0
             LIMIT 1'
        );
        $pendingStmt->execute([$email]);
        $pending = $pendingStmt->fetch();
        if ($pending) {
            $deletePending = $db->prepare('DELETE FROM users WHERE id = ? AND is_active = 0');
            $deletePending->execute([(int) $pending['id']]);
        }

        $price = getPricing($db, $data['bin_size'], (int)$data['payment_plan_id'], $data['zone_id'] ?? null);
        if ($price === null) jsonError('Invalid pricing configuration');
        
        $db->beginTransaction();
        try {
            $roleStmt = $db->prepare("SELECT id FROM roles WHERE name = 'resident'");
            $roleStmt->execute();
            $roleId = $roleStmt->fetch()['id'];
            
            $verificationToken = generateToken();
            $passwordHash = password_hash($data['password'], PASSWORD_BCRYPT);
            
            $userStmt = $db->prepare('INSERT INTO users (role_id, email, password_hash, first_name, last_name, phone, verification_token, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, 0)');
            $userStmt->execute([
                $roleId, $email, $passwordHash,
                sanitize($data['first_name']), sanitize($data['last_name']),
                sanitize($data['phone'] ?? ''), $verificationToken
            ]);
            $userId = (int)$db->lastInsertId();
            
            $resStmt = $db->prepare('INSERT INTO residents (user_id, zone_id, address, city, selected_bin_size, selected_bin_color, payment_plan_id, service_fee, registration_confirmed) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0)');
            $resStmt->execute([
                $userId, $data['zone_id'] ?? null, sanitize($data['address']),
                sanitize($data['city'] ?? 'Accra'), $data['bin_size'], $data['bin_color'],
                (int)$data['payment_plan_id'], $price
            ]);
            
            $db->commit();
            
            jsonResponse([
                'success' => true,
                'message' => 'Registration pending confirmation',
                'user_id' => $userId,
                'confirmation' => [
                    'bin_size' => $data['bin_size'],
                    'bin_color' => $data['bin_color'],
                    'payment_plan_id' => (int)$data['payment_plan_id'],
                    'service_fee' => $price,
                    'total_payable' => $price,
                ]
            ], 201);
        } catch (Exception $e) {
            $db->rollBack();
            jsonError('Registration failed: ' . $e->getMessage(), 500);
        }
        break;

    case 'confirm-registration':
        if ($method !== 'POST') jsonError('Method not allowed', 405);
        $data = getJsonInput();
        if (empty($data['user_id'])) jsonError('User ID required');
        
        $stmt = $db->prepare('UPDATE residents SET registration_confirmed = 1 WHERE user_id = ?');
        $stmt->execute([(int)$data['user_id']]);
        
        $userStmt = $db->prepare('UPDATE users SET is_active = 1, email_verified = 1 WHERE id = ?');
        $userStmt->execute([(int)$data['user_id']]);
        
        $availBin = $db->prepare("SELECT id FROM dustbins WHERE size = (SELECT selected_bin_size FROM residents WHERE user_id = ?) AND color = (SELECT selected_bin_color FROM residents WHERE user_id = ?) AND status = 'available' LIMIT 1");
        $availBin->execute([(int)$data['user_id'], (int)$data['user_id']]);
        $bin = $availBin->fetch();
        
        if ($bin) {
            $resId = $db->prepare('SELECT id FROM residents WHERE user_id = ?');
            $resId->execute([(int)$data['user_id']]);
            $residentId = $resId->fetch()['id'];
            
            $assign = $db->prepare('INSERT INTO bin_assignments (resident_id, dustbin_id) VALUES (?, ?)');
            $assign->execute([$residentId, $bin['id']]);
            
            $updateBin = $db->prepare("UPDATE dustbins SET status = 'assigned' WHERE id = ?");
            $updateBin->execute([$bin['id']]);
        }
        
        sendNotification($db, (int)$data['user_id'], 'Welcome!', 'Your account has been activated. Your bin will be delivered soon.', 'general');
        logActivity($db, (int)$data['user_id'], 'registration_confirmed', 'auth');
        
        jsonResponse(['success' => true, 'message' => 'Registration confirmed and account activated']);
        break;

    case 'login':
        if ($method !== 'POST') jsonError('Method not allowed', 405);
        $data = getJsonInput();
        
        if (empty($data['email']) || empty($data['password'])) {
            jsonError('Email and password are required');
        }
        
        $stmt = $db->prepare('SELECT u.*, r.name as role_name FROM users u JOIN roles r ON u.role_id = r.id WHERE u.email = ?');
        $stmt->execute([$data['email']]);
        $user = $stmt->fetch();
        
        if (!$user || !password_verify($data['password'], $user['password_hash'])) {
            jsonError('Invalid email or password', 401);
        }
        
        if (!$user['is_active']) {
            jsonError('Account is not activated. Please confirm your registration.', 403);
        }
        
        setUserSession($user);
        
        $updateLogin = $db->prepare('UPDATE users SET last_login = NOW() WHERE id = ?');
        $updateLogin->execute([$user['id']]);
        
        logActivity($db, $user['id'], 'login', 'auth');
        
        jsonResponse([
            'success' => true,
            'user' => [
                'id' => $user['id'],
                'email' => $user['email'],
                'first_name' => $user['first_name'],
                'last_name' => $user['last_name'],
                'role' => $user['role_name'],
                'avatar_url' => $user['avatar_url'],
            ],
            'csrf_token' => getCsrfToken(),
        ]);
        break;

    case 'logout':
        if ($method !== 'POST') jsonError('Method not allowed', 405);
        $user = requireAuth();
        logActivity($db, $user['id'], 'logout', 'auth');
        destroySession();
        jsonResponse(['success' => true, 'message' => 'Logged out successfully']);
        break;

    case 'me':
        if ($method !== 'GET') jsonError('Method not allowed', 405);
        $user = requireAuth();
        $fullUser = getUserById($db, $user['id']);
        unset($fullUser['password_hash'], $fullUser['reset_token'], $fullUser['verification_token']);
        jsonResponse(['success' => true, 'user' => $fullUser, 'csrf_token' => getCsrfToken()]);
        break;

    case 'forgot-password':
        if ($method !== 'POST') jsonError('Method not allowed', 405);
        $data = getJsonInput();
        if (empty($data['email'])) jsonError('Email is required');
        
        $stmt = $db->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$data['email']]);
        $user = $stmt->fetch();
        
        if ($user) {
            $token = generateToken();
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
            $update = $db->prepare('UPDATE users SET reset_token = ?, reset_token_expires = ? WHERE id = ?');
            $update->execute([$token, $expires, $user['id']]);
        }
        
        jsonResponse(['success' => true, 'message' => 'If the email exists, a reset link has been sent']);
        break;

    case 'reset-password':
        if ($method !== 'POST') jsonError('Method not allowed', 405);
        $data = getJsonInput();
        
        if (empty($data['token']) || empty($data['password'])) {
            jsonError('Token and new password are required');
        }
        
        $pwErrors = validatePassword($data['password']);
        if ($pwErrors) jsonError(implode('. ', $pwErrors));
        
        $stmt = $db->prepare('SELECT id FROM users WHERE reset_token = ? AND reset_token_expires > NOW()');
        $stmt->execute([$data['token']]);
        $user = $stmt->fetch();
        
        if (!$user) jsonError('Invalid or expired reset token');
        
        $hash = password_hash($data['password'], PASSWORD_BCRYPT);
        $update = $db->prepare('UPDATE users SET password_hash = ?, reset_token = NULL, reset_token_expires = NULL WHERE id = ?');
        $update->execute([$hash, $user['id']]);
        
        logActivity($db, $user['id'], 'password_reset', 'auth');
        jsonResponse(['success' => true, 'message' => 'Password reset successfully']);
        break;

    default:
        jsonError('Invalid action', 404);
}
