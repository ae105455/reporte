<?php
session_start();

$host = 'localhost';
$user = 'root';
$password = '';
$database = 'aureamed';

function sanitizar($dato) {
    $dato = trim($dato);
    $dato = stripslashes($dato);
    $dato = htmlspecialchars($dato, ENT_QUOTES, 'UTF-8');
    return $dato;
}

if(isset($_SESSION['usuario_id'])) {
    header("Location: panel.php");
    exit();
}

$error = '';

if($_SERVER["REQUEST_METHOD"] == "POST") {
    $usuario = isset($_POST['usuario']) ? sanitizar($_POST['usuario']) : '';
    $clave = isset($_POST['clave']) ? sanitizar($_POST['clave']) : '';
    
    if(empty($usuario) || empty($clave)) {
        $error = "Usuario y contraseña son obligatorios.";
    } else {
        $conn = new mysqli($host, $user, $password, $database);
        if ($conn->connect_error) {
            $error = "Error de conexión con la base de datos.";
        } else {
            $stmt = $conn->prepare("SELECT id_usuario, usuario, nombre, password, rol FROM usuarios WHERE usuario = ? AND estado = 'activo'");
            if($stmt) {
                $stmt->bind_param("s", $usuario);
                $stmt->execute();
                $resultado = $stmt->get_result();
                
                if($resultado->num_rows == 1) {
                    $usuario_data = $resultado->fetch_assoc();
                    if(md5($clave) == $usuario_data['password']) {
                        $_SESSION['usuario_id'] = $usuario_data['id_usuario'];
                        $_SESSION['usuario'] = $usuario_data['usuario'];
                        $_SESSION['nombre'] = $usuario_data['nombre'];
                        $_SESSION['rol'] = $usuario_data['rol'];
                        $stmt->close();
                        $conn->close();
                        header("Location: panel.php");
                        exit();
                    } else {
                        $error = "Contraseña incorrecta.";
                    }
                } else {
                    $error = "Usuario no encontrado o inactivo.";
                }
                $stmt->close();
            } else {
                $error = "Error al procesar la solicitud.";
            }
            $conn->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AureaMed | Iniciar Sesión</title>

    <link rel="stylesheet" href="./css/normalize.css">
    <link rel="stylesheet" href="./css/bootstrap.min.css">
    <link rel="stylesheet" href="./css/all.css">
    <link rel="stylesheet" href="./css/sweetalert2.min.css">
    <link rel="stylesheet" href="./css/style.css">

    <style>
        body {
            background: linear-gradient(135deg, #a83221 0%, #b8401e 50%, #c84028 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            padding: 20px;
        }

        .login-card {
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.25);
            width: 100%;
            max-width: 420px;
            padding: 40px 35px;
            text-align: center;
            position: relative;
        }

        .logo-box {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, #b8401e, #c84028);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            font-size: 32px;
            color: #ffffff;
            box-shadow: 0 8px 20px rgba(0, 51, 102, 0.3);
            border: 3px solid #ffc107;
        }

        h1 {
            font-size: 26px;
            color: #2c2c2c;
            margin-bottom: 4px;
            font-weight: 800;
            letter-spacing: -0.5px;
        }

        .subtitulo {
            font-size: 14px;
            color: #64748b !important;
            margin-bottom: 25px;
            font-weight: 500;
        }

        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }

        .form-group label {
            font-size: 13.5px;
            color: #334155 !important;
            margin-bottom: 6px;
            display: block;
            font-weight: 600;
        }

        .form-group label i {
            margin-right: 6px;
            color: #c84028;
            width: 16px;
        }

        .form-control {
            width: 100%;
            height: 46px;
            padding: 10px 14px;
            border: 1.5px solid #cbd5e1;
            border-radius: 10px;
            font-size: 14.5px;
            color: #1e293b;
            background-color: #f8fafc;
            transition: all 0.25s ease;
        }

        .form-control:focus {
            outline: none;
            border-color: #c84028;
            background-color: #ffffff;
            box-shadow: 0 0 0 3.5px rgba(200, 64, 40, 0.15);
        }

        .btn-login-submit {
            background: linear-gradient(135deg, #c84028 0%, #b8401e 100%);
            color: #ffffff !important;
            width: 100%;
            padding: 13px;
            border: none;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.25s ease;
            margin-top: 10px;
            box-shadow: 0 6px 18px rgba(200, 64, 40, 0.25);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-login-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(200, 64, 40, 0.35);
            background: linear-gradient(135deg, #a83221 0%, #8a2817 100%);
            color: #ffffff;
        }

        .btn-volver-sitio {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            padding: 10px;
            margin-top: 12px;
            background: #f1f5f9;
            color: #475569 !important;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            font-size: 13.5px;
            font-weight: 600;
            text-decoration: none !important;
            transition: all 0.2s ease;
        }

        .btn-volver-sitio:hover {
            background: #e2e8f0;
            color: #1e293b !important;
            transform: translateY(-1px);
        }

        .alert-error {
            background-color: #fee2e2;
            border-left: 4px solid #ef4444;
            padding: 12px 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 13.5px;
            color: #991b1b;
            text-align: left;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .login-footer {
            margin-top: 25px;
            font-size: 12px;
            color: #94a3b8 !important;
            border-top: 1px solid #e2e8f0;
            padding-top: 16px;
        }
    </style>
</head>
<body>

<div class="login-card">
    <div class="logo-box">
        <i class="fas fa-deaf"></i>
    </div>
   
    <h1>AureaMed</h1>
    <div class="subtitulo">Panel de Control y Gestión Clínica</div>
    
    <?php if($error != ''): ?>
        <div class="alert-error">
            <i class="fas fa-exclamation-circle fa-lg"></i>
            <span><?php echo $error; ?></span>
        </div>
    <?php endif; ?>
    
    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
        <div class="form-group">
            <label for="usuario"><i class="fas fa-user"></i> Usuario</label>
            <input type="text" id="usuario" class="form-control" name="usuario" placeholder="Ingresa tu usuario" required autocomplete="username" autofocus>
        </div>
        <div class="form-group">
            <label for="clave"><i class="fas fa-lock"></i> Contraseña</label>
            <input type="password" id="clave" class="form-control" name="clave" placeholder="••••••••" required autocomplete="current-password">
        </div>
        <button type="submit" class="btn-login-submit">
            <i class="fas fa-sign-in-alt"></i> INGRESAR AL SISTEMA
        </button>
    </form>

    <a href="../aurea--main/docs/index.php" class="btn-volver-sitio">
        <i class="fas fa-arrow-left"></i> Volver al Sitio Web Principal
    </a>

    <div class="login-footer">
        Centro Auditivo AureaMed &bull; Sistema Seguro
    </div>
</div>

<script src="./js/jquery-3.4.1.min.js"></script>
<script src="./js/popper.min.js"></script>
<script src="./js/bootstrap.min.js"></script>
<script src="./js/sweetalert2.min.js"></script>
<?php if($error != ''): ?>
<script>
    Swal.fire({
        icon: 'error',
        title: 'Acceso Denegado',
        text: '<?php echo addslashes($error); ?>',
        confirmButtonColor: '#c84028'
    });
</script>
<?php endif; ?>
</body>
</html>