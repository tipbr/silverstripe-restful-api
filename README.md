# SilverStripe RESTful API

RESTful API helpers for SilverStripe with JWT authentication, refresh tokens, and UUID support.

## Installation

```bash
composer require tipbr/silverstripe-restful-api
```

## Configuration

Set JWT secret in `.env`:
```
JWT_SECRET=your-secure-secret-key-32-chars-minimum
```

Run database build:
```bash
sake dev/build flush=1
```

Configure CORS in `app/_config/api.yml`:
```yaml
TipBr\RestfulApi\Middleware\CorsMiddleware:
  allowed_origins:
    - 'https://app.example.com'
```

## Usage

### Basic API Controller

```php
<?php

use TipBr\RestfulApi\Controllers\ApiController;

class MyApiController extends ApiController
{
    private static $allowed_actions = ['list', 'view', 'create'];
    
    public function list()
    {
        $member = $this->ensureUserLoggedIn();
        $items = MyModel::get();
        return $this->returnPaginated($items);
    }
    
    public function view()
    {
        $member = $this->ensureUserLoggedIn();
        $uuid = $this->getVar('uuid', FILTER_SANITIZE_STRING);
        $item = MyModel::getByUUID($uuid);
        
        if (!$item) {
            return $this->httpError(404);
        }
        
        return $this->success($item->toApi());
    }
    
    public function create()
    {
        $member = $this->ensureUserLoggedIn(['ADMIN']);
        
        $title = $this->getVar('title', FILTER_SANITIZE_STRING);
        
        $item = MyModel::create();
        $item->Title = $title;
        $item->write();
        
        return $this->success($item->toApi());
    }
}
```

### Adding UUID Support to Models

```php
<?php

use TipBr\RestfulApi\Traits\Uuidable;
use SilverStripe\ORM\DataObject;

class MyModel extends DataObject
{
    use Uuidable;
    
    private static $db = [
        'Title' => 'Varchar(255)'
    ];
}

// Find by UUID
$model = MyModel::getByUUID('550e8400-e29b-41d4-a716-446655440000');
```

### Implementing ApiReadable

```php
<?php

use TipBr\RestfulApi\Interfaces\ApiReadable;
use SilverStripe\ORM\DataObject;

class Product extends DataObject implements ApiReadable
{
    private static $db = [
        'Title' => 'Varchar(255)',
        'Price' => 'Currency'
    ];
    
    public function toApi(array $context = []): array
    {
        return [
            'uuid' => $this->UUID,
            'title' => $this->Title,
            'price' => $this->Price
        ];
    }
}
```

## Authentication

### Get Token (Login)

```bash
curl -X POST https://yoursite.com/api/auth/token \
  -H "Authorization: Basic $(echo -n 'email:password' | base64)"
```

Response:
```json
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "refreshToken": "550e8400-e29b-41d4-a716-446655440000",
  "member": {
    "uuid": "123e4567-e89b-12d3-a456-426614174000",
    "email": "user@example.com",
    "firstName": "John"
  }
}
```

### Access Protected Endpoint

```bash
curl https://yoursite.com/api/my/list \
  -H "Authorization: Bearer eyJ0eXAiOiJKV1QiLCJhbGc..."
```

### Refresh Token

```bash
curl -X POST https://yoursite.com/api/auth/refresh \
  -H "Content-Type: application/json" \
  -d '{"refreshToken": "550e8400-e29b-41d4-a716-446655440000"}'
```

Note: Old refresh token is automatically revoked (token rotation).

## Permissions

Use SilverStripe's built-in permission methods:

```php
class Article extends DataObject
{
    public function canView($member = null)
    {
        return Permission::check('CMS_ACCESS', 'any', $member);
    }
    
    public function canEdit($member = null)
    {
        return Permission::check(['ARTICLE_EDIT', 'ADMIN'], 'any', $member);
    }
}
```

In controllers:

```php
public function update()
{
    $member = $this->ensureUserLoggedIn();
    $article = Article::getByUUID($this->getVar('uuid'));
    
    if (!$article->canEdit($member)) {
        return $this->httpError(403);
    }
    
    $article->Title = $this->getVar('title', FILTER_SANITIZE_STRING);
    $article->write();
    
    return $this->success($article->toApi());
}
```

## Features

- JWT authentication with access and refresh tokens
- Token rotation (refresh tokens revoked on use)
- UUID-based identification (no internal IDs exposed)
- Input sanitization (prevent injection attacks)
- CORS middleware (configurable origins)
- Standardized error responses
- Built-in permission checking

## License

BSD-3-Clause
