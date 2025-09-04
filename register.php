<?php
session_start();
include 'conexao.php';

$erro = '';
$sucesso = '';

// Verificar se o formulário foi enviado
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nome = mysqli_real_escape_string($conexao, trim($_POST['nome']));
    $email = mysqli_real_escape_string($conexao, trim($_POST['email']));
    $senha = $_POST['senha'];
    $confirmar_senha = $_POST['confirmar_senha'];

    // Validações
    if (empty($nome) || empty($email) || empty($senha) || empty($confirmar_senha)) {
        $erro = 'Todos os campos são obrigatórios.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = 'Email inválido.';
    } elseif (strlen($senha) < 6) {
        $erro = 'A senha deve ter pelo menos 6 caracteres.';
    } elseif ($senha !== $confirmar_senha) {
        $erro = 'As senhas não coincidem.';
    } else {
        // Verificar se email já existe
        $query = "SELECT id FROM usuarios WHERE email = '$email'";
        $resultado = mysqli_query($conexao, $query);

        if (mysqli_num_rows($resultado) > 0) {
            $erro = 'Este email já está cadastrado.';
        } else {
            // Hash da senha
            $senha_hash = password_hash($senha, PASSWORD_DEFAULT);

            // Inserir novo usuário
            $query = "INSERT INTO usuarios (nome, email, senha) VALUES ('$nome', '$email', '$senha_hash')";

            if (mysqli_query($conexao, $query)) {
                $sucesso = 'Conta criada com sucesso! Faça login.';
                
            } else {
                $erro = 'Erro ao criar conta. Tente novamente.';
            }
        }
    }
}

// Verificar se já está logado
if (isset($_SESSION['usuario_logado']) && $_SESSION['usuario_logado'] === true) {
    header('Location: mural.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrar - Sistema Mural</title>
    <link rel="stylesheet" href="style.css">
    <script src="scripts/jquery.js"></script>
    <script src="scripts/jquery.validate.js"></script>
    <style>
        .register-container {
            max-width: 400px;
            margin: 50px auto;
            padding: 30px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }

        .register-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .register-header h1 {
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
        .form-group input[type="email"],
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

        .btn-register {
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

        .btn-register:hover {
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

        .login-link {
            text-align: center;
            margin-top: 20px;
        }

        .login-link a {
            color: #4285f4;
            text-decoration: none;
        }

        .login-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-header">
            <h1>Sistema Mural</h1>
            <p>Criar nova conta</p>
        </div>

        <?php if (!empty($erro)): ?>
            <div class="mensagem erro"><?php echo $erro; ?></div>
        <?php endif; ?>

        <?php if (!empty($sucesso)): ?>
            <div class="mensagem sucesso"><?php echo $sucesso; ?></div>
        <?php endif; ?>

        <form id="registerForm" method="POST" action="">
            <div class="form-group">
                <label for="nome">Nome:</label>
                <input type="text" id="nome" name="nome" required>
            </div>

            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required>
            </div>

            <div class="form-group">
                <label for="senha">Senha:</label>
                <input type="password" id="senha" name="senha" required>
            </div>

            <div class="form-group">
                <label for="confirmar_senha">Confirmar Senha:</label>
                <input type="password" id="confirmar_senha" name="confirmar_senha" required>
            </div>

            <button type="submit" class="btn-register">Criar Conta</button>
        </form>

        <div class="login-link">
            <p>Já tem uma conta? <a href="login.php">Faça login</a></p>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            $('#registerForm').validate({
                rules: {
                    nome: {
                        required: true,
                        minlength: 2
                    },
                    email: {
                        required: true,
                        email: true
                    },
                    senha: {
                        required: true,
                        minlength: 6
                    },
                    confirmar_senha: {
                        required: true,
                        equalTo: "#senha"
                    }
                },
                messages: {
                    nome: {
                        required: "Por favor, digite seu nome",
                        minlength: "O nome deve ter pelo menos 2 caracteres"
                    },
                    email: {
                        required: "Por favor, digite seu email",
                        email: "Digite um email válido"
                    },
                    senha: {
                        required: "Por favor, digite sua senha",
                        minlength: "A senha deve ter pelo menos 6 caracteres"
                    },
                    confirmar_senha: {
                        required: "Por favor, confirme sua senha",
                        equalTo: "As senhas não coincidem"
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
