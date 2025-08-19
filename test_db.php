<?php
try {
    $pdo = new PDO("mysql:host=192.168.252.75;port=3308;dbname=v2_bd_suivi_planting;charset=utf8", "root", "secret");
    echo "Connexion réussie !";
} catch (PDOException $e) {
    echo "Erreur : " . $e->getMessage();
}
