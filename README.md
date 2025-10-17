# SilverStripe RESTful API Helpers

Simple, flexible helpers for building RESTful APIs in SilverStripe with JWT authentication, UUID support, and comprehensive security features.

## Recent Improvements

### ✅ Completed

1. **Input Validation** - All user inputs are now sanitized using PHP filter functions
2. **Token Rotation** - Refresh tokens are automatically rotated on use to prevent token reuse attacks
3. **UUID Support** - All entities use UUIDs instead of internal IDs (including Members)
4. **Trait-based Architecture** - Code organized into reusable traits:
   - `Uuidable` - UUID support for any DataObject
   - `JwtAuthentication` - JWT authentication logic
   - `JsonResponse` - Standardized JSON response formatting
   - `InputValidation` - Input sanitization and validation
5. **Middleware** - CORS and JSON processing handled via middleware
6. **Standardized Errors** - Consistent error response format across all endpoints
7. **Public/Protected Endpoints** - Configuration-based access control
8. **Context-aware Serialization** - ApiReadable interface supports context parameter

## Installation

```bash
composer require tipbr/silverstripe-restful-helpers
```

## Configuration

### 1. JWT Secret (Required)

Add to your `.env` file:
```bash
JWT_SECRET=your-very-secure-secret-key-minimum-32-characters
```

### 2. CORS Configuration (Production)

Add to `app/_config/api.yml`:
```yaml
FullscreenInteractive\Restful\Middleware\CorsMiddleware:
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
    
    // Configure which actions are public (no auth required)
    private static $public_actions = [
        'public_data'
    ];
    
    // Public endpoint - no authentication required
    public function public_data()
    {
        return $this->success([
            'message' => 'This is public data'
        ]);
    }
    
    // Protected endpoint - requires JWT authentication
    public function protected_data()
    {
        $member = $this->ensureUserLoggedIn();
        
        return $this->success([
            'message' => 'Hello ' . $member->FirstName,
            'uuid' => $member->UUID
        ]);
    }
}
```

### Using UUIDs in Your Models

```php
<?php

use FullscreenInteractive\Restful\Traits\Uuidable;
use SilverStripe\ORM\DataObject;

class MyModel extends DataObject
{
    use Uuidable;
    
    private static $db = [
        'Title' => 'Varchar(255)'
    ];
}

// Find by UUID
$model = MyModel::getByUUID('550e8400-e29b-41d4-a716...');
```

### Implementing ApiReadable with Context

```php
<?php

use FullscreenInteractive\Restful\Interfaces\ApiReadable;
use SilverStripe\ORM\DataObject;

class Product extends DataObject implements ApiReadable
{
    private static $db = [
        'Title' => 'Varchar(255)',
        'Price' => 'Currency',
        'InternalNotes' => 'Text'
    ];
    
    public function toApi(array $context = []): array
    {
        $data = [
            'uuid' => $this->UUID,
            'title' => $this->Title,
            'price' => $this->Price
        ];
        
        // Include additional fields based on context
        if (isset($context['include_internal']) && $context['include_internal']) {
            $data['internalNotes'] = $this->InternalNotes;
        }
        
        // Field filtering
        if (isset($context['fields'])) {
            $data = array_intersect_key($data, array_flip($context['fields']));
        }
        
        return $data;
    }
}
```

### Authentication Flow

1. **Get Token (Login)**
```bash
curl -X POST https://yoursite.com/api/auth/token \
  -H "Authorization: Basic base64(email:password)"
```

Response:
```json
{
  "token": "******",
  "refreshToken": "550e8400-e29b-41d4-a716...",
  "member": {
    "uuid": "123e4567-e89b-12d3-a456...",
    "email": "user@example.com",
    "firstName": "John"
  }
}
```

2. **Use Token (Access Protected Endpoints)**
```bash
curl https://yoursite.com/api/my/protected_data \
  -H "Authorization: Bearer ******"
```

3. **Refresh Token (Get New Access Token)**
```bash
curl -X POST https://yoursite.com/api/auth/refresh \
  -H "Content-Type: application/json" \
  -d '{"refreshToken": "550e8400-e29b-41d4-a716..."}'
```

**Note:** The old refresh token is automatically revoked and a new one is issued (token rotation).

## Permission-Based Access Control

### Using SilverStripe's Built-in Permissions

SilverStripe provides built-in permission methods that you should leverage:

#### 1. Model-Level Permissions (canView, canEdit, canCreate, canDelete)

```php
<?php

use SilverStripe\ORM\DataObject;
use SilverStripe\Security\Permission;

class Article extends DataObject
{
    private static $db = [
        'Title' => 'Varchar(255)',
        'Content' => 'HTMLText'
    ];
    
    /**
     * Control who can view this article
     */
    public function canView($member = null)
    {
        // Public articles can be viewed by anyone
        if ($this->IsPublic) {
            return true;
        }
        
        // Otherwise, must be logged in
        return Permission::check('CMS_ACCESS', 'any', $member);
    }
    
    /**
     * Control who can edit this article
     */
    public function canEdit($member = null)
    {
        // Only editors and admins can edit
        return Permission::check(['ARTICLE_EDIT', 'ADMIN'], 'any', $member);
    }
    
    /**
     * Control who can create articles
     */
    public function canCreate($member = null, $context = [])
    {
        return Permission::check('ARTICLE_CREATE', 'any', $member);
    }
    
    /**
     * Control who can delete this article
     */
    public function canDelete($member = null)
    {
        // Only the owner or admin can delete
        if ($this->OwnerID === $member->ID) {
            return true;
        }
        
        return Permission::check('ADMIN', 'any', $member);
    }
}
```

#### 2. Using Permissions in API Endpoints

```php
<?php

use FullscreenInteractive\Restful\Controllers\ApiController;

class ArticleApiController extends ApiController
{
    private static $allowed_actions = [
        'list',
        'view',
        'create',
        'update',
        'delete'
    ];
    
    public function list()
    {
        // Get current member (will be null if not authenticated)
        $member = $this->ensureUserLoggedIn();
        
        // Filter to only articles the member can view
        $articles = Article::get()->filterByCallback(function($article) use ($member) {
            return $article->canView($member);
        });
        
        return $this->returnPaginated($articles);
    }
    
    public function view()
    {
        $uuid = $this->getVar('uuid', FILTER_SANITIZE_STRING);
        $article = Article::getByUUID($uuid);
        
        if (!$article) {
            return $this->httpError(404, 'Article not found');
        }
        
        // Check permission
        $member = $this->ensureUserLoggedIn();
        if (!$article->canView($member)) {
            return $this->httpError(403, 'You do not have permission to view this article');
        }
        
        return $this->success($article->toApi());
    }
    
    public function create()
    {
        $member = $this->ensureUserLoggedIn();
        
        // Check create permission
        $article = Article::create();
        if (!$article->canCreate($member)) {
            return $this->httpError(403, 'You do not have permission to create articles');
        }
        
        // Validate and create
        $title = $this->getVar('title', FILTER_SANITIZE_STRING);
        $content = $this->getVar('content', FILTER_SANITIZE_STRING);
        
        $article->Title = $title;
        $article->Content = $content;
        $article->OwnerID = $member->ID;
        $article->write();
        
        return $this->success($article->toApi());
    }
    
    public function update()
    {
        $member = $this->ensureUserLoggedIn();
        $uuid = $this->getVar('uuid', FILTER_SANITIZE_STRING);
        
        $article = Article::getByUUID($uuid);
        if (!$article) {
            return $this->httpError(404, 'Article not found');
        }
        
        // Check edit permission
        if (!$article->canEdit($member)) {
            return $this->httpError(403, 'You do not have permission to edit this article');
        }
        
        // Update
        $article->Title = $this->getVar('title', FILTER_SANITIZE_STRING);
        $article->Content = $this->getVar('content', FILTER_SANITIZE_STRING);
        $article->write();
        
        return $this->success($article->toApi());
    }
    
    public function delete()
    {
        $member = $this->ensureUserLoggedIn();
        $uuid = $this->getVar('uuid', FILTER_SANITIZE_STRING);
        
        $article = Article::getByUUID($uuid);
        if (!$article) {
            return $this->httpError(404, 'Article not found');
        }
        
        // Check delete permission
        if (!$article->canDelete($member)) {
            return $this->httpError(403, 'You do not have permission to delete this article');
        }
        
        $article->delete();
        
        return $this->success(['message' => 'Article deleted']);
    }
}
```

#### 3. Using ensureUserLoggedIn with Permission Codes

```php
public function admin_only_action()
{
    // This will check if user is logged in AND has ADMIN permission
    $member = $this->ensureUserLoggedIn(['ADMIN']);
    
    return $this->success([
        'message' => 'Admin access granted'
    ]);
}

public function editor_action()
{
    // Check for multiple permissions (user needs at least one)
    $member = $this->ensureUserLoggedIn(['ARTICLE_EDIT', 'ADMIN']);
    
    return $this->success([
        'message' => 'Editor access granted'
    ]);
}
```

#### 4. Defining Custom Permissions

Add to your `_config/permissions.yml`:

```yaml
SilverStripe\Security\Permission:
  permissions:
    ARTICLE_CREATE:
      name: 'Create articles'
      category: 'Article permissions'
    ARTICLE_EDIT:
      name: 'Edit articles'
      category: 'Article permissions'
    ARTICLE_DELETE:
      name: 'Delete articles'
      category: 'Article permissions'
```

Then assign these permissions to groups in the CMS under Security > Groups.

### Best Practices

1. **Always use canView/canEdit/canCreate/canDelete** in your DataObject models
2. **Check permissions in API endpoints** before performing operations
3. **Use UUIDs instead of IDs** to prevent enumeration attacks
4. **Sanitize all inputs** using the InputValidation trait methods
5. **Use context-aware serialization** to control what data is exposed
6. **Configure CORS properly** for production environments
7. **Leverage SilverStripe's permission system** - don't reinvent the wheel

## Security Features

- ✅ JWT authentication with access and refresh tokens
- ✅ Token rotation to prevent token reuse attacks
- ✅ Input sanitization to prevent injection vulnerabilities
- ✅ UUID-based identification (no internal ID exposure)
- ✅ Configurable CORS (restrictive by default in production)
- ✅ Standardized error responses (no information leakage)
- ✅ Middleware-based request/response processing
- ✅ Permission-based access control using SilverStripe's built-in system

## License

BSD-3-Clause
