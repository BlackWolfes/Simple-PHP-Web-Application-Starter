<?php
/**
 * Controlador de Login
 * Maneja la lógica de autenticación de usuarios
 * 
 * Autor: Sistema de optimización
 * Fecha: 2024
 */

class LoginController {
    private $conn;
    
    public function __construct($connection) {
        $this->conn = $connection;
    }
    
    /**
     * Procesa el formulario de login
     * @return array Resultado del procesamiento
     */
    public function processLogin() {
        $result = ['success' => false, 'error' => '', 'redirect' => ''];
        
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            return $result;
        }
        
        // Verificar token CSRF para prevenir ataques
        if (!verify_csrf_token($_POST['csrf_token'])) {
            die("Error de validación CSRF. Por favor, recargue la página e inténtelo de nuevo.");
        }

        // Verificar límite de intentos de login para prevenir ataques de fuerza bruta
        if (!check_rate_limit($_SERVER['REMOTE_ADDR'], 'login', MAX_LOGIN_ATTEMPTS, LOGIN_TIMEOUT)) {
            die("Demasiados intentos de inicio de sesión. Por favor, inténtelo más tarde.");
        }

        // Sanitizar y validar datos de entrada
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'] ?? '';

        // Validar que los campos no estén vacíos
        if (empty($email) || empty($password)) {
            $result['error'] = "Por favor, complete todos los campos.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $result['error'] = "Formato de email inválido.";
        } else {
            // Intentar autenticar al usuario
            $user = attempt_login($this->conn, $email, $password);
            
            if ($user) {
                // Login exitoso: establecer variables de sesión
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['fullname'];
                $_SESSION['login_time'] = time();
                
                // Registrar actividad de login exitoso
                log_activity($user['id'], "Login exitoso desde IP: " . $_SERVER['REMOTE_ADDR']);
                
                // Resetear contador de intentos fallidos
                reset_login_attempts($_SERVER['REMOTE_ADDR']);
                
                $result['success'] = true;
                $result['redirect'] = 'dashboard.php';
            } else {
                // Login fallido: mostrar error e incrementar contador
                $result['error'] = "Email o contraseña incorrectos.";
                increment_login_attempts($_SERVER['REMOTE_ADDR']);
                
                // Registrar intento de login fallido
                error_log("Intento de login fallido para email: $email desde IP: " . $_SERVER['REMOTE_ADDR']);
            }
        }
        
        return $result;
    }
    
    /**
     * Renderiza la vista de login
     * @param string $error Mensaje de error a mostrar
     */
    public function showLoginForm($error = '') {
        $pageTitle = "Iniciar Sesión - " . APP_NAME;
        $csrfToken = generate_csrf_token();
        $email = isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '';
        
        include __DIR__ . '/../views/login.php';
    }
}