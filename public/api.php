<?php
declare(strict_types = 1);
header('Content-Type: application/json');

require('config.default.php');
require('config.php');

function error_out(int $code, string $msg): void {
    http_response_code($code);
    print json_encode(["error" => true, "error_message" => $msg]);
    die();
}

function ok_out(string $msg): void {
    // TODO: Might be a good idea to have different return codes for some situations
    http_response_code(200);
    print json_encode(["error" => false, "message" => $msg]);
    die();
}

function result_out(array $msg): void {
    // TODO: Might be a good idea to have different return codes for some situations
    http_response_code(200);
    print json_encode(["error" => false, "result" => $msg]);
    die();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    error_out(405, "Incorrect request method. Must use POST.");
}

$api_key = $_POST['api_key'] ?? '';

if (!is_string($api_key) || empty($api_key)) {
    error_out(401, "No API key provided.");
}

if (password_verify($api_key, $config['api_key']) !== true) {
    error_out(403, "Incorrect API key provided.");
}

$api = $_GET['api'] ?? '';

if (!is_string($api) || empty($api)) {
    error_out(400, "Invalid API request.");
}

$db = new PDO('sqlite:../test.sqlite3', null, null, [PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
$db->exec('PRAGMA foreign_keys=ON;');

switch($api) {
    case 'enroll':
    case 'enrol':
        $username = $_POST['username'] ?? '';
        $classification = $_POST['classification'] ?? '';
        $template = $_POST['template'] ?? '';

        if (!is_string($username) || empty($username))
            error_out(401, "No username provided.");
        if (!is_string($classification) || empty($classification))
            error_out(401, "No classification provided.");
        // TODO: template probably should be a file transfer?
        if (!is_string($template) || empty($template))
            error_out(401, "No template provided.");

        api_enroll($db, $username, $classification, $template);
        break;
    case 'get_user_templates':
        $username = $_POST['username'] ?? '';

        if (!is_string($username) || empty($username))
            error_out(401, "No username provided.");

        api_get_user_templates($db, $username);
        break;
    case 'verify':
    case 'identify':
        error_out(200, "Not implemented");
        break;
    default:
        error_out(400, "Unknown API command: " . $api);
}

function does_username_exist(PDO $db, string $username): bool {
    return $db->prepare('SELECT count(*) AS matches FROM users WHERE name = ?')->execute([$username])['matches'] == 0;
}

function create_or_get_user_id(PDO $db, string $username): int {
    $db->prepare('INSERT OR IGNORE INTO users (name) VALUES (?)')->execute([$username]);
    $query = $db->prepare('SELECT id FROM users WHERE name = ?');
    $query->execute([$username]);
    return (int)$query->fetch()['id'];
}

function is_classification_valid(string $classification): bool {
    return in_array($classification, ['l', 'r', 'w', 's', 't', 'a']);
}

function check_username_length(string $username): bool {
    return strlen($username) >= 4 && strlen($username) <= 32;
}

function is_template_valid(string $template): bool {
    // If anyone would like to write a lexical parser...
    return true;
}

function add_fingerprint_template(PDO $db, int $user_id, string $classification, string $template): void {
    $db->prepare('INSERT INTO fingerprints (user_id, classification, template) VALUES (?, ?, ?);')
        ->execute([$user_id, $classification, $template]);
}

function api_enroll(PDO $db, string $username, string $classification, string $template): void {
    if (!check_username_length($username))
        error_out(401, "Username must be 4-32 characters long.");
    if (!is_classification_valid($classification))
        error_out(401, "Invalid classification: $classification");
    if (!is_template_valid($template))
        error_out(401, "Invalid template provided.");

    $user_id = create_or_get_user_id($db, $username);

    add_fingerprint_template($db, $user_id, $classification, $template);

    ok_out("User '$username' enrolled successfully.");
}

function api_get_user_templates(PDO $db, string $username): void {
    $user_id = create_or_get_user_id($db, $username);
    $query = $db->prepare('SELECT id, classification, template FROM fingerprints WHERE user_id = ?');
    $query->execute([$user_id]);
    result_out($query->fetchAll());
}

error_out(500, "Unknown error.");
