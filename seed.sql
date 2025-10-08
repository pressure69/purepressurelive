-- PurePressureLive seed accounts

INSERT INTO users (username, email, password, role)
VALUES (
    'admin',
    'admin@purepressurelive.com',
    '$2y$10$y/ztwF6S6vB21PHR4DnO2OD8HnbOeNn8uU4cFoSg4n3n.R3PX98M6',
    'admin'
)
ON DUPLICATE KEY UPDATE username=username;

INSERT INTO users (username, email, password, role)
VALUES (
    'testmodel',
    'model@purepressurelive.com',
    '$2y$10$M7nMbM9JfQe7A8kZ2bcxwO9F/9lx12H4FZSPyWeXryvK6G8kFw2Wq',
    'model'
)
ON DUPLICATE KEY UPDATE username=username;

INSERT INTO models (user_id, stream_key, goal_tokens, earned_tokens, preview_image, bio)
SELECT id, 'demo_stream_key_1234567890abcdef', 2000, 250, NULL, 'I am your demo model ✨'
FROM users WHERE username='testmodel'
ON DUPLICATE KEY UPDATE stream_key=stream_key;
