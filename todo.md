# Remember
- Password 'admin' for account 'admin' is stored as hash in schema.sql
- Current config is in /_php_common_/secret_config.php
- There is for some reason a min-limit on cache duration? One day works but 1min does not?
- Currently token type in Auth header is ignored and not checked, should we eother use "Bearier" or the token_type

# Backend
- Standardise on `format_json_response`
- Decide endpoint methods and add to api.md, if ambiguies use `req_require_one_of_methods`
- Implement endpoints
- Validator for the refresh-token
- Epoch -> Timestamp?

# Frontend
- Make website
- Define opengraph/twitter meta and SEO
