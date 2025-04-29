<?php
session_start();
require_once '../db.php';

// Verificar si el usuario está logueado (operador)
if (!isset($_SESSION['id_usuario']) || $_SESSION['tipo_usuario'] != 'operador') {
    header('Location: login.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Atención de Llamadas</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
        }
        
        .blue-bar {
            background-color: #93D8E8;
            color: white;
            padding: 40px;
            text-align: center;
            font-size: 24px;
            font-weight: bold;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            position: relative;
        }
        
        .container {
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .btn-atender {
            display: inline-block;
            padding: 15px 30px;
            background-color: #4CAF50;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            font-size: 18px;
            font-weight: bold;
            margin-top: 20px;
            transition: background-color 0.3s;
        }
        
        .btn-atender:hover {
            background-color: #45a049;
        }
        
        .status {
            margin-top: 30px;
            padding: 15px;
            border-radius: 5px;
            background-color: #f8f8f8;
        }
    </style>
</head>
<body>
    <div class="blue-bar">
        Sistema de Atención de Llamadas
    </div>
    
    <div class="container">
        <h2>Panel del Operador</h2>
        <p>Bienvenido, <?php echo htmlspecialchars($_SESSION['nombre']); ?></p>
        
        <a href="incidente.php" class="btn-atender">Atender Llamada</a>
        
        <div class="status">
            <?php
            // Verificar si hay una llamada en curso
            $sql = "SELECT * FROM llamadas WHERE id_operador = ? AND estatus = 'En curso' LIMIT 1";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $_SESSION['id_usuario']);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $llamada = $result->fetch_assoc();
                echo "<p>Tienes una llamada en curso (ID: ".htmlspecialchars($llamada['id_llamada']).")</p>";
                echo "<a href='incidente.php?id=".htmlspecialchars($llamada['id_llamada'])."'>Continuar atención</a>";
            }
            ?>
        </div>
    </div>
</body>
</html>