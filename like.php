<?php
session_start();
include "conexao.php";

// Verificar se o usuário está logado
if (!isset($_SESSION['usuario_logado']) || $_SESSION['usuario_logado'] !== true) {
    echo json_encode(['success' => false, 'message' => 'Usuário não logado']);
    exit;
}

header('Content-Type: application/json');

// Simple CORS for localhost development
if (isset($_SERVER['HTTP_ORIGIN'])) {
    header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');
}

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

$recado_id = intval($_POST['recado_id'] ?? 0);
$usuario_id = $_SESSION['usuario_id'];

if ($recado_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID do recado inválido']);
    exit;
}

// Verificar se o like já existe
$query_check = "SELECT id FROM likes WHERE recado_id = $recado_id AND usuario_id = $usuario_id";
$result_check = mysqli_query($conexao, $query_check);

$liked = false;
if (mysqli_num_rows($result_check) > 0) {
    // Já deu like, então remover (unlike)
    $query_delete = "DELETE FROM likes WHERE recado_id = $recado_id AND usuario_id = $usuario_id";
    mysqli_query($conexao, $query_delete);
} else {
    // Não deu like, então adicionar
    $query_insert = "INSERT INTO likes (recado_id, usuario_id) VALUES ($recado_id, $usuario_id)";
    mysqli_query($conexao, $query_insert);
    $liked = true;
}

// Contar likes totais para o recado
$query_count = "SELECT COUNT(*) as total FROM likes WHERE recado_id = $recado_id";
$result_count = mysqli_query($conexao, $query_count);
$row_count = mysqli_fetch_assoc($result_count);
$total_likes = $row_count['total'];

echo json_encode([
    'success' => true,
    'liked' => $liked,
    'total_likes' => $total_likes
]);
?>
