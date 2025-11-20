<?php
$host = 'localhost';
$dbname = 'subnet_tool';
$username = 'root'; // mettre le username de votre SGBD
$password = '';     // mettre le mot de passe de votre SGBD

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}
?>