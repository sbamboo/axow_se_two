# Remember
- Password 'admin' for account 'admin' is stored as hash in schema.sql
- Current config is in /_config_/*
- There is for some reason a min-limit on cache duration? One day works but 1min does not?
- Currently token type in Auth header is ignored and not checked, should we either use "Bearier" or the token_type
- Replace ' with "

# Backend
- wiki shorthands for $<cat>/<article> and property %<prop>% handled in frontend probably? but needs backend support (prob use $<wiki>:<cat>/<article>)
- urlpreviews /Projects/axo for media based on media types
- Ensure proper access control i.e no web access to any folder named _{}_
- Implement endpoints
- Epoch -> ? (Y2K38; 2038 32bit epoch overflow)
- Wiki store with wiki-category>wiki>page-category>page, wiki-category is just a property
- Article permission edit own or all 

# Frontend
- Make website
- Update opengraph/twitter meta and SEO
- Darkmode by default overridden by url param
- Each page sets PAGE_IDENTIFIER which can be passed in url param
- Common for nav bar, theme, noscript and footer with last-edit and github url for site