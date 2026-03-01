<?php

return [
    // 后台登录密码（首次运行时会自动 bcrypt 写入数据库）
    'default_password' => 'bluelotus',
    'default_username' => 'admin',

    // JWT
    'jwt_secret' => 'CHANGE_ME_TO_A_RANDOM_STRING_' . md5(__DIR__),
    'jwt_expiry' => 86400, // 24 hours

    // 数据库
    'database_path' => __DIR__ . '/../storage/database.sqlite',

    // IP 数据库
    'qqwry_path' => __DIR__ . '/../../qqwry.dat',

    // 邮件通知
    'mail' => [
        'enabled' => false,
        'smtp_host' => 'smtp.xxx.com',
        'smtp_port' => 465,
        'smtp_secure' => 'ssl',
        'username' => 'xxx@xxx.com',
        'password' => 'xxxxxx',
        'from' => 'xxx@xxx.com',
        'to' => 'xxxx@xxxx.com',
    ],

    // 安全
    'max_login_attempts' => 5,
    'ban_duration' => 3600, // 1 hour

    // Keep Session
    'keep_session_enabled' => true,
];
