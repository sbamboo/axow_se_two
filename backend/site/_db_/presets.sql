-- Use the existing database
USE axow_se;

-- Index 0 (Full Access)
INSERT INTO user_permissions (string, digit_index, digit) VALUES 
    ("*", 0, 1);

-- Index 1 (articles)
INSERT INTO user_permissions (string, digit_index, digit) VALUES 
    ("articles.*", 1, 1),
    ("articles.add", 1, 2),
    ("articles.modify", 1, 3),
    ("articles.remove", 1, 4),
    ("articles.add-modify", 1, 5),
    ("articles.add-remove", 1, 6),
    ("articles.remove-modify", 1, 7);

-- Index 2 (articles-cat)
INSERT INTO user_permissions (string, digit_index, digit) VALUES 
    ("articles-cat.*", 2, 1),
    ("articles-cat.add", 2, 2),
    ("articles-cat.modify", 2, 3),
    ("articles-cat.remove", 2, 4),
    ("articles-cat.add-modify", 2, 5),
    ("articles-cat.add-remove", 2, 6),
    ("articles-cat.remove-modify", 2, 7);

-- Index 3 (articles-subcat)
INSERT INTO user_permissions (string, digit_index, digit) VALUES 
    ("articles-subcat.*", 3, 1),
    ("articles-subcat.add", 3, 2),
    ("articles-subcat.modify", 3, 3),
    ("articles-subcat.remove", 3, 4),
    ("articles-subcat.add-modify", 3, 5),
    ("articles-subcat.add-remove", 3, 6),
    ("articles-subcat.remove-modify", 3, 7);

-- Index 4 (article-own)
INSERT INTO user_permissions (string, digit_index, digit) VALUES 
    ("all-articles", 4, 1),
    ("your-articles", 4, 2);

-- Index 5 (wiki)
INSERT INTO user_permissions (string, digit_index, digit) VALUES 
    ("wiki.*", 5, 1),
    ("wiki.add", 5, 2),
    ("wiki.modify", 5, 3),
    ("wiki.remove", 5, 4),
    ("wiki.add-modify", 5, 5),
    ("wiki.add-remove", 5, 6),
    ("wiki.remove-modify", 5, 7);

-- Index 6 (wiki-page)
INSERT INTO user_permissions (string, digit_index, digit) VALUES 
    ("wiki-page.*", 6, 1),
    ("wiki-page.add", 6, 2),
    ("wiki-page.modify", 6, 3),
    ("wiki-page.remove", 6, 4),
    ("wiki-page.add-modify", 6, 5),
    ("wiki-page.add-remove", 6, 6),
    ("wiki-page.remove-modify", 6, 7);

-- Index 7 (wiki-cat)
INSERT INTO user_permissions (string, digit_index, digit) VALUES 
    ("wiki-cat.*", 7, 1),
    ("wiki-cat.add", 7, 2),
    ("wiki-cat.modify", 7, 3),
    ("wiki-cat.remove", 7, 4),
    ("wiki-cat.add-modify", 7, 5),
    ("wiki-cat.add-remove", 7, 6),
    ("wiki-cat.remove-modify", 7, 7);

-- Index 8 (profiles)
INSERT INTO user_permissions (string, digit_index, digit) VALUES 
    ("profiles.*", 8, 1),
    ("profiles.add", 8, 2),
    ("profiles.modify", 8, 3),
    ("profiles.remove", 8, 4),
    ("profiles.add-modify", 8, 5),
    ("profiles.add-remove", 8, 6),
    ("profiles.remove-modify", 8, 7);

-- Index 9 (profile scope)
INSERT INTO user_permissions (string, digit_index, digit) VALUES 
    ("all-profiles", 9, 1),
    ("your-profile", 9, 2);

-- Index 10 (url-preview)
INSERT INTO user_permissions (string, digit_index, digit) VALUES 
    ("url-preview.*", 10, 1),
    ("url-preview.fetch", 10, 2);

-- Add the admin user with full permissions
INSERT INTO users (username, password_hash) VALUES ("admin", "$2y$10$jYCWrLmfdm9MGrvKJ5D5yOwS3a0Bi6W5u1w0AXc9.0rIzCkZb9coi"); -- Temp "admin" password (sha256) CHANGE TO SAFER IN PROD
INSERT INTO users_to_permissions (user_id, permission_id) SELECT u.ID, p.ID FROM users u, user_permissions p WHERE u.username = "admin" AND p.string = "*";