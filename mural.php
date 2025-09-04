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

    // Funções para likes e comentários
    $('.like-btn').on('click', function(e) {
        e.preventDefault();
        const btn = $(this);
        const recadoId = btn.data('recado-id');

        $.ajax({
            url: 'like.php',
            type: 'POST',
            data: { recado_id: recadoId },
            dataType: 'json',
            xhrFields: {
                withCredentials: true
            },
            success: function(response) {
                if (response.success) {
                    btn.find('.like-count').text(response.total_likes);
                    if (response.liked) {
                        btn.addClass('liked');
                    } else {
                        btn.removeClass('liked');
                    }
                } else {
                    alert('Erro: ' + response.message);
                }
            },
            error: function(xhr, status, error) {
                console.log('Erro AJAX:', xhr.responseText, status, error);
                alert('Erro ao processar like: ' + error);
            }
        });
    });

    $('.comment-btn').on('click', function(e) {
        e.preventDefault();
        const btn = $(this);
        const recadoId = btn.data('recado-id');
        const commentForm = btn.closest('.tweet-card').find('.comment-form');

        commentForm.slideToggle();
    });

    $('.add-comment-form').on('submit', function(e) {
        e.preventDefault();
        const form = $(this);
        const recadoId = form.data('recado-id');
        const comentario = form.find('textarea[name="comentario"]').val();
        const submitBtn = form.find('.comment-submit-btn');

        submitBtn.prop('disabled', true).text('Enviando...');

        $.ajax({
            url: 'comment.php',
            type: 'POST',
            data: {
                recado_id: recadoId,
                comentario: comentario
            },
            dataType: 'json',
            xhrFields: {
                withCredentials: true
            },
            success: function(response) {
                if (response.success) {
                    // Adicionar comentário à lista
                    const commentsSection = form.closest('.tweet-card').find('.comments-section');
                    if (commentsSection.length === 0) {
                        // Criar seção de comentários se não existir
                        form.closest('.tweet-card').find('.tweet-actions').after('<div class="comments-section"></div>');
                    }

                    const newComment = `
                        <div class="comment">
                            <div class="comment-avatar">${response.comment.nome}</div>
                            <div class="comment-content">
                                <div class="comment-header">
                                    <span class="comment-author">${response.comment.nome}</span>
                                    <span class="comment-date">${response.comment.data_criacao}</span>
                                </div>
                                <p>${response.comment.comentario.replace(/\n/g, '<br>')}</p>
                            </div>
                        </div>
                    `;

                    form.closest('.tweet-card').find('.comments-section').append(newComment);

                    // Atualizar contador de comentários
                    const commentBtn = form.closest('.tweet-card').find('.comment-btn .comment-count');
                    commentBtn.text(parseInt(commentBtn.text()) + 1);

                    // Limpar formulário e esconder
                    form.find('textarea').val('');
                    form.closest('.comment-form').slideUp();
                } else {
                    alert('Erro: ' + response.message);
                }
            },
            error: function(xhr, status, error) {
                console.log('Erro AJAX comentário:', xhr.responseText, status, error);
                alert('Erro ao enviar comentário: ' + error);
            },
            complete: function() {
                submitBtn.prop('disabled', false).text('Comentar');
            }
        });
    });
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
            <a href="logout.php" style="margin-left: 10px; color: black;">Sair</a>
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
                    $recado_id = $res['id'];
                    $data_formatada = date('d M Y, H:i', strtotime($res['data_criacao']));
                    $iniciais = strtoupper(substr($res['nome'], 0, 2));

                    // Buscar contagem de likes
                    $query_likes = "SELECT COUNT(*) as total FROM likes WHERE recado_id = $recado_id";
                    $result_likes = mysqli_query($conexao, $query_likes);
                    $likes_data = mysqli_fetch_assoc($result_likes);
                    $total_likes = $likes_data['total'];

                    // Verificar se usuário logado deu like
                    $liked = false;
                    if (isset($_SESSION['usuario_id'])) {
                        $usuario_id = $_SESSION['usuario_id'];
                        $query_user_like = "SELECT id FROM likes WHERE recado_id = $recado_id AND usuario_id = $usuario_id";
                        $result_user_like = mysqli_query($conexao, $query_user_like);
                        $liked = mysqli_num_rows($result_user_like) > 0;
                    }

                    // Buscar comentários
                    $query_comentarios = "SELECT c.*, u.nome FROM comentarios c JOIN usuarios u ON c.usuario_id = u.id WHERE c.recado_id = $recado_id ORDER BY c.data_criacao ASC";
                    $result_comentarios = mysqli_query($conexao, $query_comentarios);
                    $comentarios = [];
                    while ($comentario = mysqli_fetch_assoc($result_comentarios)) {
                        $comentarios[] = $comentario;
                    }
                    $total_comentarios = count($comentarios);

                    echo '<div class="tweet-card" data-recado-id="' . $recado_id . '">';
                    echo '<div class="tweet-header">';
                    // Show profile image if available, else initials
                    $query_user = "SELECT profile_image FROM usuarios WHERE id = " . intval($res['usuario_id']);
                    $result_user = mysqli_query($conexao, $query_user);
                    $user_data = mysqli_fetch_assoc($result_user);
                    $profile_image = $user_data['profile_image'];

                    if (!empty($profile_image) && file_exists('uploads/' . $profile_image)) {
                        echo '<div class="tweet-avatar"><img src="uploads/' . htmlspecialchars($profile_image) . '" alt="Avatar" style="width: 40px; height: 40px; border-radius: 20px;"></div>';
                    } else {
                        echo '<div class="tweet-avatar">' . $iniciais . '</div>';
                    }
                    echo '<div class="tweet-user-info">';
                    echo '<div class="tweet-name">' . htmlspecialchars($res['nome']) . '</div>';
                    echo '<div class="tweet-handle">@' . htmlspecialchars(explode('@', $res['email'])[0]) . '</div>';
                    echo '</div>';
                    echo '</div>';
                    echo '<div class="tweet-content">';
                    echo '<p>' . nl2br(htmlspecialchars($res['mensagem'])) . '</p>';
                    echo '<div class="tweet-date-bottom">' . $data_formatada . '</div>';
                    echo '</div>';
                    echo '<div class="tweet-actions">';
                    echo '<button class="tweet-action-btn like-btn ' . ($liked ? 'liked' : '') . '" data-recado-id="' . $recado_id . '"><i class="heart-icon">❤️</i> <span class="like-count">' . $total_likes . '</span></button>';
                    echo '<button class="tweet-action-btn comment-btn" data-recado-id="' . $recado_id . '"><i class="reply-icon">💬</i> <span class="comment-count">' . $total_comentarios . '</span></button>';
                    echo '<button class="tweet-action-btn"><i class="share-icon">🔗</i></button>';
                    echo '</div>';

                    // Exibir comentários
                    if (!empty($comentarios)) {
                        echo '<div class="comments-section">';
                        foreach ($comentarios as $comentario) {
                            $comentario_data = date('d M Y, H:i', strtotime($comentario['data_criacao']));
                            $nome_comentario = htmlspecialchars($comentario['nome']);
                            echo '<div class="comment">';
                            // Show profile image for comment author if available, else full name
                            $query_comment_user = "SELECT profile_image FROM usuarios WHERE nome = '" . mysqli_real_escape_string($conexao, $comentario['nome']) . "' LIMIT 1";
                            $result_comment_user = mysqli_query($conexao, $query_comment_user);
                            $comment_user_data = mysqli_fetch_assoc($result_comment_user);
                            $comment_profile_image = $comment_user_data['profile_image'];

                            echo '<div class="comment-avatar">';
                            if (!empty($comment_profile_image) && file_exists('uploads/' . $comment_profile_image)) {
                                echo '<img src="uploads/' . htmlspecialchars($comment_profile_image) . '" alt="Avatar" style="width: 30px; height: 30px; border-radius: 15px;">';
                            } else {
                                echo htmlspecialchars($nome_comentario);
                            }
                            echo '</div>';
                            echo '<div class="comment-content">';
                            echo '<div class="comment-header">';
                            echo '<span class="comment-author">' . $nome_comentario . '</span>';
                            echo '<span class="comment-date">' . $comentario_data . '</span>';
                            echo '</div>';
                            echo '<p>' . nl2br(htmlspecialchars($comentario['comentario'])) . '</p>';
                            echo '</div>';
                            echo '</div>';
                        }
                        echo '</div>';
                    }

                    // Formulário para adicionar comentário
                    echo '<div class="comment-form" style="display: none;">';
                    echo '<form class="add-comment-form" data-recado-id="' . $recado_id . '">';
                    echo '<div class="comment-input-group">';
                    echo '<textarea name="comentario" placeholder="Escreva seu comentário..." maxlength="200" required></textarea>';
                    echo '<button type="submit" class="btn comment-submit-btn">Comentar</button>';
                    echo '</div>';
                    echo '</form>';
                    echo '</div>';

                    echo '</div>';
                }
            } else {
                echo '<p>Nenhuma mensagem publicada ainda.</p>';
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
