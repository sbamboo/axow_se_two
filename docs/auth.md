## Example JWT
```json
{
  "usr": "12345",
  "iat": 1711036800,
  "exp": 1711123200,
  "tt": 1,
  "perm": "030010022"
}
```

---

## Field Explanations

| Field  | Description |
|--------|------------|
| `usr`  | User ID (identifies who the token belongs to). |
| `iat`  | **Issued At** – Unix timestamp of when the token was created. |
| `exp`  | **Expiration** – Unix timestamp of when the token expires. |
| `tt`   | **Token Type** – Defines the type of token (see mappings below). |
| `perm` | **Permissions** – Encoded string defining access levels (see mappings below). |

---

## Token Type (`tt`) Mappings

| Value | Token Type | Description |
|--------|------------|-------------|
| `0`  | Single-Use | One-time token. |
| `1`  | Single     | Only one active token, no refresh key. |
| `2`  | Pair       | Requires a Refresh token to refresh. |
| `3`  | Refresh    | Used to refresh a pair token. |

---

## Permissions (`perm`) Mapping

| Index | Options   | Permission-Key Mapping |
|--------|-----------|------------------------|
|  `0`  | `0/1`       | `0`: None; `1`: `*` (Full Access) |
|  `1`  | `0-7`       | `0`: None; `1`: `articles.*`; `2`: `articles.add`; `3`: `articles.modify`; `4`: `articles.remove`; `5`: `articles.add-modify`; `6`: `articles.add-remove`; `7`: `articles.remove-modify` |
|  `2`  | `0-7`       | `0`: None; `1`: `articles-cat.*`; `2`: `articles-cat.add`; `3`: `articles-cat.modify`; `4`: `articles-cat.remove`; `5`: `articles-cat.add-modify`; `6`: `articles-cat.add-remove`; `7`: `articles-cat.remove-modify` |
|  `3`  | `0-7`       | `0`: None; `1`: `articles-subcat.*`; `2`: `articles-subcat.add`; `3`: `articles-subcat.modify`; `4`: `articles-subcat.remove`; `5`: `articles-subcat.add-modify`; `6`: `articles-subcat.add-remove`; `7`: `articles-subcat.remove-modify` |
|  `4`  | `0-7`       | `0`: None; `1`: `wiki.*`; `2`: `wiki.add`; `3`: `wiki.modify`; `4`: `wiki.remove`; `5`: `wiki.add-modify`; `6`: `wiki.add-remove`; `7`: `wiki.remove-modify` |
|  `5`  | `0-7`       | `0`: None; `1`: `wiki-cat.*`; `2`: `wiki-cat.add`; `3`: `wiki-cat.modify`; `4`: `wiki-cat.remove`; `5`: `wiki-cat.add-modify`; `6`: `wiki-cat.add-remove`; `7`: `wiki-cat.remove-modify` |
|  `6`  | `0-7`       | `0`: None; `1`: `wiki-subcat.*`; `2`: `wiki-subcat.add`; `3`: `wiki-subcat.modify`; `4`: `wiki-subcat.remove`; `5`: `wiki-subcat.add-modify`; `6`: `wiki-subcat.add-remove`; `7`: `wiki-subcat.remove-modify` |
|  `7`  | `0-2`       | `0`: None; `1`: `wiki-home.*`; `2`: `wiki-home.update` |
|  `8`  | `0-7`       | `0`: None; `1`: `profiles.*`; `2`: `profiles.add`; `3`: `profiles.modify`; `4`: `profiles.remove`; `5`: `profiles.add-modify`; `6`: `profiles.add-remove`; `7`: `profiles.remove-modify` |
|  `9`  | `0-2`       | `0`: None; `1`: `all-profiles`; `2`: `your-profile` |
|  `10` | `0-2`       | `0`: None; `1`: `url-preview.*`; `2`: `url-preview.fetch` |
