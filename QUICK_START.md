# Quick Start Implementation Guide

This guide helps you quickly implement the most critical improvements from the code review. Start here if you want to address security concerns and improve the module right away.

---

## 🚨 Critical Security Fixes (Do These First)

### 1. Fix CORS Configuration (30 minutes)

**File:** `src/Controllers/ApiController.php`

**Step 1:** Add configuration property
```php
class ApiController extends Controller
{
    /**
     * @var array List of allowed origins for CORS. Empty array = allow all (dev only)
     */
    private static $allowed_origins = [];
```

**Step 2:** Create CORS header method
```php
    protected function addCorsHeaders(): void
    {
        $allowedOrigins = $this->config()->get('allowed_origins');
        $response = $this->getResponse();
        
        if (!empty($allowedOrigins)) {
            $origin = $this->getRequest()->getHeader('Origin');
            if (in_array($origin, $allowedOrigins)) {
                $response->addHeader('Access-Control-Allow-Origin', $origin);
                $response->addHeader('Access-Control-Allow-Credentials', 'true');
            }
        } else {
            // Fallback for development - remove in production!
            $response->addHeader('Access-Control-Allow-Origin', '*');
        }
    }
```

**Step 3:** Update init() method
```php
    public function init()
    {
        parent::init();

        if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
            $response = $this->getResponse();
            $this->addCorsHeaders(); // Use new method
            
            $response->addHeader("Content-type", "application/json");

            if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'])) {
                $response->addHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
            }

            if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'])) {
                $response->addHeader(
                    'Access-Control-Allow-Headers',
                    $_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']
                );
            }

            $response->output();
            exit;
        }

        // ... rest of init method ...
        
        $this->addCorsHeaders(); // Use new method
        $this->getResponse()->addHeader("Content-type", "application/json");
    }
```

**Step 4:** Configure allowed origins
```yaml
# app/_config/api.yml
FullscreenInteractive\Restful\Controllers\ApiController:
  allowed_origins:
    - 'https://app.example.com'
    - 'https://mobile.example.com'
```

---

### 2. Add JWT Secret Validation (15 minutes)

**File:** `src/JWT/JWTUtils.php`

**Update constructor:**
```php
    private function __construct()
    {
        if (!$this->hasValidSecret()) {
            throw new JWTUtilsException('No "secret" config found.');
        }
        
        $secret = Injector::inst()->convertServiceProperty(
            Config::inst()->get(self::class, 'secret')
        );
        
        if (strlen($secret) < 32) {
            throw new JWTUtilsException(
                'JWT secret must be at least 32 characters long. Current length: ' . strlen($secret)
            );
        }
    }
```

**Update your .env:**
```bash
# Ensure your JWT_SECRET is at least 32 characters
JWT_SECRET=your-very-secure-secret-key-that-is-at-least-32-characters-long
```

---

### 3. Add Input Sanitization (30 minutes)

**File:** `src/Controllers/ApiController.php`

**Update getVar method:**
```php
    /**
     * Returns a variable from the POST or GET vars with optional filtering
     * 
     * @param string $name Variable name
     * @param int|null $filter PHP filter constant (e.g., FILTER_SANITIZE_STRING, FILTER_SANITIZE_EMAIL)
     * @return mixed
     */
    public function getVar(string $name, ?int $filter = null): mixed
    {
        $key = strtolower($name);
        $value = $this->vars[$key] ?? null;
        
        if ($value !== null && $filter !== null && is_string($value)) {
            return filter_var($value, $filter);
        }
        
        return $value;
    }
```

**Update usage in AuthController:**
```php
    public function refresh()
    {
        $this->ensurePOST();

        // Sanitize refresh token input
        $refreshTokenValue = $this->getVar('refreshToken', FILTER_SANITIZE_STRING);

        if (!$refreshTokenValue) {
            return $this->httpError(400, 'Missing refresh token');
        }
        
        // ... rest of method
    }
```

---

### 4. Implement Refresh Token Rotation (45 minutes)

**File:** `src/Controllers/AuthController.php`

**Update refresh method:**
```php
    public function refresh()
    {
        $this->ensurePOST();

        $refreshTokenValue = $this->getVar('refreshToken', FILTER_SANITIZE_STRING);

        if (!$refreshTokenValue) {
            return $this->httpError(400, 'Missing refresh token');
        }

        $refreshToken = RefreshToken::findValid($refreshTokenValue);

        if (!$refreshToken) {
            return $this->httpError(401, 'Invalid or expired refresh token');
        }

        $member = $refreshToken->Member();

        if (!$member || !$member->exists()) {
            return $this->httpError(401, 'Invalid member');
        }

        // Generate new access token
        $payload = JWTUtils::inst()->forMember($member, true);

        if ($member->hasMethod('toApi')) {
            $api = $member->toApi() ?? [];

            if ($api instanceof ArrayData) {
                $api = $api->toMap();
            }

            $payload['member'] = array_merge($payload['member'], $api);
        }

        // IMPORTANT: Revoke old token
        $refreshToken->revoke();
        
        // Generate NEW refresh token
        $newRefreshToken = RefreshToken::generate($member);
        $payload['refreshToken'] = $newRefreshToken->Token;

        return $this->returnArray($payload);
    }
```

---

### 5. Add Basic Rate Limiting (1 hour)

**File:** `src/Controllers/AuthController.php`

**Add rate limiting method:**
```php
    /**
     * Check rate limit for IP address
     * 
     * @param string $action Action name
     * @param int $maxAttempts Maximum attempts per window
     * @param int $windowMinutes Time window in minutes
     * @throws HTTPResponse_Exception
     */
    protected function checkRateLimit(string $action, int $maxAttempts = 5, int $windowMinutes = 15): void
    {
        $ip = $this->getRequest()->getIP();
        $cacheKey = "ratelimit_{$action}_{$ip}";
        
        $cache = Injector::inst()->get(CacheInterface::class . '.api');
        $attempts = (int)$cache->get($cacheKey);
        
        if ($attempts >= $maxAttempts) {
            $this->httpError(429, 'Too many attempts. Please try again later.');
        }
        
        $cache->set($cacheKey, $attempts + 1, $windowMinutes * 60);
    }
```

**Use in token method:**
```php
    public function token()
    {
        // Rate limit: 5 attempts per 15 minutes per IP
        $this->checkRateLimit('token', 5, 15);
        
        try {
            $payload = JWTUtils::inst()->byBasicAuth($this->request, true);
            // ... rest of method
        } catch (JWTUtilsException $e) {
            return $this->httpError(403, $e->getMessage());
        }
    }
```

**Configure cache in _config/cache.yml:**
```yaml
---
Name: restful-cache-config
---
SilverStripe\Core\Injector\Injector:
  CacheInterface.api:
    factory: SilverStripe\Core\Cache\CacheFactory
    constructor:
      namespace: 'api'
```

---

## 📋 Essential Configuration

### Create .env.example

Create a file `.env.example` in your project root:

```bash
###########
# JWT Configuration
###########

# REQUIRED: JWT secret key (minimum 32 characters, 64+ recommended)
JWT_SECRET=

# Optional: JWT issuer (defaults to site URL)
# JWT_ISSUER=https://api.example.com

###########
# API Configuration
###########

# CORS allowed origins (comma-separated, or configure in YML)
API_ALLOWED_ORIGIN_1=https://app.example.com
API_ALLOWED_ORIGIN_2=https://mobile.example.com

###########
# Security Configuration
###########

# Rate limiting (requests per window)
API_RATE_LIMIT_ATTEMPTS=5
API_RATE_LIMIT_WINDOW_MINUTES=15
```

---

## 📝 Basic Documentation

### Create README.md

Create a file `README.md` in your module root:

```markdown
# SilverStripe RESTful API Helpers

Simple, flexible helpers for building RESTful APIs in SilverStripe, with JWT authentication and refresh tokens.

## Installation

```bash
composer require tipbr/silverstripe-restful-helpers
```

## Configuration

### 1. Set JWT Secret

Add to your `.env` file:
```bash
JWT_SECRET=your-very-secure-secret-key-minimum-32-characters
```

### 2. Configure CORS (Production)

Add to `app/_config/api.yml`:
```yaml
FullscreenInteractive\Restful\Controllers\ApiController:
  allowed_origins:
    - 'https://app.example.com'
    - 'https://mobile.example.com'
```

### 3. Run Database Build

```bash
sake dev/build flush=1
```

## Usage

### Creating an API Endpoint

```php
<?php

use FullscreenInteractive\Restful\Controllers\ApiController;

class MyApiController extends ApiController
{
    private static $allowed_actions = [
        'public_data',
        'protected_data'
    ];
    
    // Public endpoint - no authentication required
    public function public_data()
    {
        return $this->returnArray([
            'message' => 'This is public'
        ]);
    }
    
    // Protected endpoint - requires JWT authentication
    public function protected_data()
    {
        $member = $this->ensureUserLoggedIn();
        
        return $this->returnArray([
            'message' => 'Hello ' . $member->FirstName
        ]);
    }
}
```

### Authentication Flow

1. **Get Token** (Login)
```bash
curl -X POST https://yoursite.com/api/auth/token \
  -H "Authorization: Basic base64(email:password)"
```

Response:
```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "refreshToken": "550e8400-e29b-41d4-a716...",
  "member": {
    "id": 1,
    "email": "user@example.com",
    "firstName": "John"
  }
}
```

2. **Use Token** (Access Protected Endpoints)
```bash
curl https://yoursite.com/api/my/protected_data \
  -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc..."
```

3. **Refresh Token** (Get New Access Token)
```bash
curl -X POST https://yoursite.com/api/auth/refresh \
  -H "Content-Type: application/json" \
  -d '{"refreshToken": "550e8400-e29b-41d4-a716..."}'
```

## Security

- Always use HTTPS in production
- Set a strong JWT secret (32+ characters)
- Configure CORS to only allow your domains
- Implement rate limiting on authentication endpoints

For detailed security guidelines, see [SECURITY.md](SECURITY.md)

## License

BSD-3-Clause
```

---

## ✅ Verification Checklist

After implementing the critical fixes, verify:

- [ ] CORS is restricted to specific origins in production
- [ ] JWT secret is at least 32 characters long
- [ ] Input sanitization is applied to user inputs
- [ ] Refresh tokens are rotated on use
- [ ] Rate limiting is active on auth endpoints
- [ ] .env.example file is created
- [ ] Basic README.md is in place
- [ ] Database build runs without errors: `sake dev/build flush=1`

---

## 🧪 Testing Your Changes

### Test CORS Configuration

```bash
# Should be rejected if not in allowed_origins
curl -H "Origin: https://evil.com" https://yoursite.com/api/auth/verify
```

### Test Rate Limiting

```bash
# Should return 429 after 5 attempts
for i in {1..6}; do
  curl -X POST https://yoursite.com/api/auth/token \
    -H "Authorization: Basic invalid"
done
```

### Test Token Rotation

```bash
# Get initial tokens
TOKEN_RESPONSE=$(curl -X POST https://yoursite.com/api/auth/token \
  -H "Authorization: Basic $(echo -n 'user@example.com:password' | base64)")

REFRESH_TOKEN=$(echo $TOKEN_RESPONSE | jq -r '.refreshToken')

# Refresh once (should work)
REFRESH_RESPONSE=$(curl -X POST https://yoursite.com/api/auth/refresh \
  -H "Content-Type: application/json" \
  -d "{\"refreshToken\": \"$REFRESH_TOKEN\"}")

# Try to use old token again (should fail)
curl -X POST https://yoursite.com/api/auth/refresh \
  -H "Content-Type: application/json" \
  -d "{\"refreshToken\": \"$REFRESH_TOKEN\"}"
# Expected: 401 Invalid or expired refresh token
```

---

## 📚 Next Steps

Once critical fixes are in place:

1. **Review** [CODE_REVIEW.md](CODE_REVIEW.md) for detailed recommendations
2. **Implement** architecture improvements from [GITHUB_ISSUES.md](GITHUB_ISSUES.md)
3. **Add** comprehensive tests
4. **Create** detailed API documentation

---

## 🆘 Troubleshooting

### "No secret config found" Error
- Ensure JWT_SECRET is set in .env
- Run `sake dev/build flush=1`
- Check that .env is being loaded

### CORS Errors in Browser
- Verify allowed_origins configuration
- Check that Origin header matches exactly
- Ensure HTTPS is used in production

### Rate Limit Not Working
- Verify cache is configured properly
- Check that CacheInterface.api is available
- Run `sake dev/build flush=1`

---

*For more detailed information, see the complete [CODE_REVIEW.md](CODE_REVIEW.md) document.*
