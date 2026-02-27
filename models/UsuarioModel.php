<?php
// UsuarioModel.php
class UsuarioModel {
    private $conn;
    private $table = 'usuario';
    
    public function __construct($db) {
        $this->conn = $db;
    }
    
    public function validarCredenciales($email, $password) {
        try {
            $query = "SELECT id_usuario, email, password 
                      FROM " . $this->table . " 
                      WHERE email = :email";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            
            if($stmt->rowCount() > 0) {
                $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if(password_verify($password, $usuario['password'])) {
                    return [
                        'success' => true,
                        'id_usuario' => $usuario['id_usuario'],
                        'email' => $usuario['email']
                    ];
                }
            }
            
            return ['success' => false];
            
        } catch(PDOException $e) {
            error_log("Error: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
?>