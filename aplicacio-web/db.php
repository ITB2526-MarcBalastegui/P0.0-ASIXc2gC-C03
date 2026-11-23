<?php
// Ubicació a W-N01: /var/www/config/db.php (fora de /var/www/html)
// NO pujar aquest fitxer al repositori.
return [
    'host' => 'IP_B-N01',      // B-N01 (IP directa, no depèn del DNS)
    'db'   => 'equipaments',
    'user' => 'USER',         // usuari de només lectura
    'pass' => 'PASSWORD',
];
