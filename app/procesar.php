<?php

// funciones
function guardarToken($data) {
    file_put_contents("token.json", json_encode($data));
}

function cargarToken() {
    if (file_exists("token.json")) {
        return json_decode(file_get_contents("token.json"), true);
    }
    return null;
}

function obtenerAccessTokenDesdeRefreshToken($refresh_token) {
    $client_id = "WK.ES.A3WebApi.44480.S";
    $client_secret = "JcWu2z4dR5Q3";
    $token_url = "https://login.wolterskluwer.eu/auth/core/connect/token";

    $data = [
        'grant_type' => 'refresh_token',
        'refresh_token' => $refresh_token,
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
    curl_close($ch);

    if ($httpcode !== 200) {
        echo "Error al refrescar token. Código HTTP $httpcode<br>";
        return null;
    }

    return json_decode($response, true);
}

function obtenerAccessToken($code = null) {
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
    curl_close($ch);

    if ($httpcode !== 200) {
        echo "Error al obtener token. Código HTTP $httpcode<br>";
        return null;
    }

    return json_decode($response, true);
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
        echo "Error HTTP $httpcode al obtener vacaciones.<br>";
        return null;
    }

    $data = json_decode($response, true);

    return $data ?: [];
}

function buscarPorCIF($vacaciones, $cif) {
    foreach ($vacaciones as $vacacion) {
        if (isset($vacacion['employeeIdentification']['nif']) &&
            trim($vacacion['employeeIdentification']['nif']) === trim($cif)) {
            return $vacacion;
        }
    }
    return null;
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

// Obtener token
$tokenData = cargarToken();

if ($tokenData && isset($tokenData["refresh_token"])) {

    $nuevoTokenData = obtenerAccessTokenDesdeRefreshToken($tokenData["refresh_token"]);
    if ($nuevoTokenData && isset($nuevoTokenData["access_token"])) {
        guardarToken($nuevoTokenData);
        $access_token = $nuevoTokenData["access_token"];
    } else {
        echo "No se pudo refrescar el token. Reautenticación necesaria con código de autorización.<br>";
        exit;
    }
} else {
    $code = "Code";
    $nuevoTokenData = obtenerAccessToken($code);
    if ($nuevoTokenData && isset($nuevoTokenData["access_token"])) {
        guardarToken($nuevoTokenData);
        $access_token = $nuevoTokenData["access_token"];
    } else {
        echo "No se pudo obtener el token inicial.<br>";
        exit;
    }
}

// Configuración
$companyCode = 100;
$subscriptionKey = "ej";

// Obtener vacaciones
$vacaciones = obtenerVacacionesEmpleados($companyCode, $access_token, $subscriptionKey);

// Procesar CSV
if (isset($_FILES['archivo']) && $_FILES['archivo']['error'] === UPLOAD_ERR_OK) {
    $tmpName = $_FILES['archivo']['tmp_name'];
    $archivo = fopen($tmpName, "r");

    if ($archivo !== FALSE) {
        while (($fila = fgetcsv($archivo, 1000, ";")) !== FALSE) {
            $id = $fila[0];
            $cif = trim($fila[1]);
            $apellidos = $fila[2];
            $nombre = $fila[3];
            $diasActualizados = (int)$fila[4];

            if ($diasActualizados <= 0) {
                continue;
            }

            $registro = buscarPorCIF($vacaciones, $cif);

            if ($registro) {
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