<?php
// Configuração do Sistema de Mensagens

define('SERVER_HOST', '127.0.0.1');
define('SERVER_PORT', 5555);
define('DB_PATH', __DIR__ . '/../database/messages.db');
define('MAX_CLIENTS', 50);
define('BUFFER_SIZE', 1024);
define('TIMEOUT', 30);

// Criar diretório de base de dados se não existir
if (!is_dir(dirname(DB_PATH))) {
    mkdir(dirname(DB_PATH), 0755, true);
}

return [
    'host' => SERVER_HOST,
    'port' => SERVER_PORT,
    'db_path' => DB_PATH,
    'max_clients' => MAX_CLIENTS,
    'buffer_size' => BUFFER_SIZE,
    'timeout' => TIMEOUT
];
