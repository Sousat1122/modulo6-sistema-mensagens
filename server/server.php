<?php
// Servidor TCP - Sistema de Mensagens

require_once 'config.php';
require_once 'database.php';

echo "\n";
echo "╔════════════════════════════════════════════════════╗\n";
echo "║   SERVIDOR DE MENSAGENS - TCP/IP                   ║\n";
echo "╚════════════════════════════════════════════════════╝\n\n";

$config = require 'config.php';
$db = new Database($config['db_path']);

// Criar socket do servidor
$server_socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
if (!$server_socket) {
    die("[ERRO] Falha ao criar socket: " . socket_strerror(socket_last_error()) . "\n");
}

// Permitir reutilizar porta
socket_set_option($server_socket, SOL_SOCKET, SO_REUSEADDR, 1);

// Fazer bind
if (!socket_bind($server_socket, $config['host'], $config['port'])) {
    die("[ERRO] Falha ao fazer bind: " . socket_strerror(socket_last_error()) . "\n");
}

// Escutar conexões
if (!socket_listen($server_socket, $config['max_clients'])) {
    die("[ERRO] Falha ao escutar: " . socket_strerror(socket_last_error()) . "\n");
}

echo "[✓] Servidor iniciado em {$config['host']}:{$config['port']}\n";
echo "[✓] Aguardando conexões...\n\n";

$clients = [];

while (true) {
    // Aceitar conexão
    $client = @socket_accept($server_socket);
    
    if ($client === false) {
        continue;
    }
    
    $clients[] = $client;
    $client_ip = '';
    socket_getpeername($client, $client_ip);
    echo "[CONEXÃO] Cliente conectado: $client_ip\n";
    
    // Processar cliente em modo não-bloqueante
    socket_set_nonblock($client);
    
    // Ler dados do cliente
    $data = @socket_read($client, $config['buffer_size']);
    
    if ($data) {
        // Decodificar comando JSON
        $command = json_decode(trim($data), true);
        
        if (!$command) {
            echo "[ERRO] Comando inválido de $client_ip\n";
            continue;
        }
        
        $type = $command['type'] ?? null;
        $username = $command['username'] ?? null;
        
        switch ($type) {
            case 'register':
                if ($username) {
                    $db->addUser($username);
                    $response = json_encode([
                        'status' => 'success',
                        'message' => "Utilizador $username registado"
                    ]);
                    echo "[REGISTO] Novo utilizador: $username\n";
                } else {
                    $response = json_encode(['status' => 'error', 'message' => 'Username vazio']);
                }
                socket_write($client, $response . "\n");
                break;
            
            case 'send':
                $to_user = $command['to'] ?? null;
                $content = $command['content'] ?? null;
                
                if ($username && $to_user && $content) {
                    $db->addUser($username);
                    $db->updateLastSeen($username);
                    $db->saveMessage($username, $to_user, $content);
                    
                    $response = json_encode([
                        'status' => 'success',
                        'message' => "Mensagem enviada para $to_user"
                    ]);
                    echo "[MENSAGEM] $username → $to_user\n";
                } else {
                    $response = json_encode(['status' => 'error', 'message' => 'Dados incompletos']);
                }
                socket_write($client, $response . "\n");
                break;
            
            case 'get_messages':
                if ($username) {
                    $db->updateLastSeen($username);
                    $messages = $db->getMessages($username);
                    $response = json_encode([
                        'status' => 'success',
                        'messages' => $messages
                    ]);
                    echo "[CONSULTA] $username pediu mensagens\n";
                } else {
                    $response = json_encode(['status' => 'error', 'message' => 'Username vazio']);
                }
                socket_write($client, $response . "\n");
                break;
            
            case 'get_users':
                $users = $db->getAllUsers();
                $response = json_encode([
                    'status' => 'success',
                    'users' => $users
                ]);
                socket_write($client, $response . "\n");
                break;
            
            case 'get_conversation':
                $other_user = $command['with'] ?? null;
                if ($username && $other_user) {
                    $messages = $db->getConversation($username, $other_user);
                    $response = json_encode([
                        'status' => 'success',
                        'messages' => $messages
                    ]);
                } else {
                    $response = json_encode(['status' => 'error', 'message' => 'Dados incompletos']);
                }
                socket_write($client, $response . "\n");
                break;
            
            default:
                $response = json_encode(['status' => 'error', 'message' => 'Tipo de comando desconhecido']);
                socket_write($client, $response . "\n");
        }
    }
    
    socket_close($client);
    
    // Remover cliente da lista
    $key = array_search($client, $clients);
    if ($key !== false) {
        unset($clients[$key]);
    }
}

socket_close($server_socket);
echo "\n[✓] Servidor encerrado\n";
