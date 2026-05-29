<?php
// Cliente CLI - Sistema de Mensagens

define('SERVER_HOST', '127.0.0.1');
define('SERVER_PORT', 5555);
define('DB_PATH', __DIR__ . '/../../database/messages.db');

require_once '../../server/database.php';

echo "\n";
echo "╔════════════════════════════════════════════════════╗\n";
echo "║   CLIENTE DE MENSAGENS - CLI/Terminal              ║\n";
echo "╚════════════════════════════════════════════════════╝\n\n";

// Inicializar BD
$db = new Database(DB_PATH);

// Pedir username
echo "Nome de utilizador: ";
$username = trim(fgets(STDIN));

if (strlen($username) < 3) {
    die("[✗] Username deve ter pelo menos 3 caracteres\n");
}

$username = preg_replace('/[^a-zA-Z0-9_]/', '', $username);

// Registar utilizador
$db->addUser($username);
echo "\n[✓] Registado como: $username\n\n";

// Menu principal
while (true) {
    echo "\n" . str_repeat("─", 40) . "\n";
    echo "1. Ver contactos\n";
    echo "2. Ver conversas\n";
    echo "3. Enviar mensagem\n";
    echo "4. Sair\n";
    echo str_repeat("─", 40) . "\n";
    echo "Escolha uma opção: ";
    
    $choice = trim(fgets(STDIN));
    
    switch ($choice) {
        case '1':
            showContacts($db, $username);
            break;
        
        case '2':
            showConversations($db, $username);
            break;
        
        case '3':
            sendMessage($db, $username);
            break;
        
        case '4':
            echo "\n[✓] Até logo!\n\n";
            exit;
        
        default:
            echo "[✗] Opção inválida\n";
    }
}

function showContacts($db, $username) {
    echo "\n📋 CONTACTOS:\n\n";
    
    $users = $db->getAllUsers();
    
    if (empty($users)) {
        echo "Nenhum utilizador registado\n";
        return;
    }
    
    foreach ($users as $user) {
        if ($user['username'] === $username) continue;
        
        $last_seen = new DateTime($user['last_seen']);
        $now = new DateTime();
        $diff = $last_seen->diff($now);
        
        $time_str = "";
        if ($diff->days > 0) {
            $time_str = $diff->days . " dias atrás";
        } elseif ($diff->h > 0) {
            $time_str = $diff->h . " horas atrás";
        } elseif ($diff->i > 0) {
            $time_str = $diff->i . " minutos atrás";
        } else {
            $time_str = "Agora";
        }
        
        printf("  • %-20s (Último acesso: %s)\n", $user['username'], $time_str);
    }
}

function showConversations($db, $username) {
    echo "\n💬 CONVERSAS:\n\n";
    
    $users = $db->getAllUsers();
    
    if (empty($users)) {
        echo "Nenhuma conversa disponível\n";
        return;
    }
    
    $users_with_messages = [];
    
    foreach ($users as $user) {
        if ($user['username'] === $username) continue;
        
        $messages = $db->getConversation($username, $user['username']);
        if (!empty($messages)) {
            $users_with_messages[$user['username']] = count($messages);
        }
    }
    
    if (empty($users_with_messages)) {
        echo "Nenhuma conversa para exibir\n";
        return;
    }
    
    foreach ($users_with_messages as $other_user => $msg_count) {
        printf("  • %-20s (%d mensagens)\n", $other_user, $msg_count);
    }
    
    echo "\nQual utilizador? ";
    $other_user = trim(fgets(STDIN));
    
    if (in_array($other_user, array_keys($users_with_messages))) {
        showConversation($db, $username, $other_user);
    } else {
        echo "[✗] Utilizador não encontrado\n";
    }
}

function showConversation($db, $user1, $user2) {
    echo "\n💌 CONVERSA COM $user2:\n\n";
    
    $messages = $db->getConversation($user1, $user2);
    
    foreach ($messages as $msg) {
        $sender = $msg['from_user'];
        $content = $msg['content'];
        $time = date('d/m H:i', strtotime($msg['created_at']));
        
        $prefix = ($sender === $user1) ? "TU" : "$user2";
        printf("[%s] %s: %s\n", $time, $prefix, $content);
    }
}

function sendMessage($db, $username) {
    echo "\nDestinatário: ";
    $to_user = trim(fgets(STDIN));
    
    if (empty($to_user) || strlen($to_user) < 3) {
        echo "[✗] Nome inválido\n";
        return;
    }
    
    echo "Mensagem: ";
    $content = trim(fgets(STDIN));
    
    if (empty($content)) {
        echo "[✗] Mensagem vazia\n";
        return;
    }
    
    // Guardar na BD
    $db->addUser($username);
    $db->updateLastSeen($username);
    $db->saveMessage($username, $to_user, $content);
    
    // Enviar ao servidor TCP
    $command = json_encode([
        'type' => 'send',
        'username' => $username,
        'to' => $to_user,
        'content' => $content
    ]);
    
    $socket = @socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
    if ($socket) {
        if (@socket_connect($socket, SERVER_HOST, SERVER_PORT)) {
            @socket_write($socket, $command);
            $response = @socket_read($socket, 1024);
            @socket_close($socket);
            echo "\n[✓] Mensagem enviada para $to_user\n";
        } else {
            echo "\n[✗] Não foi possível conectar ao servidor\n";
        }
    } else {
        echo "\n[✗] Erro ao criar socket\n";
    }
}
