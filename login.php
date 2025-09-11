<?php
session_start();
include 'conexao.php';

$erro = '';
$sucesso = '';
$action = isset($_GET['action']) ? $_GET['action'] : 'login';

// Verificar se já está logado
if (isset($_SESSION['usuario_logado']) && $_SESSION['usuario_logado'] === true) {
    header('Location: mural.php');
    exit();
}

// Lidar com formulário de login
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'])) {
    $usuario = mysqli_real_escape_string($conexao, $_POST['usuario']);
    $senha = mysqli_real_escape_string($conexao, $_POST['senha']);

    if (empty($usuario) || empty($senha)) {
        $erro = 'Por favor, preencha todos os campos.';
    } else {
        $query = "SELECT * FROM mural.usuarios WHERE nome = '$usuario' OR email = '$usuario'";
        $resultado = mysqli_query($conexao, $query);

        if ($resultado && mysqli_num_rows($resultado) > 0) {
            $usuario_data = mysqli_fetch_assoc($resultado);

            if (password_verify($senha, $usuario_data['senha'])) {
                $_SESSION['usuario_id'] = $usuario_data['id'];
                $_SESSION['usuario_nome'] = $usuario_data['nome'];
                $_SESSION['usuario_logado'] = true;

                header('Location: mural.php');
                exit();
            } else {
                $erro = 'Senha incorreta.';
            }
        } else {
            $erro = 'Usuário não encontrado.';
        }
    }
}

// Lidar com formulário de esqueci senha
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['forgot'])) {
    $email = mysqli_real_escape_string($conexao, trim($_POST['email_forgot']));

    if (empty($email)) {
        $erro = 'Por favor, digite seu email.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = 'Email inválido.';
    } else {
        $query = "SELECT id, nome FROM mural.usuarios WHERE email = '$email'";
        $resultado = mysqli_query($conexao, $query);

        if ($resultado && mysqli_num_rows($resultado) > 0) {
            $usuario_data = mysqli_fetch_assoc($resultado);
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

            $update_query = "UPDATE mural.usuarios SET reset_token = '$token', reset_expires = '$expires' WHERE id = {$usuario_data['id']}";
            mysqli_query($conexao, $update_query);

            $reset_link = "http://localhost/NovoProjetoArtur/login.php?action=reset&token=$token";
            $subject = 'Redefinição de Senha - Sistema Mural';
            $message = "Olá {$usuario_data['nome']},\n\nClique no link abaixo para redefinir sua senha:\n$reset_link\n\nEste link expira em 1 hora.\n\nSe você não solicitou esta redefinição, ignore este email.";
            $headers = 'From: noreply@sistemamural.com';

            // Configuração SMTP para XAMPP com STARTTLS
            ini_set('SMTP', 'smtp.gmail.com');
            ini_set('smtp_port', 587);
            ini_set('smtp_crypto', 'tls');
            ini_set('sendmail_from', 'turx360cu@gmail.com'); // Substitua pelo seu email Gmail

            if (mail($email, $subject, $message, $headers)) {
                $sucesso = 'Um link de redefinição foi enviado para seu email.';
            } else {
                $erro = 'Erro ao enviar email. Tente novamente.';
            }
        } else {
            $erro = 'Email não encontrado.';
        }
    }
}

// Lidar com formulário de reset senha
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['reset'])) {
    $token = mysqli_real_escape_string($conexao, $_POST['token']);
    $nova_senha = $_POST['nova_senha'];
    $confirmar_senha = $_POST['confirmar_senha'];

    if (empty($nova_senha) || empty($confirmar_senha)) {
        $erro = 'Por favor, preencha todos os campos.';
    } elseif (strlen($nova_senha) < 6) {
        $erro = 'A senha deve ter pelo menos 6 caracteres.';
    } elseif ($nova_senha !== $confirmar_senha) {
        $erro = 'As senhas não coincidem.';
    } else {
        $query = "SELECT id FROM mural.usuarios WHERE reset_token = '$token' AND reset_expires > NOW()";
        $resultado = mysqli_query($conexao, $query);

        if ($resultado && mysqli_num_rows($resultado) > 0) {
            $usuario_data = mysqli_fetch_assoc($resultado);
            $senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);

            $update_query = "UPDATE mural.usuarios SET senha = '$senha_hash', reset_token = NULL, reset_expires = NULL WHERE id = {$usuario_data['id']}";
            mysqli_query($conexao, $update_query);

            $sucesso = 'Senha redefinida com sucesso! Faça login.';
            $action = 'login';
        } else {
            $erro = 'Token inválido ou expirado.';
        }
    }
}

// Verificar token para reset
if ($action == 'reset' && isset($_GET['token'])) {
    $token = mysqli_real_escape_string($conexao, $_GET['token']);
    $query = "SELECT id FROM mural.usuarios WHERE reset_token = '$token' AND reset_expires > NOW()";
    $resultado = mysqli_query($conexao, $query);

    if (!$resultado || mysqli_num_rows($resultado) == 0) {
        $erro = 'Token inválido ou expirado.';
        $action = 'login';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistema Mural</title>
    <link rel="stylesheet" href="style.css">
    <script src="scripts/jquery.js"></script>
    <script src="scripts/jquery.validate.js"></script>
    <style>
        .login-container {
            max-width: 400px;
            margin: 100px auto;
            padding: 30px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .login-header h1 {
            color: #4285f4;
            margin-bottom: 10px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #555;
        }
        
        .form-group input[type="text"],
        .form-group input[type="password"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            box-sizing: border-box;
        }
        
        .form-group input:focus {
            border-color: #4285f4;
            outline: none;
            box-shadow: 0 0 5px rgba(66, 133, 244, 0.3);
        }
        
        .btn-login {
            width: 100%;
            padding: 12px;
            background-color: #4285f4;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        
        .btn-login:hover {
            background-color: #357ae8;
        }
        
        .mensagem {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
            text-align: center;
        }
        
        .erro {
            background-color: #ffebee;
            color: #c62828;
            border: 1px solid #ffcdd2;
        }
        
        .sucesso {
            background-color: #e8f5e8;
            color: #2e7d32;
            border: 1px solid #c8e6c9;
        }

        .tab-btn {
            padding: 10px 20px;
            margin: 0 5px;
            background-color: #f1f1f1;
            border: 1px solid #ddd;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            transition: background-color 0.3s ease;
        }

        .tab-btn:hover {
            background-color: #e0e0e0;
        }

        .tab-btn.active {
            background-color: #4285f4;
            color: white;
            border-color: #4285f4;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>Sistema Mural</h1>
            <p>
                <?php if ($action == 'login'): ?>Faça login para continuar<?php elseif ($action == 'forgot'): ?>Esqueci minha senha<?php elseif ($action == 'reset'): ?>Redefinir senha<?php endif; ?>
            </p>
        </div>



        <?php if (!empty($erro)): ?>
            <div class="mensagem erro"><?php echo $erro; ?></div>
        <?php endif; ?>

        <?php if (!empty($sucesso)): ?>
            <div class="mensagem sucesso"><?php echo $sucesso; ?></div>
        <?php endif; ?>

        <?php if ($action == 'login'): ?>
            <form id="loginForm" method="POST" action="">
                <input type="hidden" name="login" value="1">
                <div class="form-group">
                    <label for="usuario">Usuário ou Email:</label>
                    <input type="text" id="usuario" name="usuario" required>
                </div>

                <div class="form-group">
                    <label for="senha">Senha:</label>
                    <input type="password" id="senha" name="senha" required>
                </div>

                <button type="submit" class="btn-login">Entrar</button>
            </form>

            <div style="text-align: center; margin-top: 20px;">
                <p><a href="login.php?action=forgot" style="color: #4285f4; text-decoration: none;">Esqueci minha senha</a></p>
                <p>Não tem uma conta? <a href="register.php" style="color: #4285f4; text-decoration: none;">Registrar-se</a></p>
            </div>

        <?php elseif ($action == 'forgot'): ?>
            <form id="forgotForm" method="POST" action="">
                <input type="hidden" name="forgot" value="1">
                <div class="form-group">
                    <label for="email_forgot">Email:</label>
                    <input type="email" id="email_forgot" name="email_forgot" required>
                </div>

                <button type="submit" class="btn-login">Enviar Link de Redefinição</button>
            </form>

            <div style="text-align: center; margin-top: 20px;">
                <p><a href="login.php?action=login" style="color: #4285f4; text-decoration: none;">Voltar ao Login</a></p>
            </div>

        <?php elseif ($action == 'reset' && isset($_GET['token'])): ?>
            <form id="resetForm" method="POST" action="">
                <input type="hidden" name="reset" value="1">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($_GET['token']); ?>">
                <div class="form-group">
                    <label for="nova_senha">Nova Senha:</label>
                    <input type="password" id="nova_senha" name="nova_senha" required>
                </div>

                <div class="form-group">
                    <label for="confirmar_senha">Confirmar Nova Senha:</label>
                    <input type="password" id="confirmar_senha" name="confirmar_senha" required>
                </div>

                <button type="submit" class="btn-login">Redefinir Senha</button>
            </form>

            <div style="text-align: center; margin-top: 20px;">
                <p><a href="login.php?action=login" style="color: #4285f4; text-decoration: none;">Voltar ao Login</a></p>
            </div>
        <?php endif; ?>
    </div>

    <script>
        $(document).ready(function() {
            $('#loginForm').validate({
                rules: {
                    usuario: {
                        required: true,
                        minlength: 3
                    },
                    senha: {
                        required: true,
                        minlength: 6
                    }
                },
                messages: {
                    usuario: {
                        required: "Por favor, digite seu usuário ou email",
                        minlength: "O usuário deve ter pelo menos 3 caracteres"
                    },
                    senha: {
                        required: "Por favor, digite sua senha",
                        minlength: "A senha deve ter pelo menos 6 caracteres"
                    }
                },
                errorClass: "erro",
                errorElement: "span",
                highlight: function(element, errorClass) {
                    $(element).addClass('erro').css('border-color', '#c62828');
                },
                unhighlight: function(element, errorClass) {
                    $(element).removeClass('erro').css('border-color', '#ddd');
                },
                submitHandler: function(form) {
                    // Verificações adicionais antes do envio
                    var usuario = $('#usuario').val();
                    var senha = $('#senha').val();

                    // Verificar se os campos não estão vazios
                    if (!usuario.trim() || !senha.trim()) {
                        alert('Por favor, preencha todos os campos.');
                        return false;
                    }

                    // Verificar formato básico de email se parece ser um email
                    if (usuario.includes('@') && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(usuario)) {
                        alert('Digite um email válido.');
                        return false;
                    }

                    form.submit();
                }
            });
        });
    </script>
</body>
</html>
