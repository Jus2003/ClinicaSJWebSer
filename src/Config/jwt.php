<?php
// config/jwt.php
return [
    'secret_key' => 'tu_clave_secreta_muy_segura_aqui_2024_sistema_clinica', // Cambia por una clave más segura
    'algorithm' => 'HS256',
    'expiration_time' => 3600, // 1 hora en segundos
    'issuer' => 'Sistema-Clinica-API',
    'audience' => 'Sistema-Clinica-Frontend'
];