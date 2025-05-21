# Remember
- Password 'admin' for account 'admin' is stored as hash in schema.sql
- Current config is in /_php_common_/secret_config.php
- There is for some reason a min-limit on cache duration? One day works but 1min does not?
- Currently token type in Auth header is ignored and not checked, should we eother use "Bearier" or the token_type

# Backend
- url preview oembed additional fields if exists
- Make permissiondigits based on DB, `get_permissiondigits_length()` (based on number of unique indexes found in the DB), Add fields `index` `digit` to each permission.
- Implement endpoints
- Validator for the refresh-token
- Epoch -> Timestamp? (Y2K38; 2038 32bit epoch overflow)

# Frontend
- Make website
- Update opengraph/twitter meta and SEO
