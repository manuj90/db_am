<?php
require_once __DIR__ . '/../config/database.php';

class DatabaseManager
{
    private static $instance = null;
    private $connection = null;
    private $database = null;

    private function __construct()
    {
        $this->database = new Database();
        $this->connection = $this->database->connect();
    }
    private function __clone()
    {
    }

    public function __wakeup()
    {
        throw new Exception("Cannot unserialize singleton");
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection()
    {
        try {
            $this->connection->query('SELECT 1');
        } catch (PDOException $e) {
            $this->connection = $this->database->connect();
        }
        return $this->connection;
    }

    public function select($sql, $params = [])
    {
        try {
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Error al ejecutar consulta SELECT");
        }
    }

    public function selectOne($sql, $params = [])
    {
        try {
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            throw new Exception("Error al ejecutar consulta SELECT");
        }
    }
    public function insert($sql, $params = [])
    {
        try {
            $stmt = $this->getConnection()->prepare($sql);
            $result = $stmt->execute($params);
            if ($result) {
                $insertId = (int) $this->getConnection()->lastInsertId();
                return $insertId > 0 ? $insertId : true;
            }
            return false;
        } catch (PDOException $e) {
            throw new Exception("Error al ejecutar consulta INSERT");
        }
    }

    public function update($sql, $params = [])
    {
        try {
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->execute($params);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            throw new Exception("Error al ejecutar consulta UPDATE");
        }
    }

    public function delete($sql, $params = [])
    {
        try {
            $stmt = $this->getConnection()->prepare($sql);
            $stmt->execute($params);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            throw new Exception("Error al ejecutar consulta DELETE");
        }
    }

    public function execute($sql, $params = [])
    {
        try {
            $stmt = $this->getConnection()->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            throw new Exception("Error al ejecutar consulta");
        }
    }

    public function count($table, $where = '', $params = [])
    {
        try {
            $sql = "SELECT COUNT(*) as total FROM $table";
            if (!empty($where)) {
                $sql .= " WHERE $where";
            }

            $result = $this->selectOne($sql, $params);
            return (int) $result['total'];
        } catch (Exception $e) {
            throw new Exception("Error al contar registros");
        }
    }

    public function exists($table, $where, $params = [])
    {
        return $this->count($table, $where, $params) > 0;
    }

    public function beginTransaction()
    {
        return $this->getConnection()->beginTransaction();
    }

    public function commit()
    {
        return $this->getConnection()->commit();
    }

    public function rollback()
    {
        return $this->getConnection()->rollback();
    }

    public function transaction($callback)
    {
        try {
            $this->beginTransaction();

            $result = $callback($this);

            $this->commit();
            return $result;

        } catch (Exception $e) {
            $this->rollback();
            throw $e;
        }
    }

    public function lastInsertId()
    {
        return $this->getConnection()->lastInsertId();
    }

    public function quote($string)
    {
        return $this->getConnection()->quote($string);
    }

    public function getConnectionInfo()
    {
        $connection = $this->getConnection();
        return [
            'driver' => $connection->getAttribute(PDO::ATTR_DRIVER_NAME),
            'version' => $connection->getAttribute(PDO::ATTR_SERVER_VERSION),
            'connection_status' => $connection->getAttribute(PDO::ATTR_CONNECTION_STATUS)
        ];
    }

    public function close()
    {
        $this->connection = null;
        self::$instance = null;
    }

    public function __destruct()
    {
        $this->connection = null;
    }
}

function getDB()
{
    return DatabaseManager::getInstance();
}
?>