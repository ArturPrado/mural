<?php
session_start();
include 'conexao.php';

// Verificar se o usuário está logado
if (!isset($_SESSION['usuario_logado']) || $_SESSION['usuario_logado'] !== true) {
    header('Content-Type: application/json');
    echo json_encode(['exists' => false]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = mysqli_real_escape_string($conexao, trim($_POST['email']));
    $usuario_id = intval($_POST['usuario_id']);

    // Verificar se email já está em uso por outro usuário
    $query = "SELECT id FROM usuarios WHERE email = '$email' AND id != $usuario_id";
    $resultado = mysqli_query($conexao, $query);

    $exists = mysqli_num_rows($resultado) > 0;

    header('Content-Type: application/json');
    echo json_encode(['exists' => $exists]);
} else {
    header('Content-Type: application/json');
    echo json_encode(['exists' => false]);
}
?>
