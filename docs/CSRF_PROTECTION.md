# CSRF Protection - Cookie-Based Implementation

## Overview

O Lalaz Framework implementa proteção CSRF usando cookies HttpOnly com SameSite=Strict, proporcionando segurança robusta contra ataques Cross-Site Request Forgery.

## Características

- ✅ **HttpOnly Cookies**: Tokens armazenados em cookies inacessíveis via JavaScript
- ✅ **SameSite=Strict**: Previne envio de cookies em requisições cross-site
- ✅ **Token Rotation**: Tokens rodam automaticamente após validações bem-sucedidas
- ✅ **Timing-Safe Comparison**: Usa `hash_equals()` para prevenir timing attacks
- ✅ **HTTPS Detection**: Automaticamente detecta conexões seguras
- ✅ **Header Support**: Suporta tokens via header para requisições AJAX

## Uso Básico

### 1. Formulários HTML

Use o helper `csrfField()` em seus templates Twig:

```twig
<form method="POST" action="/users/create">
    {{ csrfField() | raw }}
    
    <input type="text" name="name" required>
    <input type="email" name="email" required>
    <button type="submit">Criar Usuário</button>
</form>
```

Isso gera:
```html
<input type="hidden" name="csrfToken" value="abc123...">
```

### 2. Requisições AJAX

Para requisições AJAX, use o header `X-CSRF-Token`:

```javascript
// Obter o token do cookie (via JavaScript helper ou meta tag)
const token = getCsrfToken();

fetch('/api/users', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-CSRF-Token': token
    },
    body: JSON.stringify({ name: 'John', email: 'john@example.com' })
});
```

### 3. Incluir Token em Meta Tag (Recomendado para SPAs)

No seu layout base:

```twig
<!DOCTYPE html>
<html>
<head>
    <meta name="csrf-token" content="{{ csrfToken() }}">
</head>
<body>
    <!-- Seu conteúdo -->
</body>
</html>
```

Depois em JavaScript:

```javascript
function getCsrfToken() {
    return document.querySelector('meta[name="csrf-token"]').getAttribute('content');
}
```

## Validação Automática

A validação CSRF é **automática** para métodos: `POST`, `PUT`, `PATCH`.

```php
class UserController extends Controller
{
    public function store(Request $req, Response $res): void
    {
        // CSRF já foi validado automaticamente pelo Router
        // Se chegou aqui, o token é válido
        
        $req->validateCsrfToken(); // Opcional - já feito pelo framework
        
        $data = $req->body();
        // ... processar dados
    }
}
```

## API Programática

### Gerar Token Manualmente

```php
use Lalaz\Security\CsrfProtection;

$token = CsrfProtection::generateToken();
```

### Obter Token Atual

```php
$token = CsrfProtection::getToken();
```

### Validar Token Manualmente

```php
$isValid = CsrfProtection::validateToken(
    $request->body(),
    $request->getHeaders()
);

if (!$isValid) {
    throw HttpException::csrfMismatch('Invalid CSRF token');
}
```

### Rotacionar Token

```php
// Rotaciona o token (automático após validações bem-sucedidas)
$newToken = CsrfProtection::rotateToken();
```

### Deletar Token

```php
// Útil ao fazer logout
CsrfProtection::deleteToken();
```

## Configuração de Segurança

### Cookies Seguros (Produção)

O framework detecta automaticamente HTTPS e configura cookies seguros:

```php
// Configuração automática baseada em:
- $_SERVER['HTTPS'] === 'on'
- $_SERVER['SERVER_PORT'] === 443
- $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https'
```

### SameSite=Strict

Cookies CSRF sempre usam `SameSite=Strict`:

```
Set-Cookie: __csrf_token=abc123...; 
            Path=/; 
            HttpOnly; 
            Secure; 
            SameSite=Strict; 
            Max-Age=86400
```

## Tratamento de Erros

Quando um token CSRF é inválido, o framework lança `HttpException::csrfMismatch()`:

```php
try {
    $request->validateCsrfToken();
} catch (HttpException $e) {
    // HTTP 419 - CSRF Token Mismatch
    // Dados forenses incluídos:
    $context = $e->getContext();
    // ['ip' => '192.168.1.100', 'user_agent' => 'Mozilla/5.0 ...']
}
```

Response automático:
- **JSON API**: `{"error": "Invalid CSRF token", "code": 419}`
- **HTML**: Página de erro renderizada

## Exemplo Completo: Formulário de Login

### Controller

```php
class AuthController extends Controller
{
    public function showLogin(Request $req, Response $res): void
    {
        // Token gerado automaticamente e disponível na view
        $res->render('auth/login');
    }

    public function login(Request $req, Response $res): void
    {
        // CSRF validado automaticamente antes deste método
        
        $credentials = $req->body();
        
        if ($this->authenticate($credentials)) {
            // Token rotacionado automaticamente
            $res->redirect('/dashboard');
        } else {
            $res->redirect('/login')->with('error', 'Credenciais inválidas');
        }
    }
}
```

### View (Twig)

```twig
{# views/auth/login.twig #}
<!DOCTYPE html>
<html>
<head>
    <meta name="csrf-token" content="{{ csrfToken() }}">
    <title>Login</title>
</head>
<body>
    <h1>Login</h1>
    
    <form method="POST" action="/login">
        {{ csrfField() | raw }}
        
        <div>
            <label>Email</label>
            <input type="email" name="email" required>
        </div>
        
        <div>
            <label>Senha</label>
            <input type="password" name="password" required>
        </div>
        
        <button type="submit">Entrar</button>
    </form>
</body>
</html>
```

### Routes

```php
use Lalaz\Lalaz;

Lalaz::router()
    ->get('/login', 'AuthController', 'showLogin')
    ->post('/login', 'AuthController', 'login'); // CSRF automático
```

## Exemplo: API com AJAX

### JavaScript

```javascript
// app.js
class ApiClient {
    constructor() {
        this.csrfToken = document.querySelector('meta[name="csrf-token"]')?.content;
    }

    async post(url, data) {
        const response = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-Token': this.csrfToken
            },
            body: JSON.stringify(data)
        });

        if (!response.ok) {
            throw new Error(`HTTP ${response.status}`);
        }

        return response.json();
    }
}

// Uso
const api = new ApiClient();

document.querySelector('#createUserForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    
    try {
        const data = new FormData(e.target);
        const result = await api.post('/api/users', Object.fromEntries(data));
        console.log('Usuário criado:', result);
    } catch (error) {
        console.error('Erro:', error);
    }
});
```

### Controller

```php
class ApiUserController extends Controller
{
    public function create(Request $req, Response $res): void
    {
        // CSRF validado automaticamente via header X-CSRF-Token
        
        $data = json_decode($req->body(), true);
        
        $user = User::create($data);
        
        $res->json(['user' => $user, 'message' => 'Criado com sucesso'], 201);
    }
}
```

## Boas Práticas

1. ✅ **Sempre use csrfField() em formulários**
2. ✅ **Inclua meta tag com token para SPAs**
3. ✅ **Use header X-CSRF-Token em AJAX**
4. ✅ **Não desabilite validação CSRF em produção**
5. ✅ **Delete token ao fazer logout** (`CsrfProtection::deleteToken()`)
6. ⚠️ **Nunca exponha tokens em URLs** (use POST/body/header)
7. ⚠️ **Não armazene tokens em localStorage** (use cookies HttpOnly)

## Segurança Adicional

### Double Submit Cookie Pattern

O Lalaz usa o padrão "Double Submit Cookie":
1. Token armazenado em cookie HttpOnly (seguro)
2. Token enviado no body/header (validável)
3. Comparação timing-safe dos dois valores

### Proteção Contra Timing Attacks

```php
// Usa hash_equals() ao invés de ===
return hash_equals($cookieToken, $requestToken);
```

### Rotação Automática

Tokens rotam automaticamente após cada POST/PUT/PATCH bem-sucedido, reduzindo janela de ataque.

## Troubleshooting

### Token Mismatch em Desenvolvimento

Se estiver testando localmente e recebendo erros CSRF:

1. Verifique se o cookie está sendo enviado:
   ```javascript
   console.log(document.cookie); // Deve conter __csrf_token
   ```

2. Confirme que o campo/header está presente:
   ```javascript
   const formData = new FormData(form);
   console.log(formData.get('csrfToken')); // Deve ter valor
   ```

3. Para debug, adicione log temporário:
   ```php
   error_log('Cookie Token: ' . ($_COOKIE['__csrf_token'] ?? 'missing'));
   error_log('Request Token: ' . ($body['csrfToken'] ?? 'missing'));
   ```

### Cookies não sendo enviados

Verifique:
- Domain está correto (localhost vs 127.0.0.1)
- SameSite=Strict pode bloquear em alguns cenários
- HTTPS está configurado em produção

## Comparação: Session vs Cookie

| Aspecto | Session (Anterior) | Cookie HttpOnly (Atual) |
|---------|-------------------|--------------------------|
| Armazenamento | Servidor (PHP Session) | Cookie no cliente |
| JavaScript Access | Não | Não (HttpOnly) |
| SameSite Protection | ❌ | ✅ Strict |
| Performance | Mais lento (I/O) | Mais rápido |
| Escalabilidade | Requer session store | Stateless |
| Segurança | Boa | Excelente |

## Referências

- [OWASP CSRF Prevention Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Cross-Site_Request_Forgery_Prevention_Cheat_Sheet.html)
- [MDN: SameSite Cookies](https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Set-Cookie/SameSite)
- [Double Submit Cookie Pattern](https://cheatsheetseries.owasp.org/cheatsheets/Cross-Site_Request_Forgery_Prevention_Cheat_Sheet.html#double-submit-cookie)
