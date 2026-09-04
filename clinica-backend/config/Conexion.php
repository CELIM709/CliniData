<?php

class Conexion {
    private static $host = 'localhost';
    private static $port = '5432';
    private static $dbname = 'clinica';  
    private static $user = 'postgres';     // Usuario predeterminado
    private static $password = '1234';     // Contraseña predeterminada

    private static $instancia = null;

    public static function conectar() {
        if (self::$instancia === null) {
            try {
                $dsn = "pgsql:host=" . self::$host . ";port=" . self::$port . ";dbname=" . self::$dbname;
                
                self::$instancia = new PDO($dsn, self::$user, self::$password, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,          // Lanza excepciones en errores SQL
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,     // Devuelve los datos como arreglos asociativos
                    PDO::ATTR_EMULATE_PREPARES => false                   // Desactiva la emulación para usar consultas preparadas nativas
                ]);
            } catch (PDOException $e) {
                die("Error crítico de conexión a la base de datos: " . $e->getMessage());
            }
        }

        return self::$instancia;
    }
}
// Prueba rápida (eliminar o comentar después de verificar)
$db = Conexion::conectar();
if ($db) echo "Conexion exitosa a PostgreSQL mediante PDO";