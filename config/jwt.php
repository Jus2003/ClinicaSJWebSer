<?php
// config/jwt.php
return [
    'secret_key' => 'sistema_clinica_jwt_secret_key_2024_muy_segura_fixed_salt_12345',
    'algorithm' => 'HS256',
    'expiration_time' => 3600, // 1 hora
    'issuer' => 'Sistema-Clinica-API',
    'audience' => 'Sistema-Clinica-Frontend'
];