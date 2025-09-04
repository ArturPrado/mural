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
$comentario = trim($_POST['comentario'] ?? '');
$usuario_id = $_SESSION['usuario_id'];

if ($recado_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID do recado inválido']);
    exit;
}

if (empty($comentario)) {
    echo json_encode(['success' => false, 'message' => 'Comentário não pode estar vazio']);
    exit;
}

if (strlen($comentario) > 280) {
    echo json_encode(['success' => false, 'message' => 'Comentário muito longo (máx 280 caracteres)']);
    exit;
}

// Inserir comentário
$query_insert = "INSERT INTO comentarios (recado_id, usuario_id, comentario) VALUES ($recado_id, $usuario_id, '" . mysqli_real_escape_string($conexao, $comentario) . "')";
if (mysqli_query($conexao, $query_insert)) {
    $comment_id = mysqli_insert_id($conexao);

    // Buscar dados do usuário para retornar
    $query_user = "SELECT nome FROM usuarios WHERE id = $usuario_id";
    $result_user = mysqli_query($conexao, $query_user);
    $user_data = mysqli_fetch_assoc($result_user);

    echo json_encode([
        'success' => true,
        'comment' => [
            'id' => $comment_id,
            'nome' => $user_data['nome'],
            'comentario' => $comentario,
            'data_criacao' => date('d M Y, H:i')
        ]
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Erro ao salvar comentário']);
}
?>
