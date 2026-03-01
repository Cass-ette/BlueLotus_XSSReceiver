-- XSS 记录表
CREATE TABLE IF NOT EXISTS records (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_ip TEXT NOT NULL,
    user_port TEXT,
    protocol TEXT,
    request_method TEXT,
    request_uri TEXT,
    request_time INTEGER NOT NULL,
    location TEXT,
    headers_data TEXT,
    get_data TEXT,
    decoded_get_data TEXT,
    post_data TEXT,
    decoded_post_data TEXT,
    cookie_data TEXT,
    decoded_cookie_data TEXT,
    keepsession INTEGER DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
CREATE INDEX IF NOT EXISTS idx_records_request_time ON records(request_time);

-- JS 脚本表（合并 template 和 myjs）
CREATE TABLE IF NOT EXISTS scripts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    description TEXT,
    content TEXT,
    type TEXT NOT NULL CHECK(type IN ('template', 'myjs')),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(name, type)
);

-- 用户表
CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT NOT NULL UNIQUE,
    password_hash TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- IP 封禁表
CREATE TABLE IF NOT EXISTS banned_ips (
    ip TEXT PRIMARY KEY,
    attempts INTEGER DEFAULT 0,
    banned_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
