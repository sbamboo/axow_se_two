# Remember
- Password 'admin' for account 'admin' is stored as hash in schema.sql
- Current token JWT key is 'your-secret-key'

# Backend
- /_php_common_/auth.php: standardize returns as `[<success>, <msg>, <http_code>, <opt:data>]` and make `req_` funcs just wrap regular
- Auth: 'pair' type tokens with refresh tokens
- Implement endpoints

# Frontend
- Make website
