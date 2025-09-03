<?php
session_start();
include "conexao.php";

// Verificar se o usuário está logado
if (!isset($_SESSION['usuario_logado']) || $_SESSION['usuario_logado'] !== true) {
    header('Location: login.php');
    exit();
}

// Atualizar recado
if(isset($_POST['atualiza'])){
    $idatualiza = intval($_POST['id']);
    $nome       = mysqli_real_escape_string($conexao, $_POST['nome']);
    $email      = mysqli_real_escape_string($conexao, $_POST['email']);
    $msg        = mysqli_real_escape_string($conexao, $_POST['msg']);

    $sql = "UPDATE recados SET nome='$nome', email='$email', mensagem='$msg' WHERE id=$idatualiza";
    mysqli_query($conexao, $sql) or die("Erro ao atualizar: " . mysqli_error($conexao));
    header("Location: moderar.php");
    exit;
}

// Excluir recado
if(isset($_GET['acao']) && $_GET['acao'] == 'excluir'){
    $id = intval($_GET['id']);
    mysqli_query($conexao, "DELETE FROM recados WHERE id=$id") or die("Erro ao deletar: " . mysqli_error($conexao));
    header("Location: moderar.php");
    exit;
}

// Editar recado
$editar_id = isset($_GET['acao']) && $_GET['acao'] == 'editar' ? intval($_GET['id']) : 0;
$recado_editar = null;
if($editar_id){
    $res = mysqli_query($conexao, "SELECT * FROM recados WHERE id=$editar_id");
    $recado_editar = mysqli_fetch_assoc($res);
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="utf-8"/>
<title>Moderar pedidos</title>
<link rel="stylesheet" href="style.css"/>
</head>
<body>
<!-- Navegação -->
<nav class="navbar">
    <div class="container">
        <a href="mural.php" class="logo">Sistema Mural</a>
        <ul class="nav-links">
            <li><a href="mural.php">Mural</a></li>
            <li><a href="profile.php">Perfil</a></li>
            <li><a href="moderar.php">Moderar</a></li>
        </ul>
        <div class="user-info">
            <span>Olá, <?php echo htmlspecialchars($_SESSION['usuario_nome']); ?>!</span>
            <a href="logout.php" style="margin-left: 10px; color: #fff;">Sair</a>
        </div>
    </div>
</nav>

<!-- Conteúdo principal -->
<div class="main-content">
    <div class="container">
        <div class="page-header">
            <h1>Moderar Pedidos</h1>
            <p>Gerencie as mensagens do mural</p>
        </div>

        <?php if($recado_editar): ?>
        <div class="card">
            <h2>Editar Mensagem</h2>
            <form method="post">
                <div class="form-group">
                    <label for="nome">Nome:</label>
                    <input type="text" id="nome" name="nome" value="<?php echo htmlspecialchars($recado_editar['nome']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($recado_editar['email']); ?>" required>
                </div>
                <div class="form-group">
                    <label for="msg">Mensagem:</label>
                    <textarea id="msg" name="msg" required><?php echo htmlspecialchars($recado_editar['mensagem']); ?></textarea>
                </div>
                <input type="hidden" name="id" value="<?php echo $recado_editar['id']; ?>"/>
                <button type="submit" name="atualiza" class="btn">Modificar Recado</button>
            </form>
        </div>
        <?php endif; ?>

        <div class="card">
            <h2>Todas as Mensagens</h2>
            <?php
            $seleciona = mysqli_query($conexao, "SELECT * FROM recados ORDER BY id DESC");
            if(mysqli_num_rows($seleciona) <= 0){
                echo "<p>Nenhum pedido no mural!</p>";
            }else{
                while($res = mysqli_fetch_assoc($seleciona)){
                    echo '<ul class="recados">';
                    echo '<li><strong>ID:</strong> ' . $res['id'] . ' | ';
                    echo '<div class="action-links">';
                    echo '<a href="moderar.php?acao=excluir&id=' . $res['id'] . '">Remover</a> | ';
                    echo '<a href="moderar.php?acao=editar&id=' . $res['id'] . '">Modificar</a>';
                    echo '</div></li>';
                    echo '<li><strong>Nome:</strong> ' . htmlspecialchars($res['nome']) . '</li>';
                    echo '<li><strong>Email:</strong> ' . htmlspecialchars($res['email']) . '</li>';
                    echo '<li><strong>Mensagem:</strong> ' . nl2br(htmlspecialchars($res['mensagem'])) . '</li>';
                    echo '</ul>';
                }
            }
            ?>
        </div>
    </div>
</div>

<!-- Footer -->
<div class="footer">
    <p>&copy; 2024 Sistema Mural. Todos os direitos reservados.</p>
</div>
</body>
</html>
