<?php
// Gestão da Base de Dados SQLite

class Database {
    private $db;
    private $db_path;
    
    public function __construct($db_path) {
        $this->db_path = $db_path;
        $this->connect();
        $this->initialize();
    }
    
    private function connect() {
        try {
            $this->db = new SQLite3($this->db_path);
            $this->db->busyTimeout(5000);
            echo "[DB] Conectado a: {$this->db_path}\n";
        } catch (Exception $e) {
            die("[ERRO] Falha ao conectar BD: " . $e->getMessage());
        }
    }
    
    private function initialize() {
        // Tabela de utilizadores
        $sql_users = "CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT UNIQUE NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            last_seen TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        
        // Tabela de mensagens
        $sql_messages = "CREATE TABLE IF NOT EXISTS messages (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            from_user TEXT NOT NULL,
            to_user TEXT NOT NULL,
            content TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            read_status INTEGER DEFAULT 0
        )";
        
        try {
            $this->db->exec($sql_users);
            $this->db->exec($sql_messages);
            echo "[DB] Tabelas inicializadas\n";
        } catch (Exception $e) {
            echo "[ERRO] Falha ao inicializar tabelas: " . $e->getMessage() . "\n";
        }
    }
    
    public function addUser($username) {
        $username = $this->db->escapeString($username);
        $sql = "INSERT OR IGNORE INTO users (username) VALUES ('$username')";
        
        if ($this->db->exec($sql)) {
            return true;
        }
        return false;
    }
    
    public function saveMessage($from, $to, $content) {
        $from = $this->db->escapeString($from);
        $to = $this->db->escapeString($to);
        $content = $this->db->escapeString($content);
        
        $sql = "INSERT INTO messages (from_user, to_user, content) VALUES ('$from', '$to', '$content')";
        
        if ($this->db->exec($sql)) {
            return true;
        }
        return false;
    }
    
    public function getMessages($username) {
        $username = $this->db->escapeString($username);
        $sql = "SELECT * FROM messages WHERE to_user = '$username' ORDER BY created_at DESC LIMIT 50";
        
        $result = $this->db->query($sql);
        $messages = [];
        
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $messages[] = $row;
        }
        
        return $messages;
    }
    
    public function getConversation($user1, $user2) {
        $user1 = $this->db->escapeString($user1);
        $user2 = $this->db->escapeString($user2);
        
        $sql = "SELECT * FROM messages 
                WHERE (from_user = '$user1' AND to_user = '$user2') 
                   OR (from_user = '$user2' AND to_user = '$user1') 
                ORDER BY created_at ASC";
        
        $result = $this->db->query($sql);
        $messages = [];
        
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $messages[] = $row;
        }
        
        return $messages;
    }
    
    public function getAllUsers() {
        $sql = "SELECT * FROM users ORDER BY last_seen DESC";
        $result = $this->db->query($sql);
        $users = [];
        
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $users[] = $row;
        }
        
        return $users;
    }
    
    public function updateLastSeen($username) {
        $username = $this->db->escapeString($username);
        $sql = "UPDATE users SET last_seen = CURRENT_TIMESTAMP WHERE username = '$username'";
        return $this->db->exec($sql);
    }
    
    public function getUnreadCount($username) {
        $username = $this->db->escapeString($username);
        $sql = "SELECT COUNT(*) as count FROM messages WHERE to_user = '$username' AND read_status = 0";
        $result = $this->db->querySingle($sql, true);
        return $result['count'] ?? 0;
    }
    
    public function markAsRead($message_id) {
        $sql = "UPDATE messages SET read_status = 1 WHERE id = $message_id";
        return $this->db->exec($sql);
    }
    
    public function close() {
        if ($this->db) {
            $this->db->close();
        }
    }
}
