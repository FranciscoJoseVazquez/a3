<?php

// funciones

function obtenerAccessToken($code) {
    $client_id = "WK.ES.A3WebApi.44480.S";
    $client_secret = "JcWu2z4dR5Q3";
    $redirect_uri = "http://172.16.7.75:8100/listas.php";
    $token_url = "https://login.wolterskluwer.eu/auth/core/connect/token";

    $data = [
        'grant_type' => 'authorization_code',
        'code' => $code,
        'redirect_uri' => $redirect_uri,
        'client_id' => $client_id,
        'client_secret' => $client_secret,
    ];

    $options = [
        CURLOPT_URL => $token_url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($data),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/x-www-form-urlencoded'
        ],
    ];

    $ch = curl_init();
    curl_setopt_array($ch, $options);
    $response = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if (curl_errno($ch)) {
        echo "Error CURL: " . curl_error($ch);
        curl_close($ch);
        return null;
    }

    curl_close($ch);

    if ($httpcode !== 200) {
        echo "Error HTTP $httpcode al obtener token.\nRespuesta: $response\n";
        return null;
    }

    $json = json_decode($response, true);

    if (isset($json["access_token"])) {
        echo "Access token obtenido correctamente:\n";
        echo $json["access_token"] . "\n";
        return $json;
    } else {
        echo "No se pudo obtener el token de acceso.\n";
        return null;
    }
}

function obtenerVacacionesEmpleados($companyCode, $access_token, $subscriptionKey) {
    $url = "https://a3api.wolterskluwer.es/Laboral/api/companies/{$companyCode}/absences?pageNumber=1&pageSize=100";

    $headers = [
        "Authorization: Bearer {$access_token}",
        "Ocp-Apim-Subscription-Key: {$subscriptionKey}",
        "Content-Type: application/json"
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $response = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpcode !== 200) {
        echo "Error HTTP $httpcode al obtener vacaciones.\nRespuesta: $response\n";
        return null;
    }

    $data = json_decode($response, true);

    if (empty($data)) {
        echo "No se encontraron vacaciones o el formato es incorrecto.\n";
        return [];
    }

    return $data;
}

function buscarVacacionesPorCIF($vacaciones, $cif) {
    foreach ($vacaciones as $vacacion) {
        if (isset($vacacion['employeeIdentification']['nif']) &&
            trim($vacacion['employeeIdentification']['nif']) === trim($cif)) {
            return $vacacion;
        }
    }
    return null;
}

function calcularDias($inicio, $fin) {
    $start = new DateTime($inicio);
    $end = new DateTime($fin);
    $interval = $start->diff($end);
    return $interval->days + 1;
}

function actualizarAusencia($absenceId, $diasActualizados, $access_token, $subscriptionKey) {
    $url = "https://a3api.wolterskluwer.es/Laboral/api/absences/{$absenceId}";

    $headers = [
        "Authorization: Bearer {$access_token}",
        "Ocp-Apim-Subscription-Key: {$subscriptionKey}",
        "Content-Type: application/json"
    ];

    $data = [
        "daysTaken" => $diasActualizados
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $response = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpcode === 200 || $httpcode === 204) {
        return true;
    } else {
        echo "Error al actualizar días de ausencia ID $absenceId. HTTP $httpcode. Respuesta: $response<br>";
        return false;
    }
}

// Variables
//- Variables de token

$code = "Ej"; // <-- Cambiar 
$tokenData = obtenerAccessToken($code);
$access_token = $tokenData["access_token"];

//- Variables de consultas

$access_token = $access_token;
$companyCode = 100; // Código de empresa
$subscriptionKey = "2kxwf5rb7ffb65dd5"; // <-- Cambiar 

// sacar todas las vacaciones (Formato JSON)
$vacaciones = obtenerVacacionesEmpleados($companyCode, $access_token, $subscriptionKey);


if (isset($_FILES['archivo']) && $_FILES['archivo']['error'] === UPLOAD_ERR_OK) {
    $tmpName = $_FILES['archivo']['tmp_name'];
    $archivo = fopen($tmpName, "r");

    if ($archivo !== FALSE) {
        // fgetcsv($archivo); // Si el fichero tiene encabezados, descomentar esto

        while (($fila = fgetcsv($archivo, 1000, ";")) !== FALSE) {
            // Datos archivo
            $id = $fila[0];
            $cif = trim($fila[1]);
            $apellidos = $fila[2];
            $nombre = $fila[3];
            $diasActualizados = (int)$fila[4];

            // si no se actualiza lo ignora
            if ($diasActualizados <= 0) {
                continue;
            }

            // buscar vacaciones de usuario
            $registro = buscarVacacionesPorCIF($vacaciones, $cif);

            if ($registro) {
                // Actualizar
                $absenceId = $registro["id"];
                actualizarAusencia($absenceId, $diasActualizados, $access_token, $subscriptionKey);
            } else {
                echo "$nombre $apellidos (CIF: $cif) no encontrado.<br>";
            }
        }

        fclose($archivo);
    } else {
        echo "No se pudo abrir el archivo.";
    }
} else {
    echo "Error al subir el archivo.";
}

?>