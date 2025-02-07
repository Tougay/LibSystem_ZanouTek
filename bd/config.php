<?php
// config.php
define('DB_HOST', 'localhost');
define('DB_NAME', 'depotmemo');
define('DB_USER', 'root');
define('DB_PASS', '');

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
        ]
    );
} catch(PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

// Fonctions utilitaires
function getCategoriesByType($pdo, $type) {
    $sql = "SELECT id, nom FROM categories WHERE parent_id = (SELECT id FROM categories WHERE nom = ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$type]);
    return $stmt->fetchAll();
}

function getMatieresForSerie($pdo, $serie) {
    $sql = "SELECT id, nom FROM categories WHERE parent_id = (SELECT id FROM categories WHERE nom = ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(["Série " . $serie]);
    return $stmt->fetchAll();
}
?>