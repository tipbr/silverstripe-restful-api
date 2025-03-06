# silverstripe-restful-api

This module provides a very basic RESTful API for Silverstripe CMS. It is intended to be used with a decoupled frontend, such as a React app.

## Configuration

Set the following configuration:

```yaml
---
Name: app-restful-config
After:
    - "restful-config"
---
Level51\JWTUtils\JWTUtils:
    secret: "your-super-secret-key"
```

Then set your auth route wherever you'd like:

```yaml
SilverStripe\Control\Director:
    rules:
        "api/v1/auth/$Action": 'TipBr\Controllers\AuthApiController'
```

## UUID

```php
private static $extensions = [
    UUIDExtension::class
];
```

## Features

tbc

## Requirements

tbc

## Installation

tbc

## License

See [License](LICENSE.md)

This module template defaults to using the "BSD-3-Clause" license. The BSD-3 license is one of the most
permissive open-source license and is used by most Silverstripe CMS module.
