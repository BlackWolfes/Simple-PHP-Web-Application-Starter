<?php
/**
 * Funciones Utilitarias del Sistema
 * Contiene funciones auxiliares para validación, sanitización y operaciones de base de datos
 * 
 * Funciones incluidas:
 * - Sanitización y validación de datos
 * - Generación de tokens seguros
 * - Consultas de estadísticas de usuario
 * - Manejo de actividad reciente
 * - Autenticación de usuarios
 * 
 * Autor: Sistema de optimización
 * Fecha: 2024
 */

// === FUNCIONES DE SANITIZACIÓN Y VALIDACIÓN ===

/**
 * Sanitiza datos de entrada eliminando etiquetas HTML y espacios
 * @param string $input Datos a sanitizar
 * @return string Datos sanitizados
 */
function sanitize_input($input) {
    if (empty($input)) {
        return '';
    }
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

/**
 * Valida formato de correo electrónico
 * @param string $email Correo a validar
 * @return bool|string Email válido o false si es inválido
 */
function validate_email($email) {
    $email = filter_var(trim($email), FILTER_VALIDATE_EMAIL);
    return $email !== false ? $email : false;
}

/**
 * Genera un token aleatorio seguro
 * @param int $length Longitud del token en bytes (por defecto 32)
 * @return string Token hexadecimal
 */
function generate_random_token($length = 32) {
    try {
        return bin2hex(random_bytes($length));
    } catch (Exception $e) {
        // Fallback en caso de error
        error_log("Error generando token aleatorio: " . $e->getMessage());
        return hash('sha256', uniqid(mt_rand(), true));
    }
}

/**
 * Valida la fortaleza de una contraseña
 * @param string $password Contraseña a validar
 * @return array Array con 'valid' (bool) y 'errors' (array)
 */
function validate_password_strength($password) {
    $errors = [];
    
    if (strlen($password) < 8) {
        $errors[] = "La contraseña debe tener al menos 8 caracteres";
    }
    
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = "La contraseña debe contener al menos una letra mayúscula";
    }
    
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = "La contraseña debe contener al menos una letra minúscula";
    }
    
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = "La contraseña debe contener al menos un número";
    }
    
    return [
        'valid' => empty($errors),
        'errors' => $errors
    ];
}

// === FUNCIONES DE ESTADÍSTICAS DE USUARIO ===

/**
 * Obtiene el número total de proyectos de un usuario
 * @param mysqli $conn Conexión a la base de datos
 * @param int $user_id ID del usuario
 * @return int Número de proyectos
 */
function get_user_project_count($conn, $user_id) {
    try {
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM projects WHERE user_id = ? AND deleted_at IS NULL");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $count = $result->fetch_assoc()['count'] ?? 0;
        $stmt->close();
        return (int)$count;
    } catch (Exception $e) {
        error_log("Error obteniendo conteo de proyectos para usuario $user_id: " . $e->getMessage());
        return 0;
    }
}

/**
 * Obtiene el número de tareas completadas de un usuario
 * @param mysqli $conn Conexión a la base de datos
 * @param int $user_id ID del usuario
 * @return int Número de tareas completadas
 */
function get_user_completed_task_count($conn, $user_id) {
    try {
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM tasks WHERE user_id = ? AND status = 'completed' AND deleted_at IS NULL");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $count = $result->fetch_assoc()['count'] ?? 0;
        $stmt->close();
        return (int)$count;
    } catch (Exception $e) {
        error_log("Error obteniendo conteo de tareas completadas para usuario $user_id: " . $e->getMessage());
        return 0;
    }
}

/**
 * Obtiene el número de tareas pendientes de un usuario
 * @param mysqli $conn Conexión a la base de datos
 * @param int $user_id ID del usuario
 * @return int Número de tareas pendientes
 */
function get_user_pending_task_count($conn, $user_id) {
    try {
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM tasks WHERE user_id = ? AND status IN ('pending', 'in_progress') AND deleted_at IS NULL");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $count = $result->fetch_assoc()['count'] ?? 0;
        $stmt->close();
        return (int)$count;
    } catch (Exception $e) {
        error_log("Error obteniendo conteo de tareas pendientes para usuario $user_id: " . $e->getMessage());
        return 0;
    }
}

/**
 * Obtiene todas las estadísticas de un usuario de una vez (optimizado)
 * @param mysqli $conn Conexión a la base de datos
 * @param int $user_id ID del usuario
 * @return array Array con las estadísticas del usuario
 */
function get_user_statistics($conn, $user_id) {
    try {
        $stats = [
            'projects' => 0,
            'completed_tasks' => 0,
            'pending_tasks' => 0,
            'total_tasks' => 0
        ];
        
        // Obtener estadísticas de proyectos
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM projects WHERE user_id = ? AND deleted_at IS NULL");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $stats['projects'] = (int)($result->fetch_assoc()['count'] ?? 0);
        $stmt->close();
        
        // Obtener estadísticas de tareas en una sola consulta
        $stmt = $conn->prepare("
            SELECT 
                status,
                COUNT(*) as count 
            FROM tasks 
            WHERE user_id = ? AND deleted_at IS NULL 
            GROUP BY status
        ");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            switch ($row['status']) {
                case 'completed':
                    $stats['completed_tasks'] = (int)$row['count'];
                    break;
                case 'pending':
                case 'in_progress':
                    $stats['pending_tasks'] += (int)$row['count'];
                    break;
            }
            $stats['total_tasks'] += (int)$row['count'];
        }
        $stmt->close();
        
        return $stats;
        
    } catch (Exception $e) {
        error_log("Error obteniendo estadísticas para usuario $user_id: " . $e->getMessage());
        return [
            'projects' => 0,
            'completed_tasks' => 0,
            'pending_tasks' => 0,
            'total_tasks' => 0
        ];
    }
}

// === FUNCIONES DE ACTIVIDAD RECIENTE ===

/**
 * Obtiene la actividad reciente de un usuario
 * @param mysqli $conn Conexión a la base de datos
 * @param int $user_id ID del usuario
 * @param int $limit Número máximo de actividades a obtener
 * @return array Array de actividades formateadas
 */
function get_user_recent_activity($conn, $user_id, $limit = 5) {
    try {
        // Consulta optimizada que incluye verificación de eliminación lógica
        $stmt = $conn->prepare("
            SELECT 'task' as type, title, created_at, updated_at
            FROM tasks 
            WHERE user_id = ? AND deleted_at IS NULL
            UNION ALL
            SELECT 'project' as type, name as title, created_at, updated_at
            FROM projects 
            WHERE user_id = ? AND deleted_at IS NULL
            ORDER BY GREATEST(created_at, COALESCE(updated_at, created_at)) DESC
            LIMIT ?
        ");
        $stmt->bind_param("iii", $user_id, $user_id, $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        $activities = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        // Formatear actividades para mostrar
        $formatted_activities = [];
        foreach ($activities as $activity) {
            $date = $activity['updated_at'] ?? $activity['created_at'];
            $formatted_activities[] = [
                'date' => date('d/m/Y H:i', strtotime($date)),
                'description' => ucfirst($activity['type']) . ': ' . htmlspecialchars($activity['title']),
                'type' => $activity['type']
            ];
        }

        return $formatted_activities;
        
    } catch (Exception $e) {
        error_log("Error obteniendo actividad reciente para usuario $user_id: " . $e->getMessage());
        return [];
    }
}

// === FUNCIONES DE AUTENTICACIÓN ===

/**
 * Intenta autenticar a un usuario con email y contraseña
 * @param mysqli $conn Conexión a la base de datos
 * @param string $email Email del usuario
 * @param string $password Contraseña en texto plano
 * @return array|false Datos del usuario si es exitoso, false si falla
 */
function attempt_login($conn, $email, $password) {
    try {
        // Preparar consulta con verificación de cuenta activa
        $stmt = $conn->prepare("
            SELECT id, fullname, password, email, last_login_at, failed_login_attempts 
            FROM users 
            WHERE email = ? AND verified = 1 AND deleted_at IS NULL
        ");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // Verificar contraseña con pepper
            if (password_verify($password . PASSWORD_PEPPER, $user['password'])) {
                // Actualizar último login y resetear intentos fallidos
                $update_stmt = $conn->prepare("
                    UPDATE users 
                    SET last_login_at = NOW(), failed_login_attempts = 0 
                    WHERE id = ?
                ");
                $update_stmt->bind_param("i", $user['id']);
                $update_stmt->execute();
                $update_stmt->close();
                
                $stmt->close();
                
                // Remover información sensible antes de retornar
                unset($user['password']);
                return $user;
            } else {
                // Incrementar intentos fallidos
                $update_stmt = $conn->prepare("
                    UPDATE users 
                    SET failed_login_attempts = failed_login_attempts + 1 
                    WHERE id = ?
                ");
                $update_stmt->bind_param("i", $user['id']);
                $update_stmt->execute();
                $update_stmt->close();
            }
        }
        
        $stmt->close();
        return false;
        
    } catch (Exception $e) {
        error_log("Error en intento de login para email $email: " . $e->getMessage());
        return false;
    }
}

/**
 * Verifica si un usuario existe por email
 * @param mysqli $conn Conexión a la base de datos
 * @param string $email Email a verificar
 * @return bool True si existe, false si no
 */
function user_exists_by_email($conn, $email) {
    try {
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND deleted_at IS NULL");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $exists = $result->num_rows > 0;
        $stmt->close();
        return $exists;
    } catch (Exception $e) {
        error_log("Error verificando existencia de usuario con email $email: " . $e->getMessage());
        return false;
    }
}

// === FUNCIONES DE UTILIDAD GENERAL ===

/**
 * Formatea un timestamp para mostrar de manera amigable
 * @param string $timestamp Timestamp a formatear
 * @param bool $include_time Si incluir la hora
 * @return string Fecha formateada
 */
function format_friendly_date($timestamp, $include_time = false) {
    $date = new DateTime($timestamp);
    $now = new DateTime();
    $diff = $now->diff($date);
    
    if ($diff->days == 0) {
        return $include_time ? 'Hoy ' . $date->format('H:i') : 'Hoy';
    } elseif ($diff->days == 1) {
        return $include_time ? 'Ayer ' . $date->format('H:i') : 'Ayer';
    } elseif ($diff->days < 7) {
        return $include_time ? $date->format('l H:i') : $date->format('l');
    } else {
        return $include_time ? $date->format('d/m/Y H:i') : $date->format('d/m/Y');
    }
}

/**
 * Trunca texto a una longitud específica manteniendo palabras completas
 * @param string $text Texto a truncar
 * @param int $length Longitud máxima
 * @param string $suffix Sufijo a agregar si se trunca
 * @return string Texto truncado
 */
function truncate_text($text, $length = 100, $suffix = '...') {
    if (strlen($text) <= $length) {
        return $text;
    }
    
    $truncated = substr($text, 0, $length);
    $last_space = strrpos($truncated, ' ');
    
    if ($last_space !== false) {
        $truncated = substr($truncated, 0, $last_space);
    }
    
    return $truncated . $suffix;
}
?>