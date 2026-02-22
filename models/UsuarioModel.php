<?php
class UsuarioModel {
    private $pdo; //estos es la variable de conexion q luego haras en database.php

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    //Aqui iran todas las consultas relacionadas y necesarias para la tabla de base de datos Usuario
    public function verificarCredenciales($nickname, $password) {
    }
}