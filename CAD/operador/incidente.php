
<?php
session_start();
require_once '../db.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$mensaje_exito = '';
$mensaje_error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        // Validación de ubicación
        if (empty($_POST['latitud']) || empty($_POST['longitud'])) {
            throw new Exception("Debes seleccionar una ubicación en el mapa");
        }

        $latitud = (float)$_POST['latitud'];
        $longitud = (float)$_POST['longitud'];
        
        if (!is_numeric($latitud) || !is_numeric($longitud)) {
            throw new Exception("Las coordenadas deben ser valores numéricos");
        }

        // Validación y saneamiento de datos
        $quepaso = trim($_POST['paso'] ?? '');
        $tipo_auxilio = trim($_POST['tipo_auxilio'] ?? '');
        $num_personas = intval($_POST['num_personas'] ?? 0);
        $telefono = trim($_POST['numero'] ?? '');
        $clasificacion = trim($_POST['clasificacion'] ?? '');
        $prioridad = trim($_POST['prioridad'] ?? '');
        $id_usuario_reporta = $_SESSION['id_usuario'] ?? null;

        // Validar valores de enumeración
        $clasificaciones_validas = ['Broma', 'Emergencia Real', 'TTY'];
        $prioridades_validas = ['Grave', 'Media', 'Baja'];
        
        if (!in_array($clasificacion, $clasificaciones_validas)) {
            throw new Exception("Clasificación no válida");
        }
        
        if (!in_array($prioridad, $prioridades_validas)) {
            throw new Exception("Prioridad no válida");
        }

        // Consulta SQL con todos los campos
        $sql = "INSERT INTO incidentes (
            quepaso, tipo_auxilio, hora_incidente, fecha_incidente, 
            num_personas, telefono, id_usuario_reporta, 
            clasificacion, prioridad, latitud, longitud
        ) VALUES (?, ?, CURTIME(), CURDATE(), ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Error al preparar la consulta: " . $conn->error);
        }

        // Registrar parámetros para depuración
        error_log("Parámetros de la consulta:");
        error_log("1. quepaso: " . $quepaso);
        error_log("2. tipo_auxilio: " . $tipo_auxilio);
        error_log("3. num_personas: " . $num_personas);
        error_log("4. telefono: " . $telefono);
        error_log("5. id_usuario_reporta: " . $id_usuario_reporta);
        error_log("6. clasificacion: " . $clasificacion);
        error_log("7. prioridad: " . $prioridad);
        error_log("8. latitud: " . $latitud);
        error_log("9. longitud: " . $longitud);

        $stmt->bind_param(
            "ssisissdd",
            $quepaso,
            $tipo_auxilio,
            $num_personas,
            $telefono,
            $id_usuario_reporta,
            $clasificacion,
            $prioridad,
            $latitud,
            $longitud
        );

        if ($stmt->execute()) {
            $folio_incidente = $stmt->insert_id;
            $mensaje_exito = "Incidente registrado correctamente (Folio #$folio_incidente)";
            
            // Limpiar formulario después de enviar
            echo '<script>
                document.getElementById("form-incidente").reset();
                document.getElementById("latitud_display").value = "";
                document.getElementById("longitud_display").value = "";
                document.getElementById("latitud").value = "";
                document.getElementById("longitud").value = "";
                if (window.marker) window.map.removeLayer(window.marker);
            </script>';
        } else {
            throw new Exception("Error al registrar: " . $stmt->error);
        }
    } catch (Exception $e) {
        $mensaje_error = $e->getMessage();
        error_log("Error en registro de incidente: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro del incidente</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f4f4f4;
            display: flex;
            flex-wrap: wrap;
        }
        
        .blue-bar {
            background-color: #93D8E8;
            color: white;
            padding: 20px;
            text-align: center;
            font-size: 24px;
            font-weight: bold;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            position: relative;
            display: flex;
            justify-content: center;
            align-items: center;
            width: 100%;
        }
        
        .logo {
            position: absolute;
            top: 50%;
            right: 20px;
            transform: translateY(-50%);
            max-height: 50px;
            width: auto;
        }
        
        .map-panel {
            flex: 1;
            min-width: 300px;
            padding: 20px;
            background-color: white;
            margin: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        
        #map {
            height: 400px;
            width: 100%;
            border-radius: 8px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
        }
        
        .search-box {
            display: flex;
            margin-bottom: 15px;
        }
        
        .search-box input {
            flex: 1;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px 0 0 4px;
            font-size: 14px;
        }
        
        .search-box button {
            padding: 10px 15px;
            background-color: #93D8E8;
            color: white;
            border: none;
            border-radius: 0 4px 4px 0;
            cursor: pointer;
        }
        
        .form-panel {
            flex: 1;
            min-width: 300px;
            max-width: 600px;
            padding: 20px;
            background-color: white;
            margin: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
        }
        
        input[type="text"],
        input[type="number"],
        select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        
        .coords-container {
            display: flex;
            gap: 10px;
        }
        
        .coord-input {
            flex: 1;
        }
        
        #clasificacion option[value="broma"] {
            background-color: #c300ff; 
            color: #000;
        }
        
        #clasificacion option[value="emergencia_real"] {
            background-color: #ff8800; 
            color: white;
        }
        
        #clasificacion option[value="tty"] {
            background-color: #4682B4; 
            color: white;
        }
        
        #prioridad option[value="grave"] {
            background-color: #ff1100;
            color: #000;
        }
        
        #prioridad option[value="media"] {
            background-color: #80f894; 
            color: white;
        }
        
        #prioridad option[value="baja"] {
            background-color: #eee454; 
            color: white;
        }

        .submit-btn {
            background-color: #93D8E8;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            width: 100%;
        }
        
        .submit-btn:hover {
            background-color: #82c8d8;
        }
        
        .mensaje-exito {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px;
            background: #4CAF50;
            color: white;
            border-radius: 5px;
            z-index: 1000;
        }

        .mensaje-error {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 15px;
            background: #f44336;
            color: white;
            border-radius: 5px;
            z-index: 1000;
        }
        
        @media (max-width: 768px) {
            .map-panel, .form-panel {
                margin: 10px;
                min-width: calc(100% - 20px);
            }
            .coords-container {
                flex-direction: column;
            }
        }
    </style>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
</head>
<body>
    <div class="blue-bar">
        <img src="../logo.png" alt="Logo" class="logo">
        REGISTRO DE INCIDENTE
    </div>
    
    <div class="map-panel">
        <h3>Ubicación del Incidente</h3>
        <div id="map"></div>
        <div class="search-box">
            <input type="text" id="address-input" placeholder="Buscar ubicación...">
            <button id="search-button">Buscar</button>
        </div>
        <div class="form-group">
            <label>Coordenadas:</label>
            <div class="coords-container">
                <div class="coord-input">
                    <label for="latitud_display">Latitud:</label>
                    <input type="text" id="latitud_display" readonly>
                </div>
                <div class="coord-input">
                    <label for="longitud_display">Longitud:</label>
                    <input type="text" id="longitud_display" readonly>
                </div>
            </div>
        </div>
    </div>
    
    <div class="form-panel">
        <form id="form-incidente" action="incidente.php" method="post" onsubmit="return validarFormulario()">
            <input type="hidden" id="latitud" name="latitud" value="">
            <input type="hidden" id="longitud" name="longitud" value="">
            
            <div class="form-group">
                <label for="paso">¿Qué pasó?:</label>
                <input type="text" id="paso" name="paso" required value="<?= htmlspecialchars($_POST['paso'] ?? '') ?>">
            </div>
            
            <div class="form-group">
                <label for="tipo_auxilio">Tipo de auxilio:</label>
                <select id="tipo_auxilio" name="tipo_auxilio" required>
                    <option value="">Seleccione un tipo</option>
                    <option value="medico" <?= ($_POST['tipo_auxilio'] ?? '') == 'medico' ? 'selected' : '' ?>>Médico</option>
                    <option value="proteccion_civil" <?= ($_POST['tipo_auxilio'] ?? '') == 'proteccion_civil' ? 'selected' : '' ?>>Protección Civil</option>
                    <option value="seguridad" <?= ($_POST['tipo_auxilio'] ?? '') == 'seguridad' ? 'selected' : '' ?>>Seguridad</option>
                    <option value="servicios_publicos" <?= ($_POST['tipo_auxilio'] ?? '') == 'servicios_publicos' ? 'selected' : '' ?>>Servicios Públicos</option>
                    <option value="otros" <?= ($_POST['tipo_auxilio'] ?? '') == 'otros' ? 'selected' : '' ?>>Otros servicios</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="num_personas">Número de personas involucradas:</label>
                <input type="number" id="num_personas" name="num_personas" min="1" required value="<?= htmlspecialchars($_POST['num_personas'] ?? '') ?>">
            </div>
            
            <div class="form-group">
                <label for="numero">Número celular:</label>
                <input type="text" id="numero" name="numero" required value="<?= htmlspecialchars($_POST['numero'] ?? '') ?>">
            </div>
            
            <div class="form-group">
                <label for="clasificacion">Clasificación de la llamada:</label>
                <select id="clasificacion" name="clasificacion" required>
                    <option value="">Seleccione un tipo</option>
                    <option value="Broma" <?= ($_POST['clasificacion'] ?? '') == 'Broma' ? 'selected' : '' ?>>Broma</option>
                    <option value="Emergencia Real" <?= ($_POST['clasificacion'] ?? '') == 'Emergencia Real' ? 'selected' : '' ?>>Emergencia Real</option>
                    <option value="TTY" <?= ($_POST['clasificacion'] ?? '') == 'TTY' ? 'selected' : '' ?>>TTY</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="prioridad">Prioridad:</label>
                <select id="prioridad" name="prioridad" required>
                    <option value="">Seleccione la prioridad</option>
                    <option value="Grave" <?= ($_POST['prioridad'] ?? '') == 'Grave' ? 'selected' : '' ?>>Grave</option>
                    <option value="Media" <?= ($_POST['prioridad'] ?? '') == 'Media' ? 'selected' : '' ?>>Media</option>
                    <option value="Baja" <?= ($_POST['prioridad'] ?? '') == 'Baja' ? 'selected' : '' ?>>Baja</option>
                </select>
            </div>

            <button type="submit" class="submit-btn">ENVIAR REPORTE</button>
        </form>
    </div>

    <script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
    <script>
        // Configuración del mapa
        const map = L.map('map').setView([19.4326, -99.1332], 12);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png').addTo(map);

        let marker = null;

        function actualizarCoordenadas(lat, lng) {
            document.getElementById('latitud').value = lat;
            document.getElementById('longitud').value = lng;
            document.getElementById('latitud_display').value = lat;
            document.getElementById('longitud_display').value = lng;
            
            console.log('Coordenadas actualizadas:', {
                latitud: lat,
                longitud: lng
            });
        }

        map.on('click', function(e) {
            if (marker) map.removeLayer(marker);
            marker = L.marker(e.latlng).addTo(map);
            actualizarCoordenadas(
                e.latlng.lat.toFixed(6), 
                e.latlng.lng.toFixed(6)
            );
        });

        document.getElementById('search-button').addEventListener('click', function() {
            const address = document.getElementById('address-input').value;
            if (!address.trim()) return;
            
            fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(address)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.length > 0) {
                        const lat = data[0].lat;
                        const lon = data[0].lon;
                        
                        if (marker) map.removeLayer(marker);
                        map.setView([lat, lon], 15);
                        marker = L.marker([lat, lon]).addTo(map);
                        actualizarCoordenadas(lat, lon);
                    } else {
                        alert('Ubicación no encontrada');
                    }
                });
        });

        function validarFormulario() {
            const latitud = document.getElementById('latitud').value;
            const longitud = document.getElementById('longitud').value;
            
            if (!latitud || !longitud) {
                alert('Por favor selecciona una ubicación en el mapa haciendo clic en él');
                return false;
            }
            
            if (isNaN(latitud) || isNaN(longitud)) {
                alert('Las coordenadas no son válidas. Por favor selecciona una nueva ubicación');
                return false;
            }
            
            return true;
        }
    </script>
    
    <?php if ($mensaje_exito): ?>
        <div class="mensaje-exito"><?= htmlspecialchars($mensaje_exito) ?></div>
        <script>
            setTimeout(() => document.querySelector('.mensaje-exito').remove(), 5000);
        </script>
    <?php endif; ?>

    <?php if ($mensaje_error): ?>
        <div class="mensaje-error"><?= htmlspecialchars($mensaje_error) ?></div>
        <script>
            setTimeout(() => document.querySelector('.mensaje-error').remove(), 5000);
        </script>
    <?php endif; ?>
</body>
</html>