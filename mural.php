<?php
session_start();
include "conexao.php";

// Verificar se o usuário está logado
if (!isset($_SESSION['usuario_logado']) || $_SESSION['usuario_logado'] !== true) {
    header('Location: login.php');
    exit();
}

// Inserir novo pedido/recado
if(isset($_POST['cadastra'])){
    $nome  = mysqli_real_escape_string($conexao, $_POST['nome']);
    $msg   = mysqli_real_escape_string($conexao, $_POST['msg']);
    $usuario_id = $_SESSION['usuario_id'];

    // Buscar email do usuário logado
    $query_email = "SELECT email FROM usuarios WHERE id = $usuario_id";
    $resultado_email = mysqli_query($conexao, $query_email);
    $usuario_data = mysqli_fetch_assoc($resultado_email);
    $email = $usuario_data['email'];

    $sql = "INSERT INTO recados (nome, email, mensagem, usuario_id) VALUES ('$nome', '$email', '$msg', $usuario_id)";
    mysqli_query($conexao, $sql) or die("Erro ao inserir dados: " . mysqli_error($conexao));
    header("Location: mural.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
<meta charset="utf-8"/>
<title>Mural de pedidos</title>
<link rel="stylesheet" href="style.css"/>
<script src="scripts/jquery.js"></script>
<script src="scripts/jquery.validate.js"></script>
<script>
$(document).ready(function() {
    // Validação do formulário
    $("#mural").validate({
        rules: {
            nome: { required: true, minlength: 4 },
            msg: { required: true, minlength: 10, maxlength: 280 }
        },
        messages: {
            nome: { required: "Digite o seu nome", minlength: "O nome deve ter no mínimo 4 caracteres" },
            msg: {
                required: "Digite sua mensagem",
                minlength: "A mensagem deve ter no mínimo 10 caracteres",
                maxlength: "A mensagem deve ter no máximo 280 caracteres"
            }
        }
    });

    // Contador de caracteres
    const textarea = $('#msg');
    const charCount = $('#charCount');
    const tweetBtn = $('.tweet-btn');

    function updateCharCount() {
        const length = textarea.val().length;
        charCount.text(length);

        // Remove classes anteriores
        charCount.removeClass('warning danger');

        // Adiciona classes baseadas no comprimento
        if (length > 250) {
            charCount.addClass('warning');
        }
        if (length > 270) {
            charCount.addClass('danger');
        }

        // Desabilita botão se exceder limite
        if (length > 280) {
            tweetBtn.prop('disabled', true);
        } else {
            tweetBtn.prop('disabled', false);
        }
    }

    // Atualiza contador em tempo real
    textarea.on('input', updateCharCount);

    // Inicializa contador
    updateCharCount();
});
</script>
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
            <h1>Mural de Pedidos</h1>
            <p>Compartilhe suas mensagens e ideias</p>
        </div>

        <div class="card tweet-compose">
            <h2>💬 O que você está pensando?</h2>
            <form id="mural" method="post">
                <div class="compose-header">
                    <div class="compose-avatar">
                        <?php echo strtoupper(substr($_SESSION['usuario_nome'], 0, 2)); ?>
                    </div>
                    <div class="compose-input">
                        <input type="text" id="nome" name="nome" value="<?php echo htmlspecialchars($_SESSION['usuario_nome']); ?>" style="display: none;">
                        <textarea id="msg" name="msg" placeholder="Compartilhe suas ideias..." required maxlength="280"></textarea>
                        <div class="compose-footer">
                            <div class="char-count">
                                <span id="charCount">0</span>/280
                            </div>
                            <button type="submit" name="cadastra" class="btn tweet-btn">Publicar</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <div class="card">
            <h2>Mensagens Publicadas</h2>
            <?php
            $seleciona = mysqli_query($conexao, "SELECT * FROM recados ORDER BY id DESC");
            if(mysqli_num_rows($seleciona) > 0){
                while($res = mysqli_fetch_assoc($seleciona)){
                    $data_formatada = date('d M Y, H:i', strtotime($res['data_criacao']));
                    $iniciais = strtoupper(substr($res['nome'], 0, 2));
                    echo '<div class="tweet-card">';
                    echo '<div class="tweet-header">';
                    echo '<div class="tweet-avatar">' . $iniciais . '</div>';
                    echo '<div class="tweet-user-info">';
                    echo '<div class="tweet-name">' . htmlspecialchars($res['nome']) . '</div>';
                    echo '<div class="tweet-handle">@' . htmlspecialchars(explode('@', $res['email'])[0]) . '</div>';
                    echo '</div>';
                    echo '<div class="tweet-date">' . $data_formatada . '</div>';
                    echo '</div>';
                    echo '<div class="tweet-content">';
                    echo '<p>' . nl2br(htmlspecialchars($res['mensagem'])) . '</p>';
                    echo '</div>';
                    echo '<div class="tweet-actions">';
                    echo '<button class="tweet-action-btn"><i class="heart-icon">❤️</i> 0</button>';
                    echo '<button class="tweet-action-btn"><i class="reply-icon">💬</i> 0</button>';
                    echo '<button class="tweet-action-btn"><i class="share-icon">🔗</i></button>';
                    echo '</div>';
                    echo '</div>';
                }
            } else {
                echo '<div class="no-tweets">';
                echo '<p>📝 Nenhuma mensagem publicada ainda.</p>';
                echo '<p>Seja o primeiro a compartilhar algo!</p>';
                echo '</div>';
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
