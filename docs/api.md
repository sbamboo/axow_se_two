# General

## Assets
If a content wants to contain a multimedia file that are stored in /static it will get included as base64.

# URL Preview Fetcher
GET `api.axow.se/site/url_preview?url=<string:urlencoded>`
```json (Response)
{
    "type": "twitter"/"opengraph",
    "title": "<string>",
    "description": "<string>",
    "image": "<string>",
    "url": "<string>",
    "type": "<string=type/card>"
}
```

# Auth
POST `api.axow.se/site/auth?token_type=<string>` (Credential)<br>
(`token_type` = `single`, `single_use`, `refresh`)
```Headers (Request)
Content-Type: application/json
```
```json (Request)
{
    "username": "<string>",
    "password_hash": "<string:hash>"
}
```
```json (Response)
{
    "status": "success"/"failed",
    "msg": "<string:optional>",
    "token_type": "single",
    "expires": <epoch>,
    "token": "<string:token>"
}
```
<br><br>

# Articles

## Get all categories
GET `api.axow.se/site/articles/categories/getAll`
```json (Response)
{
    "status": "success"/"failed",
    "msg": "<string:optional>",
    "categories": [
        "<string:category>"
    ]
}
```

## Add category
POST `api.axow.se/site/articles/categories/add` (Authed)
```Headers (Request)
Content-Type: application/json
Authorization: <string:token>
```
```json (Request)
{
    "category": "<string:category>"
}
```
```json (Response)
{
    "status": "success"/"failed",
    "msg": "<string:optional>"
}
```

## Rename category
POST `api.axow.se/site/articles/categories/update?category=<string:category>` (Authed)
```Headers (Request)
Content-Type: application/json
Authorization: <string:token>
```
```json (Request)
{
    "category": "<string:category>"
}
```
```json (Response)
{
    "status": "success"/"failed",
    "msg": "<string:optional>"
}
```

## Remove category
REMOVE `api.axow.se/site/articles/categories/remove?category=<string:category>` (Authed)
```Headers (Request)
Content-Type: application/json
Authorization: <string:token>
```
```json (Response)
{
    "status": "success"/"failed",
    "msg": "<string:optional>"
}
```

## Get all subcategories
GET `api.axow.se/site/articles/subcategories/getAll`
```json (Response)
{
    "status": "success"/"failed",
    "msg": "<string:optional>",
    "subcategories": [
        "<string:subcategory>"
    ]
}
```

## Add subcategory
POST `api.axow.se/site/articles/subcategories/add` (Authed)
```Headers (Request)
Content-Type: application/json
Authorization: <string:token>
```
```json (Request)
{
    "subcategory": "<string:subcategory>"
}
```
```json (Response)
{
    "status": "success"/"failed",
    "msg": "<string:optional>"
}
```

## Rename subcategory
POST `api.axow.se/site/articles/subcategories/update?subcategory=<string:subcategory>` (Authed)
```Headers (Request)
Content-Type: application/json
Authorization: <string:token>
```
```json (Request)
{
    "subcategory": "<string:subcategory>"
}
```
```json (Response)
{
    "status": "success"/"failed",
    "msg": "<string:optional>"
}
```

## Remove subcategory
REMOVE `api.axow.se/site/articles/subcategories/remove?subcategory=<string:subcategory>` (Authed)
```Headers (Request)
Content-Type: application/json
Authorization: <string:token>
```
```json (Response)
{
    "status": "success"/"failed",
    "msg": "<string:optional>"
}
```

## Get all articles
GET `api.axow.se/site/articles/getAll`
```json (Response)
{
    "status": "success"/"failed",
    "msg": "<string:optional>", 
    "articles": [
        {
            "id": "<string:article_id>",
            "name": "<string>",
            "author": "<string:profile_id_with_@>",
            "published": <epoch>,
            "last_changed": <epoch>,
            "tags": ["<string>"],
            "category": "<string:category>",
            "subcategory": "<string:subcategory>",
            "favicon": "<string:optional:url_or_base_url>",
            "banner": "<string:optional:url_or_base_url>"
        }
    ]
}
```

## Get a specific article
GET `api.axow.se/site/article/get?id=<string:article_id>`
```json (Response)
{
    "status": "success"/"failed",
    "msg": "<string:optional>",
    "article": {
        "meta": {
            "id": "<string:article_id>",
            "name": "<string>",
            "author": "<string:profile_id_with_@>",
            "published": <epoch>,
            "last_changed": <epoch>,
            "tags": ["<string>"],
            "category": "<string:category>",
            "subcategory": "<string:subcategory>",
            "favicon": "<string:optional:url_or_base_url>",
            "banner": "<string:optional:url_or_base_url>"
        },
        "content": "<markdown>",
        "url_previews": {
            "<string:url>": {
                "type": "twitter"/"opengraph",
                "title": "<string>",
                "description": "<string>",
                "image": "<string>",
                "url": "<string>",
                "type": "<string=type/card>"
            },
            ...
        }
    }
}
```
<br><br>

## Add an article
POST `api.axow.se/site/article/add` (Authed)
```Headers (Request)
Content-Type: application/json
Authorization: <string:token>
```
```json (Request)
{
    "meta": {
        "id": "<string:article_id>",
        "name": "<string>",
        "author": "<string:profile_id_with_@>",
        "published": <epoch>,
        "last_changed": <epoch>,
        "tags": ["<string>"],
        "category": "<string:category>",
        "subcategory": "<string:subcategory>",
        "favicon": "<string:optional:url_or_base_url>",
        "banner": "<string:optional:url_or_base_url>"
    },
    "content": "<markdown:if-action=merge>"
}
```
<br><br>

## Modify an article
POST `api.axow.se/site/article/update?id=<string:article_id>&action=<merge/remove>` (Authed)
```Headers (Request)
Content-Type: application/json
Authorization: <string:token>
```
```json (Request)
{
    "meta": {
        "id": "<string:article_id>",
        "name": "<string>",
        "author": "<string:profile_id_with_@>",
        "published": <epoch>,
        "last_changed": <epoch>,
        "tags": ["<string>"],
        "category": "<string:category>",
        "subcategory": "<string:subcategory>",
        "favicon": "<string:optional:url_or_base_url>",
        "banner": "<string:optional:url_or_base_url>"
    },
    "content": "<markdown:if-action=merge>"
}
```
(`merge` ensures al meta fields and replaces content, `remove` removes meta fields and does not touch content)
```json (Response)
{
    "status": "success"/"failed",
    "msg": "<string:optional>"
}
```
<br><br>

## Remove an article
DELETE `api.axow.se/site/article/remove?id=<string:article_id>` (Authed)
```Headers (Request)
Content-Type: application/json
Authorization: <string:token>
```
```json (Response)
{
    "status": "success"/"failed",
    "msg": "<string:optional>"
}
```
<br><br>

# Projects
## Get axo77 server timeline and media
GET `api.axow.se/site/projects/axo77_server`
```json (Response)
{
    "status": "success"/"failed",
    "msg": "<string:optional>",
    "timeline": [
        {
            "date": <epoch>,
            "date_format": "<string:date_format>",
            "title": "<string>",
            "description": "<string>"
        }
    ],
    "media": {
        "carousel": [
            {...media_type_and_data...}
        ]
    },
    "url_previews": {
        "<string:url>": {
            "type": "twitter"/"opengraph",
            "title": "<string>",
            "description": "<string>",
            "image": "<string>",
            "url": "<string>",
            "type": "<string=type/card>"
        },
        ...
    }
}
```
<br><br>

# Chibit storage
## Get repository
GET `api.axow.se/site/chibits/?repo=<string:repo>&cat_filter=<string:optional>`
```json (Response)
{
    "status": "success"/"failed",
    "msg": "<string:optional>",
    "<string:repo>": {
        "<string:group>": {
            "<string:chibit_uuid>": "<string:chibit_entry_url>",
            ...
        }
        ...
    }
}
```
GET `api.axow.se/site/chibits/?repo=<string:repo>&category=<string:category>`
```json (Response)
{
    "status": "success"/"failed",
    "msg": "<string:optional>",
    "<string:repo>": {
        "<string:category>": {
            "<string:chibit_uuid>": "<string:chibit_entry_url>",
            ...
        }
    }
}
```
GET `api.axow.se/site/chibits/?repo=<string:repo>&include_entries=true`
```json (Response)
{
    "status": "success"/"failed",
    "msg": "<string:optional>",
    "<string:repo>": {
        "group": "axo77",
        "entries": {
            "<string:chibit_uuid>": {...chibit_entry_data...},
            ...
        },
        ...
    }
}
```
<br><br>

# Wiki
## Get all categories
GET `api.axow.se/site/wiki/categories/getAll`
```json (Response)
{
    "status": "success"/"failed",
    "msg": "<string:optional>",
    "categories": [
        "<string:category>"
    ]
}
```

## Add category
POST `api.axow.se/site/wiki/category/add` (Authed)
```Headers (Request)
Content-Type: application/json
Authorization: <string:token>
```
```json (Request)
{
    "category": "<string:category>"
}
```
```json (Response)
{
    "status": "success"/"failed",
    "msg": "<string:optional>"
}
```

## Rename category
POST `api.axow.se/site/wiki/category/update?category=<string:category>` (Authed)
```Headers (Request)
Content-Type: application/json
Authorization: <string:token>
```
```json (Request)
{
    "category": "<string:category>"
}
```
```json (Response)
{
    "status": "success"/"failed",
    "msg": "<string:optional>"
}
```

## Remove category
REMOVE `api.axow.se/site/wiki/category/remove?category=<string:category>` (Authed)
```Headers (Request)
Content-Type: application/json
Authorization: <string:token>
```
```json (Response)
{
    "status": "success"/"failed",
    "msg": "<string:optional>"
}
```

## Get all subcategories
GET `api.axow.se/site/wiki/subcategories/getAll`
```json (Response)
{
    "status": "success"/"failed",
    "msg": "<string:optional>",
    "subcategories": [
        "<string:subcategory>"
    ]
}
```

## Add subcategory
POST `api.axow.se/site/wiki/subcategory/add` (Authed)
```Headers (Request)
Content-Type: application/json
Authorization: <string:token>
```
```json (Request)
{
    "subcategory": "<string:subcategory>"
}
```
```json (Response)
{
    "status": "success"/"failed",
    "msg": "<string:optional>"
}
```

## Modify subcategory
POST `api.axow.se/site/wiki/subcategory/update?subcategory=<string:subcategory>` (Authed)
```Headers (Request)
Content-Type: application/json
Authorization: <string:token>
```
```json (Request)
{
    "subcategory": "<string:subcategory>"
}
```
```json (Response)
{
    "status": "success"/"failed",
    "msg": "<string:optional>"
}
```

## Remove subcategory
REMOVE `api.axow.se/site/wiki/subcategory/remove?subcategory=<string:subcategory>` (Authed)
```Headers (Request)
Content-Type: application/json
Authorization: <string:token>
```
```json (Response)
{
    "status": "success"/"failed",
    "msg": "<string:optional>"
}
```

## Get wiki homepage information (description, editors, media, highlighted-articles)
GET `api.axow.se/site/wiki/home`
```json (Response)
{
    "status": "success"/"failed",
    "msg": "<string:optional>",
    "wiki_homepage": {
        "name": "<string>",
        "description": "<markdown>",
        "editors": [
            "<string:profile_id_with_@>"
        ],
        "highlight_media": {
            "carousel": [
                {...media_type_and_data...}
            ]
        },
        "highlight_articles": [
            {
                "category": "<string:category>",
                "subcategory": "<string:subcategory>",
                "page": "<string:article_id>",
                "href": "<string:optional:url>"
            }
        ]
    },
    "url_previews": {
        "<string:url>": {
            "type": "twitter"/"opengraph",
            "title": "<string>",
            "description": "<string>",
            "image": "<string>",
            "url": "<string>",
            "type": "<string=type/card>"
        },
        ...
    }
}
```
<br><br>

## Modify the wiki homepage
POST `api.axow.se/site/wiki/home/update?action=<merge/remove>` (Authed)
```Headers (Request)
Content-Type: application/json
Authorization: <string:token>
```
```json (Request)
{
    "wiki_homepage": {
        "name": "<string>",
        "description": "<markdown>",
        "editors": [
            "<string:profile_id_with_@>"
        ],
        "highlight_media": {
            "carousel": [
                {...media_type_and_data...}
            ]
        },
        "highlight_articles": [
            {
                "category": "<string:category>",
                "subcategory": "<string:subcategory>",
                "page": "<string:article_id>",
                "href": "<string:optional:url>"
            }
        ]
    }
}
```
(`merge` ensures al meta fields and replaces content, `remove` removes meta fields and does not touch content)
```json (Response)
{
    "status": "success"/"failed",
    "msg": "<string:optional>"
}
```
<br><br>

## Get wiki articles
GET `api.axow.se/site/wiki/articles/getAll?cat_filter=<string:optional>&subcat_filter=<string:optional>`
```json (Response)
{
    "status": "success"/"failed",
    "msg": "<string:optional>",
    "articles": [
        {
            "page": "<string:article_id>",
            "category": "<string:category>",
            "subcategory": "<string:subcategory>",
            "assets": [
                "<string:optional:url_or_base_url>"
            ],
            "title": "<string:optional>",
            "data": {...article_data...}
        }
        ...
    ]
}
```
<br><br>

## Get an article
GET `api.axow.se/site/wiki/articles/get?id=<string>`
```json (Response)
{
    "status": "success"/"failed",
    "msg": "<string:optional>",
    "article": {
        "page": "<string:article_id>",
        "category": "<string:category>",
        "subcategory": "<string:subcategory>",
        "assets": [ 
            "<string:optional:url_or_base_url>"
        ],
        "title": "<string:optional>",
        "data": {...article_data...}
    },
    "url_previews": {
        "<string:url>": {
            "type": "twitter"/"opengraph",
            "title": "<string>",
            "description": "<string>",
            "image": "<string>",
            "url": "<string>",
            "type": "<string=type/card>"
        },
        ...
    }
}
```
<br><br>

## Add an article
POST `api.axow.se/site/wiki/articles/add` (Authed)
```Headers (Request)
Content-Type: application/json
Authorization: <string:token>
```
```json (Request)
{
    "article": {
        "page": "<string:article_id>",
        "category": "<string:category>",
        "subcategory": "<string:subcategory>",
        "assets": [ 
            "<string:optional:url_or_base_url>"
        ],
        "title": "<string:optional>",
        "data": {...article_data...}
    }
}
```
<br><br>

## Modify an article
PUT `api.axow.se/site/wiki/articles/update?action=<merge/remove>` (Authed)
```Headers (Request)
Content-Type: application/json
Authorization: <string:token>
```
```json (Request)
{
    "article": {
        "page": "<string:article_id>",
        "category": "<string:category>",
        "subcategory": "<string:subcategory>",
        "assets": [ 
            "<string:optional:url_or_base_url>"
        ],
        "title": "<string:optional>",
        "data": {...article_data...}
    }
}
```
(`merge` ensures al meta fields and replaces content, `remove` removes meta fields and does not touch content)
```json (Response)
{
    "status": "success"/"failed",
    "msg": "<string:optional>"
}
```
<br><br>

## Remove an article
DELETE `api.axow.se/site/wiki/articles/remove?id=<string:article_id>` (Authed)
```Headers (Request)
Content-Type: application/json
Authorization: <string:token>
```
```json (Response)
{
    "status": "success"/"failed",
    "msg": "<string:optional>"
}
```
<br><br>

# Profiles
## Get all profiles
GET `api.axow.se/site/profiles/getAll`
```json (Response)
{
    "status": "success"/"failed",
    "msg": "<string:optional>",
    "profiles": [
        "<string:profile_id_with_@>"
    ]
}
```

## Get a profile
GET `api.axow.se/site/profiles/get?id=<string:profile_id_with_@>&contexts=<string:optional:comma_list>&social_filters=<string:optional:comma_list>`
```json (Response)
{
    "status": "success"/"failed",
    "msg": "<string:optional>",
    "profile": {
        "name": "<string>",
        "image": "<string:optional:url_or_base_url>",
        "description": "<string:optional>",
        "title": "<string:optional>",
        "socials": {
            "github":  "<string:optional:url>",
            "discord": "<string:optional:url>",
            "discord-server": "<string:optional:url>",
            "twitch": "<string:optional:url>",
            "youtube": "<string:optional:url>",
            "bluesky": "<string:optional:url>",
            "twitter": "<string:optional:url>",
            "instagram": "<string:optional:url>",
            "pinterest": "<string:optional:url>",
            "email": "<string:optional:url>",
            "domain": "<string:optional:url>"
        },
        "sections": {
            "<string:context>": "<string>"
            ...
        }
    },
    "url_previews": {
        "<string:url>": {
            "type": "twitter"/"opengraph",
            "title": "<string>",
            "description": "<string>",
            "image": "<string>",
            "url": "<string>",
            "type": "<string=type/card>"
        },
        ...
    }
}
```
<br><br>

## Add a profile
POST `api.axow.se/site/profiles/add` (Authed)
```Headers (Request)
Content-Type: application/json
Authorization: <string:token>
```
```json (Request)
{
    "id": "<string:profile_id_with_@>", // The new id
    "profile": {
        "name": "<string>",
        "image": "<string:optional:url_or_base_url>",
        "description": "<string:optional>",
        "title": "<string:optional>",
        "socials": {
            "github":  "<string:optional:url>",
            "discord": "<string:optional:url>",
            "discord-server": "<string:optional:url>",
            "twitch": "<string:optional:url>",
            "youtube": "<string:optional:url>",
            "bluesky": "<string:optional:url>",
            "twitter": "<string:optional:url>",
            "instagram": "<string:optional:url>",
            "pinterest": "<string:optional:url>",
            "email": "<string:optional:url>",
            "domain": "<string:optional:url>"
        },
        "sections": {
            "<string:context>": "<string>"
            ...
        }
    }
}
```
<br><br>

## Modify a profile
POST `api.axow.se/site/profiles/modify?id=<string:profile_id_with_@>&action=<merge/remove>` (Authed)
```Headers (Request)
Content-Type: application/json
Authorization: <string:token>
```
```json (Request)
{
    "profile": {
        "name": "<string>",
        "image": "<string:optional:url_or_base_url>",
        "description": "<string:optional>",
        "title": "<string:optional>",
        "socials": {
            "github":  "<string:optional:url>",
            "discord": "<string:optional:url>",
            "discord-server": "<string:optional:url>",
            "twitch": "<string:optional:url>",
            "youtube": "<string:optional:url>",
            "bluesky": "<string:optional:url>",
            "twitter": "<string:optional:url>",
            "instagram": "<string:optional:url>",
            "pinterest": "<string:optional:url>",
            "email": "<string:optional:url>",
            "domain": "<string:optional:url>"
        },
        "sections": {
            "<string:context>": "<string>"
            ...
        }
    }
}
```
(`merge` ensures al meta fields and replaces content, `remove` removes meta fields and does not touch content)
```json (Response)
{
    "status": "success"/"failed",
    "msg": "<string:optional>"
}
```
<br><br>

## Remove a profile
DELETE `api.axow.se/site/profiles/remove?id=<string:profile_id_with_@>` (Authed)
```Headers (Request)
Content-Type: application/json
Authorization: <string:token>
```
```json (Response)
{
    "status": "success"/"failed",
    "msg": "<string:optional>"
}
```
<br><br>