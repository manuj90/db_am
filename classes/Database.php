<?php
require_once __DIR__ . '/../config/database.php';

/**
 * Clase Database - Singleton para manejo de conexiones
 */
class DatabaseManager {
    private static $instance = null;
    private $connection = null;
    private $database = null;
    
    private function __construct() {
        $this->database = new Database();
        $this->connection = $this->database->connect();
    }
    
    // Evitar clonación
    private function __clone() {}
    
    // Evitar deserialización
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
    
    // Obtener instancia única
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    // Obtener conexión PDO
    public function getConnection() {
        // Verificar si la conexión sigue activa
        try {
            $this->connection->query('SELECT 1');
        } catch (PDOException $e) {
            // Reconectar si la conexión se perdió
            $this->connection = $this->database->connect();
        }
        
        return $this->connection;
    }
    
    // Ejecutar consulta SELECT
    public function select($sql, $params = []) {
        try {
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Database SELECT Error: " . $e->getMessage());
            throw new Exception("Error al ejecutar consulta SELECT");
        }
    }
    
    // Ejecutar consulta SELECT que devuelve un solo registro
    public function selectOne($sql, $params = []) {
        try {
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Database SELECT ONE Error: " . $e->getMessage());
            throw new Exception("Error al ejecutar consulta SELECT");
        }
    }
    
    // Ejecutar consulta INSERT
    public function insert($sql, $params = []) {
        try {
            $stmt = $this->getConnection()->prepare($sql);
            $result = $stmt->execute($params);
            if ($result) {
                // Convert the insert ID to int to avoid a "0" string evaluating as false
                $insertId = (int) $this->getConnection()->lastInsertId();
                // When the table has an auto‑increment PK, we return the ID (must be >0).
                // If the table does not use AI (or triggers return 0), at least return true.
                return $insertId > 0 ? $insertId : true;
            }
            return false;
        } catch (PDOException $e) {
            error_log("Database INSERT Error: " . $e->getMessage());
            throw new Exception("Error al ejecutar consulta INSERT");
        }
    }
    
    // Ejecutar consulta UPDATE
    public function update($sql, $params = []) {
        try {
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->execute($params);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            error_log("Database UPDATE Error: " . $e->getMessage());
            throw new Exception("Error al ejecutar consulta UPDATE");
        }
    }
    
    // Ejecutar consulta DELETE
    public function delete($sql, $params = []) {
        try {
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->execute($params);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            error_log("Database DELETE Error: " . $e->getMessage());
            throw new Exception("Error al ejecutar consulta DELETE");
        }
    }
    
    // Ejecutar cualquier consulta (para casos especiales)
    public function execute($sql, $params = []) {
        try {
            $stmt = $this->getConnection()->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            error_log("Database EXECUTE Error: " . $e->getMessage());
            throw new Exception("Error al ejecutar consulta");
        }
    }
    
    // Contar registros
    public function count($table, $where = '', $params = []) {
        try {
            $sql = "SELECT COUNT(*) as total FROM $table";
            if (!empty($where)) {
                $sql .= " WHERE $where";
            }
            
            $result = $this->selectOne($sql, $params);
            return (int) $result['total'];
        } catch (Exception $e) {
            error_log("Database COUNT Error: " . $e->getMessage());
            throw new Exception("Error al contar registros");
        }
    }
    
    // Verificar si existe un registro
    public function exists($table, $where, $params = []) {
        return $this->count($table, $where, $params) > 0;
    }
    
    // Iniciar transacción
    public function beginTransaction() {
        return $this->getConnection()->beginTransaction();
    }
    
    // Confirmar transacción
    public function commit() {
        return $this->getConnection()->commit();
    }
    
    // Revertir transacción
    public function rollback() {
        return $this->getConnection()->rollback();
    }
    
    // Ejecutar múltiples consultas en transacción
    public function transaction($callback) {
        try {
            $this->beginTransaction();
            
            $result = $callback($this);
            
            $this->commit();
            return $result;
            
        } catch (Exception $e) {
            $this->rollback();
            error_log("Transaction Error: " . $e->getMessage());
            throw $e;
        }
    }
    
    // Obtener último ID insertado
    public function lastInsertId() {
        return $this->getConnection()->lastInsertId();
    }
    
    // Escapar string para prevenir inyección SQL (usar con cuidado, mejor usar parámetros)
    public function quote($string) {
        return $this->getConnection()->quote($string);
    }
    
    // Obtener información de la conexión
    public function getConnectionInfo() {
        $connection = $this->getConnection();
        return [
            'driver' => $connection->getAttribute(PDO::ATTR_DRIVER_NAME),
            'version' => $connection->getAttribute(PDO::ATTR_SERVER_VERSION),
            'connection_status' => $connection->getAttribute(PDO::ATTR_CONNECTION_STATUS)
        ];
    }
    
    // Cerrar conexión
    public function close() {
        $this->connection = null;
        self::$instance = null;
    }
    
    // Destructor
    public function __destruct() {
        $this->connection = null;
    }
}

// Función helper para obtener la instancia de base de datos
function getDB() {
    return DatabaseManager::getInstance();
}

?>