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
  <title>AureaMed | Iniciar Sesión</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <script type="module" src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.esm.js"></script>
  <script nomodule src="https://unpkg.com/ionicons@7.1.0/dist/ionicons/ionicons.js"></script>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@500&display=swap');

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: 'Poppins', sans-serif;
    }

    body {
      position: relative;
      display: flex;
      align-items: center;
      justify-content: center;
      min-height: 100vh;
      background: #efefef;
      padding: 20px;
      overflow: hidden;
    }

    .brand-bg {
      position: absolute;
      inset: 0;
      background-image: url('./aurea.jpg');
      background-repeat: no-repeat;
      background-position: center 58%;
      background-size: 76% auto;
      opacity: 0.35;
      pointer-events: none;
    }

    section {
      position: relative;
      z-index: 1;
      max-width: 420px;
      width: 100%;
      background-color: rgba(255, 255, 255, 0.08);
      border: 2px solid rgba(255, 255, 255, 0.35);
      border-radius: 22px;
      backdrop-filter: blur(18px);
      -webkit-backdrop-filter: blur(18px);
      display: flex;
      justify-content: center;
      align-items: center;
      padding: 2.2rem 2.5rem;
      box-shadow: 0 20px 40px rgba(0, 0, 0, 0.25);
    }

    form {
      width: 100%;
    }

    h1 {
      font-size: 2rem;
      color: #2c2c2c;
      text-align: center;
      margin-bottom: 10px;
      letter-spacing: 1px;
      font-weight: 700;
      text-shadow: 0 2px 8px rgba(255,255,255,0.3);
    }

    .logo-box {
      width: 100px;
      height: 100px;
      border-radius: 50%;
      background: linear-gradient(135deg, rgba(255,255,255,0.35), rgba(255,255,255,0.12));
      background-image: url('./aurea.jpg');
      background-size: contain;
      background-position: center;
      background-repeat: no-repeat;
      border: 3px solid rgba(255,255,255,0.6);
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 18px auto;
      color: #c84028;
      font-size: 3rem;
      box-shadow: 0 10px 30px rgba(0,0,0,0.25), inset 0 2px 4px rgba(255,255,255,0.5);
      font-weight: bold;
    }

    .subtitulo {
      color: #4a4a4a;
      text-align: center;
      font-size: 0.9rem;
      margin-bottom: 20px;
      font-weight: 500;
    }

    .inputbox {
      position: relative;
      margin: 28px 0;
      border-bottom: 2px solid rgba(44, 44, 44, 0.4);
    }

    .inputbox label {
      position: absolute;
      top: 50%;
      left: 5px;
      transform: translateY(-50%);
      color: #7a7a7a;
      font-size: 1rem;
      pointer-events: none;
      transition: all 0.5s ease-in-out;
    }

    .inputbox input:focus ~ label,
    .inputbox input:valid ~ label {
      top: -5px;
      font-size: 0.8rem;
    }

    .inputbox input {
      width: 100%;
      height: 60px;
      background: transparent;
      border: none;
      outline: none;
      font-size: 1rem;
      padding: 0 35px 0 5px;
      color: #2c2c2c;
    }

    .inputbox ion-icon {
      position: absolute;
      right: 8px;
      color: #7a7a7a;
      font-size: 1.2rem;
      top: 18px;
    }

    .forget {
      margin: 28px 0;
      font-size: 0.85rem;
      color: #4a4a4a;
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .forget label {
      display: flex;
      align-items: center;
      gap: 5px;
    }

    .forget label input {
      accent-color: #d4654f;
    }

    .forget a {
      color: #d4654f;
      text-decoration: none;
      font-weight: 600;
    }

    .forget a:hover {
      text-decoration: underline;
    }

    button {
      width: 100%;
      height: 45px;
      border-radius: 40px;
      background-color: #2c2c2c;
      color: #fff;
      border: none;
      outline: none;
      cursor: pointer;
      font-size: 1rem;
      font-weight: 600;
      transition: all 0.4s ease;
      margin-top: 5px;
    }

    button:hover {
      background-color: #1a1a1a;
    }

    .register {
      font-size: 0.9rem;
      color: #4a4a4a;
      text-align: center;
      margin: 25px 0 10px;
    }

    .register a {
      text-decoration: none;
      color: #d4654f;
      font-weight: 600;
    }

    .register a:hover {
      text-decoration: underline;
    }

    .alert-error {
      background: rgba(212, 101, 79, 0.15);
      border: 1px solid rgba(212, 101, 79, 0.5);
      color: #8b3a2a;
      padding: 10px 12px;
      border-radius: 12px;
      font-size: 0.85rem;
      margin-bottom: 15px;
    }

    @media (max-width: 480px) {
      section {
        padding: 1.5rem 1.2rem;
      }
    }
  </style>
</head>
<body>
  <div class="brand-bg"></div>
  <section>
    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
      <div class="logo-box">
        <i class="fas fa-deaf"></i>
      </div>
      <h1>AureaMed</h1>
      <div class="subtitulo">Panel de Control</div>

      <?php if($error != ''): ?>
        <div class="alert-error"><?php echo $error; ?></div>
      <?php endif; ?>

      <div class="inputbox">
        <ion-icon name="mail-outline"></ion-icon>
        <input type="text" name="usuario" required autocomplete="username" autofocus>
        <label>Usuario</label>
      </div>

      <div class="inputbox">
        <ion-icon name="lock-closed-outline"></ion-icon>
        <input type="password" name="clave" required autocomplete="current-password">
        <label>Contraseña</label>
      </div>

      <div class="forget">
        <label><input type="checkbox"> Recordarme</label>
        <a href="#">¿Olvidaste tu contraseña?</a>
      </div>

      <button type="submit">Iniciar sesión</button>

      <div class="register">
        <p>¿No tienes cuenta? <a href="#">Contáctanos</a></p>
      </div>
    </form>
  </section>
</body>
</html>
