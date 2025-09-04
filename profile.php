<?php
session_start();
include 'conexao.php';

// Verificar se o usuário está logado
if (!isset($_SESSION['usuario_logado']) || $_SESSION['usuario_logado'] !== true) {
    header('Location: login.php');
    exit();
}

$erro = '';
$sucesso = '';
$usuario_id = $_SESSION['usuario_id'];

// Buscar dados do usuário
$query = "SELECT nome, email, profile_image FROM usuarios WHERE id = $usuario_id";
$resultado = mysqli_query($conexao, $query);
$usuario = mysqli_fetch_assoc($resultado);

// Atualizar dados do usuário
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nome = mysqli_real_escape_string($conexao, trim($_POST['nome']));
    $email = mysqli_real_escape_string($conexao, trim($_POST['email']));
    $senha_atual = $_POST['senha_atual'];
    $nova_senha = $_POST['nova_senha'];
    $confirmar_senha = $_POST['confirmar_senha'];

    // Validar campos obrigatórios
    if (empty($nome) || empty($email)) {
        $erro = 'Nome e email são obrigatórios.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = 'Email inválido.';
    } else {
        // Verificar se email já está em uso por outro usuário
        $query_email = "SELECT id FROM usuarios WHERE email = '$email' AND id != $usuario_id";
        $res_email = mysqli_query($conexao, $query_email);
        if (mysqli_num_rows($res_email) > 0) {
            $erro = 'Este email já está em uso por outro usuário.';
        } else {
            // Atualizar nome e email
            $update_query = "UPDATE usuarios SET nome = '$nome', email = '$email' WHERE id = $usuario_id";
            if (!mysqli_query($conexao, $update_query)) {
                $erro = 'Erro ao atualizar dados.';
            } else {
                // Atualizar senha se fornecida
                if (!empty($senha_atual) || !empty($nova_senha) || !empty($confirmar_senha)) {
                    if (empty($senha_atual) || empty($nova_senha) || empty($confirmar_senha)) {
                        $erro = 'Para alterar a senha, preencha todos os campos de senha.';
                    } elseif ($nova_senha !== $confirmar_senha) {
                        $erro = 'A nova senha e a confirmação não coincidem.';
                    } elseif (strlen($nova_senha) < 6) {
                        $erro = 'A nova senha deve ter pelo menos 6 caracteres.';
                    } else {
                        // Verificar senha atual
                        $query_senha = "SELECT senha FROM usuarios WHERE id = $usuario_id";
                        $res_senha = mysqli_query($conexao, $query_senha);
                        $row = mysqli_fetch_assoc($res_senha);
                        if (!password_verify($senha_atual, $row['senha'])) {
                            $erro = 'Senha atual incorreta.';
                        } else {
                            // Atualizar senha
                            $nova_senha_hash = password_hash($nova_senha, PASSWORD_DEFAULT);
                            $update_senha = "UPDATE usuarios SET senha = '$nova_senha_hash' WHERE id = $usuario_id";
                            if (!mysqli_query($conexao, $update_senha)) {
                                $erro = 'Erro ao atualizar a senha.';
                            } else {
                                $sucesso = 'Dados e senha atualizados com sucesso.';
                            }
                        }
                    }
                } else {
                    $sucesso = 'Dados atualizados com sucesso.';
                }

                // Handle profile image upload
                if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
                    $allowed = ['jpg', 'jpeg', 'png', 'gif'];
                    $filename = $_FILES['profile_image']['name'];
                    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                    if (in_array($ext, $allowed)) {
                        $new_filename = $usuario_id . '_' . time() . '.' . $ext;
                        $upload_path = 'uploads/' . $new_filename;
                        if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $upload_path)) {
                            $update_image = "UPDATE usuarios SET profile_image = '$new_filename' WHERE id = $usuario_id";
                            mysqli_query($conexao, $update_image);
                            $sucesso .= ' Imagem de perfil atualizada com sucesso.';
                        } else {
                            $erro = 'Erro ao fazer upload da imagem.';
                        }
                    } else {
                        $erro = 'Tipo de arquivo não permitido. Use apenas JPG, PNG ou GIF.';
                    }
                }
            }
        }
    }
    // Atualizar dados para exibir no formulário
    $query = "SELECT nome, email, profile_image FROM usuarios WHERE id = $usuario_id";
    $resultado = mysqli_query($conexao, $query);
    $usuario = mysqli_fetch_assoc($resultado);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Perfil do Usuário - Sistema Mural</title>
    <link rel="stylesheet" href="style.css" />
    <script src="scripts/jquery.js"></script>
    <script src="scripts/jquery.validate.js"></script>
    <style>
        .profile-container {
            max-width: 500px;
            margin: 50px auto;
            padding: 30px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .profile-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .profile-header h1 {
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
        .btn-save {
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
        .btn-save:hover {
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
        .logout-link {
            text-align: center;
            margin-top: 20px;
        }
        .logout-link a {
            color: #4285f4;
            text-decoration: none;
        }
        .logout-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="profile-container">
        <div class="profile-header">
            <h1>Perfil do Usuário</h1>
            <p>Atualize seus dados e senha</p>
        </div>

        <?php if (!empty($erro)): ?>
            <div class="mensagem erro"><?php echo $erro; ?></div>
        <?php endif; ?>

        <?php if (!empty($sucesso)): ?>
            <div class="mensagem sucesso"><?php echo $sucesso; ?></div>
        <?php endif; ?>

        <form id="profileForm" method="POST" action="" enctype="multipart/form-data">
            <div class="form-group">
                <label for="nome">Nome:</label>
                <input type="text" id="nome" name="nome" value="<?php echo htmlspecialchars($usuario['nome']); ?>" required>
            </div>

            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($usuario['email']); ?>" required>
            </div>

            <div class="form-group">
                <label for="profile_image">Imagem de Perfil:</label>
                <input type="file" id="profile_image" name="profile_image" accept="image/*">
                <?php if (!empty($usuario['profile_image'])): ?>
                    <p>Imagem atual: <img src="uploads/<?php echo htmlspecialchars($usuario['profile_image']); ?>" alt="Imagem de perfil" style="width: 50px; height: 50px; border-radius: 50%;"></p>
                <?php endif; ?>
            </div>

            <hr>

            <div class="form-group">
                <label for="senha_atual">Senha Atual:</label>
                <input type="password" id="senha_atual" name="senha_atual" placeholder="Deixe em branco para não alterar">
            </div>

            <div class="form-group">
                <label for="nova_senha">Nova Senha:</label>
                <input type="password" id="nova_senha" name="nova_senha" placeholder="Deixe em branco para não alterar">
            </div>

            <div class="form-group">
                <label for="confirmar_senha">Confirmar Nova Senha:</label>
                <input type="password" id="confirmar_senha" name="confirmar_senha" placeholder="Deixe em branco para não alterar">
            </div>

            <button type="submit" class="btn-save">Salvar Alterações</button>
        </form>

        <div class="logout-link">
            <p><a href="logout.php">Sair</a></p>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            $('#profileForm').validate({
                rules: {
                    nome: {
                        required: true,
                        minlength: 2
                    },
                    email: {
                        required: true,
                        email: true
                    },
                    nova_senha: {
                        minlength: 6
                    },
                    confirmar_senha: {
                        equalTo: "#nova_senha"
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
                    nova_senha: {
                        minlength: "A nova senha deve ter pelo menos 6 caracteres"
                    },
                    confirmar_senha: {
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
