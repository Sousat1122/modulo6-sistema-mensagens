# Sistema de Mensagens entre Utilizadores

## Objetivo da Aplicação

Desenvolver uma aplicação de comunicação em rede baseada no modelo cliente-servidor que permite o envio e receção de mensagens entre múltiplos utilizadores. A aplicação utiliza sockets TCP para estabelecer comunicação fiável entre um servidor centralizado e vários clientes conectados.

## Funcionamento

### Arquitetura

- **Servidor TCP**: Gerencia as conexões de múltiplos clientes, armazena mensagens em base de dados SQLite e as distribui
- **Cliente Web**: Interface web para enviar mensagens, ver histórico e listar utilizadores online
- **Base de Dados**: SQLite para persistência de mensagens e utilizadores

### Fluxo Principal

1. **Utilizador registra-se** no sistema web
2. **Servidor** cria/atualiza a entrada do utilizador na BD
3. **Utilizador envia mensagem** para outro utilizador
4. **Servidor** armazena a mensagem na BD
5. **Recetor** vê a mensagem (em tempo real ou na próxima consulta)
6. **Histórico** é mantido e acessível

## Requisitos Cumpridos

✅ Utilizar comunicação em rede (TCP)  
✅ Implementar um servidor funcional  
✅ Implementar clientes (Web + CLI)  
✅ Permitir envio e receção de dados  
✅ Modelo cliente-servidor  
✅ Troca de informação entre múltiplos sistemas  

## Estrutura do Projeto

```
.
├── server/
│   ├── server.php          # Servidor TCP
│   ├── config.php          # Configurações
│   └── database.php        # Gestão da BD SQLite
├── client/
│   ├── web/
│   │   ├── index.php       # Dashboard web
│   │   └── styles.css      # Estilos
│   └── cli/
│       └── client.php      # Cliente CLI
├── database/
│   └── messages.db         # Base de dados SQLite
└── README.md
```

## Como Usar

### 1. Iniciar o Servidor

```bash
cd server
php server.php
```

O servidor iniciará em `127.0.0.1:5555`

### 2. Usar a Interface Web

Abra seu navegador:
```
http://localhost/modulo6-sistema-mensagens/client/web/
```

OU com servidor PHP embutido:
```bash
php -S localhost:8000 -t client/web/
```

### 3. Usar o Cliente CLI (Terminal)

```bash
php client/cli/client.php
```

## Protocolo de Comunicação TCP

Os comandos são enviados em formato JSON:

### Registar Utilizador
```json
{
  "type": "register",
  "username": "joao"
}
```

### Enviar Mensagem
```json
{
  "type": "send",
  "username": "joao",
  "to": "maria",
  "content": "Olá!"
}
```

### Obter Mensagens
```json
{
  "type": "get_messages",
  "username": "joao"
}
```

### Obter Utilizadores
```json
{
  "type": "get_users"
}
```

### Obter Conversa
```json
{
  "type": "get_conversation",
  "username": "joao",
  "with": "maria"
}
```

## Funcionalidades

### Interface Web
- ✅ Login simples
- ✅ Lista de contactos com status online
- ✅ Visualizar conversas por contacto
- ✅ Enviar mensagens em tempo real
- ✅ Histórico de mensagens
- ✅ Auto-refresh a cada 3 segundos
- ✅ Design responsivo

### Cliente CLI
- ✅ Menu interativo
- ✅ Ver contactos
- ✅ Ver conversas
- ✅ Enviar mensagens
- ✅ Timestamp de mensagens

### Servidor
- ✅ Gerenciamento de conexões TCP
- ✅ Processamento de comandos JSON
- ✅ Armazenamento em SQLite
- ✅ Suporte para múltiplos clientes simultâneos
- ✅ Logs de operações

## Dificuldades Encontradas

### 1. **Sincronização de dados em tempo real**
   - **Solução**: Implementado sistema de polling (refresh automático a cada 3 segundos na web e consultas na CLI)

### 2. **Persistência de múltiplas conexões simultâneas**
   - **Solução**: Utilizado SQLite com suporte a múltiplas conexões e timeout configurável

### 3. **Comunicação entre cliente e servidor**
   - **Solução**: Protocolo JSON simples baseado em sockets TCP

### 4. **Interface user-friendly**
   - **Solução**: Design moderno com CSS gradiente e layout flexível responsivo

### 5. **Autenticação**
   - **Solução**: Sistema de cookies para manter sessão do utilizador na web

## Requisitos do Sistema

- PHP 7.4 ou superior
- Extensão SQLite3 (geralmente incluída por padrão)
- Suporte a Sockets (geralmente incluído)
- Navegador web moderno (para interface web)
- Terminal/CLI (para cliente de linha de comando)

## Testes Realizados

✅ Servidor inicia e escuta conexões
✅ Múltiplos clientes web simultâneos
✅ Cliente CLI funcional
✅ Envio e receção de mensagens
✅ Armazenamento em BD SQLite
✅ Histórico de conversas
✅ Lista de utilizadores atualizada
✅ Responsividade em dispositivos móveis

## Notas Técnicas

- **Sem dependências externas**: Apenas PHP puro com SQLite3
- **Escalabilidade**: Suporta até 50 clientes simultâneos (configurável)
- **Segurança básica**: Validação de entrada e escape de queries SQL
- **Performance**: Índices nas tabelas para consultas rápidas

## Melhorias Futuras

- Autenticação com hash de senha
- Encriptação de mensagens
- Notificações push
- Salas de chat em grupo
- Upload de ficheiros
- WebSocket para tempo real real
- Cache de mensagens

## Autor

Desenvolvido para Módulo 6 - Programação de Sistemas de Comunicação

## Licença

Livre para uso educacional
