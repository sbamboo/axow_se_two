# Remember
- Password 'admin' for account 'admin' is stored as hash in schema.sql
- Current token JWT key is 'your-secret-key'
- Current token expiration times are defined in /_php_common_/auth.php
- Current default url-preview cache duration is defined in /_php_common_/url_preview/fetch.php
- There is for some reason a min-limit on cache duration? One day works but 1min does not?
- Currently token type in Auth header is ignored and not checked, should we eother use "Bearier" or the token_type

# Backend
- As per the "Remember" move to secrets.php file
- Implement endpoints
- Validator for the refresh-token
- Epoch -> Timestamp?

# Frontend
- Make website
