<?php
declare(strict_types=1);

session_start();

header('Content-Type: application/json; charset=utf-8');

const DB_HOST = 'localhost';
const DB_NAME = 'pneumo';
const DB_USER = 'pneumo';
const DB_PASS = 'Buxoro2025';

function db(): PDO
{
    static $pdo = null;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    return $pdo;
}

function respond(array $payload, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function request_data(): array
{
    $raw = file_get_contents('php://input');
    $json = $raw ? json_decode($raw, true) : null;

    if (is_array($json)) {
        return $json;
    }

    return $_POST;
}

function normalize_phone(string $phone): string
{
    $digits = preg_replace('/\D+/', '', $phone) ?? '';

    if (strlen($digits) === 9) {
        return '+998' . $digits;
    }

    if (strlen($digits) === 12 && substr($digits, 0, 3) === '998') {
        return '+' . $digits;
    }

    return $phone;
}

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function require_auth(): array
{
    $user = current_user();
    if (!$user) {
        respond(['success' => false, 'message' => 'Avval tizimga kiring.'], 401);
    }

    return $user;
}

function is_butun_buxoro(array $user): bool
{
    return ($user['district_key'] ?? '') === 'butun_buxoro';
}

function require_butun_buxoro(): array
{
    $user = require_auth();

    if (!is_butun_buxoro($user)) {
        respond(['success' => false, 'message' => 'Bu amal faqat Butun Buxoro roli uchun ruxsat etilgan.'], 403);
    }

    return $user;
}

function district_where_for_user(array $user, string $alias = ''): array
{
    if (is_butun_buxoro($user)) {
        return ['', []];
    }

    $column = $alias ? $alias . '.district_key' : 'district_key';
    return [" AND {$column} = :auth_district", ['auth_district' => $user['district_key']]];
}

function fetch_patient(int $id): ?array
{
    $stmt = db()->prepare('SELECT * FROM patients WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $patient = $stmt->fetch();

    return $patient ?: null;
}

function assert_patient_visible(array $user, array $patient): void
{
    if (!is_butun_buxoro($user) && ($patient['district_key'] ?? '') !== ($user['district_key'] ?? '')) {
        respond(['success' => false, 'message' => 'Bu bemor sizning tumaningizga tegishli emas.'], 403);
    }
}

function patient_payload(array $data): array
{
    $districtKey = trim((string)($data['districtKey'] ?? $data['district_key'] ?? ''));
    $districtName = trim((string)($data['districtName'] ?? $data['district_name'] ?? $districtKey));

    return [
        'full_name' => trim((string)($data['fullName'] ?? $data['full_name'] ?? '')),
        'birth_day' => (int)($data['birthDay'] ?? $data['birth_day'] ?? 0),
        'birth_month' => (int)($data['birthMonth'] ?? $data['birth_month'] ?? 0),
        'birth_year' => (int)($data['birthYear'] ?? $data['birth_year'] ?? 0),
        'passport_series' => strtoupper(trim((string)($data['passportSeries'] ?? $data['passport_series'] ?? ''))),
        'passport_number' => trim((string)($data['passportNumber'] ?? $data['passport_number'] ?? '')),
        'pinfl' => trim((string)($data['pinfl'] ?? '')),
        'gender' => trim((string)($data['gender'] ?? 'male')),
        'district_key' => $districtKey,
        'district_name' => $districtName,
        'village' => trim((string)($data['village'] ?? '')),
        'last_visit' => trim((string)($data['lastVisit'] ?? $data['last_visit'] ?? '')),
        'infection_date' => trim((string)($data['infectionDate'] ?? $data['infection_date'] ?? '')),
        'status' => trim((string)($data['status'] ?? 'Qabul kutilmoqda')),
        'details' => trim((string)($data['details'] ?? '')),
        'source_patient_id' => isset($data['sourcePatientId']) && $data['sourcePatientId'] !== ''
            ? (int)$data['sourcePatientId']
            : (isset($data['source_patient_id']) && $data['source_patient_id'] !== '' ? (int)$data['source_patient_id'] : null),
    ];
}

function validate_patient_payload(array $patient): void
{
    if ($patient['full_name'] === '' || $patient['passport_series'] === '' || $patient['passport_number'] === '' || $patient['pinfl'] === '' || $patient['district_key'] === '') {
        respond(['success' => false, 'message' => 'Majburiy bemor maʼlumotlari toʻliq emas.'], 422);
    }

    if (!preg_match('/^[A-Z]{2}$/', $patient['passport_series'])) {
        respond(['success' => false, 'message' => 'Pasport seriyasi 2 ta lotin harfidan iborat bo‘lishi kerak.'], 422);
    }

    if (!preg_match('/^\d{7}$/', $patient['passport_number'])) {
        respond(['success' => false, 'message' => 'Pasport raqami 7 xonali bo‘lishi kerak.'], 422);
    }

    if (!preg_match('/^\d{14}$/', $patient['pinfl'])) {
        respond(['success' => false, 'message' => 'JShShIR 14 xonali bo‘lishi kerak.'], 422);
    }

    if (!in_array($patient['gender'], ['male', 'female'], true)) {
        respond(['success' => false, 'message' => 'Bemor jinsi noto‘g‘ri tanlangan.'], 422);
    }
}

function mark_source_if_needed(?int $sourcePatientId): void
{
    if (!$sourcePatientId) {
        return;
    }

    $stmt = db()->prepare('UPDATE patients SET is_source = 1 WHERE id = :id');
    $stmt->execute(['id' => $sourcePatientId]);
}
