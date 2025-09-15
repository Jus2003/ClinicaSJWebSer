<?php
// config/jwt.php
return [
    'secret_key' => 'sistema_clinica_jwt_secret_key_2024_muy_segura_!' . time(), // Clave única
    'algorithm' => 'HS256',
    'expiration_time' => 3600, // 1 hora en segundos
    'issuer' => 'Sistema-Clinica-API',
    'audience' => 'Sistema-Clinica-Frontend'
];