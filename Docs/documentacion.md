# Documentación de Procesos - Integración API Wolters Kluwer (A3)

## 📌 Objetivo

Gestionar los días de vacaciones de empleados mediante la API de A3 Wolters Kluwer, permitiendo:

- Autenticación OAuth2 con almacenamiento y refresco automático de tokens.
- Obtención y actualización de ausencias (vacaciones) por empleado.
- Lectura de datos desde archivo CSV.
- Aplicación de nuevos valores de días de vacaciones de forma dinámica.

---

## 🔐 Autenticación y Tokens

### 1. Proceso de Autenticación

La autenticación se realiza mediante OAuth2 con `authorization_code`.

- **Parámetros utilizados**:
  - `client_id`: provisto por WK
  - `client_secret`: provisto por WK
  - `redirect_uri`: URI registrada para el flujo de autorización
  - `code`: Código único recibido tras autorizar la app

### 2. Guardado de Tokens

- Al recibir un `access_token` y `refresh_token`, estos se guardan automáticamente en un archivo `token.json`.
- Este archivo es usado para futuras peticiones, sin necesidad de volver a ingresar el `code`.

### 3. Refresco del Token

- Si el `access_token` ha caducado, se utiliza el `refresh_token` para obtener uno nuevo.
- Este proceso se realiza automáticamente sin intervención del usuario.

---

## 📥 Entrada de Datos

### Formato del Archivo CSV

El sistema acepta un archivo CSV con el siguiente formato (separado por `;`):

```csv
ID;NIF;APELLIDOS;NOMBRE;DIAS
001;12345678A;Pérez García;Laura;15
