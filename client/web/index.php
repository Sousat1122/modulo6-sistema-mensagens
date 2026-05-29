<?php
// Interface Web - Dashboard de Mensagens

// Configurações
define('SERVER_HOST', '127.0.0.1');
define('SERVER_PORT', 5555);
define('DB_PATH', __DIR__ . '/../../database/messages.db');

// Carregar classe de BD
require_once '../../server/database.php';

$db = new Database(DB_PATH);
$username = $_SESSION['username'] ?? $_COOKIE['username'] ?? null;
$current_chat = $_GET['chat'] ?? null;

// Se não tem utilizador, mostrar login
if (!$username) {
    if ($_POST['login'] ?? false) {
        $username = preg_replace('/[^a-zA-Z0-9_]/', '', $_POST['username'] ?? '');
        if (strlen($username) > 2) {
            setcookie('username', $username, time() + (86400 * 30));
            $_COOKIE['username'] = $username;
            header('Location: index.php');
            exit;
        }
    }
    ?>
    <!DOCTYPE html>
    <html lang="pt">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Sistema de Mensagens</title>
        <link rel="stylesheet" href="styles.css">
    </head>
    <body>
        <div class="login-container">
            <div class="login-box">
                <h1>💬 Mensagens</h1>
                <p>Sistema de Comunicação em Rede</p>
                
                <form method="POST">
                    <input type="text" name="username" placeholder="Nome de utilizador" required 
                           pattern="[a-zA-Z0-9_]{3,}" title="Mínimo 3 caracteres (letras, números, _)">
                    <button type="submit" name="login" value="1">Entrar</button>
                </form>
                
                <p style="font-size: 0.9em; color: #666; margin-top: 20px;">
                    ℹ️ Use um nome único para se identificar no sistema
                </p>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Processar ações
if ($_POST['action'] ?? false) {
    $action = $_POST['action'];
    
    if ($action === 'send') {
        $to_user = preg_replace('/[^a-zA-Z0-9_]/', '', $_POST['to_user'] ?? '');
        $content = trim($_POST['content'] ?? '');
        
        if ($to_user && strlen($content) > 0) {
            $db->addUser($username);
            $db->updateLastSeen($username);
            $db->saveMessage($username, $to_user, $content);
            
            // Comunicar com servidor TCP
            $command = json_encode([
                'type' => 'send',
                'username' => $username,
                'to' => $to_user,
                'content' => $content
            ]);
            
            $socket = @socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
            if ($socket) {
                @socket_connect($socket, SERVER_HOST, SERVER_PORT);
                @socket_write($socket, $command);
                @socket_close($socket);
            }
            
            header('Location: index.php?chat=' . urlencode($to_user));
            exit;
        }
    }
    
    if ($action === 'logout') {
        setcookie('username', '', time() - 3600);
        header('Location: index.php');
        exit;
    }
}

// Carregar dados
$users = $db->getAllUsers();
$messages = [];
if ($current_chat) {
    $messages = $db->getConversation($username, $current_chat);
}

?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mensagens - <?php echo htmlspecialchars($username); ?></title>
    <link rel="stylesheet" href="styles.css">
    <meta http-equiv="refresh" content="3">
</head>
<body>
    <div class="container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2>💬 Mensagens</h2>
                <p class="user-info">Utilizador: <strong><?php echo htmlspecialchars($username); ?></strong></p>
            </div>
            
            <div class="sidebar-section">
                <h3>Contactos (<?php echo count($users); ?>)</h3>
                <div class="contacts-list">
                    <?php foreach ($users as $user): ?>
                        <?php if ($user['username'] !== $username): ?>
                            <a href="index.php?chat=<?php echo urlencode($user['username']); ?>" 
                               class="contact-item <?php echo ($current_chat === $user['username'] ? 'active' : ''); ?>">
                                <div class="contact-name"><?php echo htmlspecialchars($user['username']); ?></div>
                                <div class="contact-time"><?php echo date('H:i', strtotime($user['last_seen'])); ?></div>
                            </a>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="sidebar-footer">
                <form method="POST">
                    <button type="submit" name="action" value="logout" class="logout-btn">Sair</button>
                </form>
            </div>
        </aside>
        
        <!-- Chat Area -->
        <main class="chat-area">
            <?php if ($current_chat): ?>
                <div class="chat-header">
                    <h2>Conversa com <?php echo htmlspecialchars($current_chat); ?></h2>
                </div>
                
                <div class="messages-container">
                    <?php if (empty($messages)): ?>
                        <p class="no-messages">Nenhuma mensagem ainda. Comece a conversa!</p>
                    <?php else: ?>
                        <?php foreach ($messages as $msg): ?>
                            <div class="message <?php echo ($msg['from_user'] === $username ? 'sent' : 'received'); ?>">
                                <div class="message-content"><?php echo htmlspecialchars($msg['content']); ?></div>
                                <div class="message-time"><?php echo date('d/m H:i', strtotime($msg['created_at'])); ?></div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <form method="POST" class="message-form">
                    <input type="hidden" name="action" value="send">
                    <input type="hidden" name="to_user" value="<?php echo htmlspecialchars($current_chat); ?>">
                    <input type="text" name="content" placeholder="Escreva uma mensagem..." required>
                    <button type="submit">Enviar</button>
                </form>
            <?php else: ?>
                <div class="chat-empty">
                    <h3>👉 Selecione um contacto para começar</h3>
                    <p>Escolha um utilizador da lista para enviar mensagens</p>
                </div>
            <?php endif; ?>
        </main>
    </div>
</body>
</html>
