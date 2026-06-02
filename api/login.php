<?php
declare(strict_types=1);

require __DIR__ . '/config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(['success' => false, 'message' => 'Faqat POST so‘rovi qabul qilinadi.'], 405);
}

$data = request_data();
$phone = normalize_phone((string)($data['phone'] ?? ''));
$password = (string)($data['password'] ?? '');

if ($phone === '' || $password === '') {
    respond(['success' => false, 'message' => 'Telefon raqam va parolni kiriting.'], 422);
}

$stmt = db()->prepare('SELECT * FROM users WHERE phone = :phone LIMIT 1');
$stmt->execute(['phone' => $phone]);
$user = $stmt->fetch();

if (!$user || (string)$user['password'] !== $password) {
    respond(['success' => false, 'message' => 'Telefon raqam yoki parol noto‘g‘ri.'], 401);
}

$_SESSION['user'] = [
    'id' => (int)$user['id'],
    'full_name' => $user['full_name'],
    'phone' => $user['phone'],
    'role' => $user['role'],
    'district_key' => $user['district_key'],
    'district_name' => $user['district_name'],
];

$redirect = $user['district_key'] === 'butun_buxoro' ? '/dashboard/' : '/second_admin/';

respond([
    'success' => true,
    'message' => 'Tizimga muvaffaqiyatli kirildi.',
    'redirect' => $redirect,
    'user' => $_SESSION['user'],
]);
