<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Subir archivo</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f6f8;
            margin: 0;
            padding: 0;
            display: flex;
            height: 100vh;
            align-items: center;
            justify-content: center;
        }

        .container {
            background: #fff;
            padding: 2rem 3rem;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            text-align: center;
            max-width: 400px;
            width: 100%;
        }

        h2 {
            margin-bottom: 1.5rem;
            color: #333;
        }

        form {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 1rem;
        }

        .file-upload {
            border: 2px dashed #ccc;
            border-radius: 10px;
            background: #fafafa;
            width: 100%;
            height: 150px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: border-color 0.3s ease;
            position: relative;
            overflow: hidden;
            padding: 1rem;
            text-align: center;
        }

        .file-upload:hover {
            border-color: #007bff;
        }

        .file-upload input[type="file"] {
            opacity: 0;
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            cursor: pointer;
        }

        .file-upload span {
            color: #666;
            font-size: 16px;
            pointer-events: none;
            max-width: 100%;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            display: inline-block;
        }

        input[type="submit"] {
            background-color: #007bff;
            border: none;
            color: white;
            padding: 10px 20px;
            font-size: 16px;
            border-radius: 6px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        input[type="submit"]:hover:enabled {
            background-color: #0056b3;
        }

        input[type="submit"]:disabled {
            background-color: #ccc;
            cursor: not-allowed;
        }

        .remove-btn {
            background-color: #dc3545;
            color: white;
            border: none;
            padding: 6px 12px;
            font-size: 14px;
            border-radius: 6px;
            cursor: pointer;
            display: none;
        }

        .remove-btn:hover {
            background-color: #c82333;
        }

        @media (max-width: 500px) {
            .container {
                padding: 1.5rem 2rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Subir archivo</h2>
        <form action="procesar.php" method="POST" enctype="multipart/form-data">
            <label class="file-upload" id="file-label">
                <span id="file-name">Haz clic o arrastra un archivo aquí</span>
                <input id="file-input" type="file" name="archivo" accept=".csv" required onchange="mostrarNombre(this)">
            </label>
            <button type="button" id="remove-button" class="remove-btn" onclick="quitarArchivo()">Quitar archivo</button>
            <input id="submit-button" type="submit" value="Procesar" disabled>
        </form>
    </div>

    <script>
        const fileNameSpan = document.getElementById('file-name');
        const submitBtn = document.getElementById('submit-button');
        const removeBtn = document.getElementById('remove-button');
        const fileLabel = document.getElementById('file-label');

        function mostrarNombre(input) {
            if (input.files.length > 0) {
                const file = input.files[0];
                if (!file.name.toLowerCase().endsWith(".csv")) {
                    alert("Archivo incompatible");
                    quitarArchivo();
                    return;
                }

                fileNameSpan.textContent = file.name;
                submitBtn.disabled = false;
                removeBtn.style.display = 'inline-block';
            } else {
                quitarArchivo();
            }
        }

        function quitarArchivo() {
            // Eliminar input viejo
            const oldInput = document.getElementById('file-input');
            oldInput.remove();

            // Crear uno nuevo
            const newInput = document.createElement('input');
            newInput.type = 'file';
            newInput.name = 'archivo';
            newInput.id = 'file-input';
            newInput.accept = '.csv';
            newInput.required = true;
            newInput.onchange = function () {
                mostrarNombre(this);
            };

            // Insertar input
            fileLabel.appendChild(newInput);

            // Reset UI
            fileNameSpan.textContent = "Haz clic o arrastra un archivo aquí";
            submitBtn.disabled = true;
            removeBtn.style.display = 'none';
        }
    </script>
</body>
</html>
