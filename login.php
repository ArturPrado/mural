<?php
session_start();
include 'conexao.php';

$erro = '';
$sucesso = '';

// Verificar se o formulário foi enviado
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $usuario = mysqli_real_escape_string($conexao, $_POST['usuario']);
    $senha = mysqli_real_escape_string($conexao, $_POST['senha']);
    
    // Verificar se os campos estão preenchidos
    if (empty($usuario) || empty($senha)) {
        $erro = 'Por favor, preencha todos os campos.';
    } else {
        // Consultar o banco de dados para verificar o usuário
        $query = "SELECT * FROM mural.usuarios WHERE nome = '$usuario' OR email = '$usuario'";
        $resultado = mysqli_query($conexao, $query);
        
        if ($resultado && mysqli_num_rows($resultado) > 0) {
            $usuario_data = mysqli_fetch_assoc($resultado);
            
            // Verificar a senha (assumindo que está em texto plano - em produção usar password_hash)
            if ($senha == $usuario_data['senha']) {
                // Login bem-sucedido
                $_SESSION['usuario_id'] = $usuario_data['id'];
                $_SESSION['usuario_nome'] = $usuario_data['nome'];
                $_SESSION['usuario_logado'] = true;
                
                // Redirecionar para a página mural
                header('Location: pages/mural.php');
                exit();
            } else {
                $erro = 'Senha incorreta.';
            }
        } else {
            $erro = 'Usuário não encontrado.';
        }
    }
}

// Verificar se já está logado
if (isset($_SESSION['usuario_logado']) && $_SESSION['usuario_logado'] === true) {
    header('Location: pages/mural.php');
    exit();
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
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <h1>Sistema Mural</h1>
            <p>Faça login para continuar</p>
        </div>
        
        <?php if (!empty($erro)): ?>
            <div class="mensagem erro"><?php echo $erro; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($sucesso)): ?>
            <div class="mensagem sucesso"><?php echo $sucesso; ?></div>
        <?php endif; ?>
        
        <form id="loginForm" method="POST" action="">
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
                }
            });
        });
    </script>
</body>
</html>
