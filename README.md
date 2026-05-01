# 🛡️ 溯真防伪查询系统

## 📖 项目简介

这是一个基于 Web 的防伪查询系统，用于验证产品的真伪。系统支持用户通过序列号验证产品，支持管理员后台管理，包括产品添加、编辑、删除、批量导入导出、模板管理、管理员管理、系统设置等功能。同时提供工厂模式 API，供生产系统直接提交产品数据。

## ✨ 功能特性

- ✅ **用户查询**：用户可以通过输入序列号（SN）验证产品真伪，支持 GET 和 POST 两种查询方式。
- 🔧 **管理员后台**：管理员可登录后台进行产品管理、类型管理、模板管理、管理员管理、系统设置等操作。
- 🏭 **工厂模式**：提供独立的工厂 API 接口，供生产系统直接 POST JSON 提交产品数据（序列号由调用方提供，支持图片路径等字段）。
- 📦 **批量操作**：支持批量导入（Excel）、批量导出、批量修改、批量删除产品。
- 📋 **模板管理**：保存常用产品信息为模板，添加产品时可一键导入。
- 📝 **系统日志**：记录管理员操作日志，支持日志开关、保留天数设置和清空操作。
- 📤 **文件上传**：支持上传产品图片，提供图片库选择功能。
- 🔢 **序列号规则**：可配置序列号分组数量和每组字符数。
- 🔑 **权限控制**：区分超级管理员和普通管理员，不同权限对应不同操作范围。

## 💻 技术栈

- 🌐 前端：HTML5, CSS3, JavaScript（ES6+）
- 🎨 UI 框架：Bootstrap 5.3 + Bootstrap Icons
- 📊 数据表格：SheetJS (xlsx) 用于 Excel 导入导出
- ⚙️ 后端：PHP 7.0+
- 🗄️ 数据库：MySQL 5.7+（通过 db.sql 初始化）
- 🔐 密码加密：bcrypt

## 🚀 安装与部署

1. **环境要求**：
   - 🐘 PHP 7.0+
   - 🐬 MySQL 5.7+
   - 🌐 Web 服务器（如 Apache 或 Nginx）

2. **数据库设置**：
   - 📥 导入 `db.sql` 文件到 MySQL 数据库
   - ⚙️ 修改 `api/config.php` 中的数据库连接信息

3. **部署步骤**：
   - 📤 将项目文件上传到 Web 服务器根目录
   - 🔒 确保 `uploads/` 目录有写权限
   - 🌟 访问 `index.html` 开始使用
   - 🔑 默认管理员账户请参考 `db.sql` 中的初始数据

## 📋 使用说明

- 🔍 **用户查询**：访问 `index.html`，输入序列号进行验证。
- 🔐 **管理员登录**：访问 `login.html` 登录后台。
- 🛠️ **后台管理**：登录后进入 `admin.html` 可进行产品管理、日志查看等。
- 🏭 **工厂模式**：在系统设置中启用工厂模式并配置访问口令后，通过 POST 请求调用 `api/factory_api.php?token=口令` 提交产品数据。

### 工厂 API 调用示例

```
POST /api/factory_api.php?token=口令
Content-Type: application/json

{
  "sn": "MOB-20260502-0001",
  "product_type": "手机",
  "product_name": "旗舰手机 14 Pro",
  "product_model": "M2104K10AC",
  "sn_code": "ABC1234567890",
  "sales_channel": "天猫",
  "manual_link": "https://example.com/manual.pdf",
  "image": "uploads/demo.jpg",
  "remarks": "备注信息"
}
```

浏览器调用示例：
```javascript
fetch('https://你的域名/api/factory_api.php?token=口令', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    sn: 'MOB-20260502-0001',
    product_type: '手机',
    product_name: '旗舰手机 14 Pro',
    product_model: 'M2104K10AC'
  })
})
.then(r => r.json())
.then(data => console.log(data));
```

必填字段：`sn`、`product_type`、`product_name`、`product_model`

## 📁 目录架构

```
查询项目/
├── 📄 index.html                        # 🏠 主页，用户查询界面
├── 📄 login.html                        # 🔑 管理员登录页面
├── 📄 admin.html                        # ⚙️ 管理员后台管理页面
├── 📄 db.sql                            # 🗄️ 数据库初始化脚本
├── 📄 批量导入模板.xlsx                  # 📊 批量导入 Excel 模板
├── 📄 README.md                         # 📖 项目说明文档
│
├── 📁 api/                              # 🔌 后端 API 接口
│   ├── 📄 api.php                       # 📥 API 通用入口（POST 查询）
│   ├── 📄 config.php                    # ⚙️ 数据库连接配置
│   ├── 📄 query.php                     # 🔍 用户查询接口（GET/POST）
│   ├── 📄 site_config.php               # 🌐 站点公共配置
│   ├── 📄 factory_api.php               # 🏭 工厂模式数据接收接口
│   │
│   └── 📁 admin/                        # 👨‍💼 管理员后台 API
│       ├── 📄 login.php                 # 🔐 管理员登录
│       ├── 📄 logout.php                # 🚪 管理员登出
│       ├── 📄 check_auth.php            # 🛡️ 权限验证中间件
│       │
│       ├── 📄 add.php                   # ➕ 添加单个产品
│       ├── 📄 edit.php                  # ✏️ 编辑单个产品
│       ├── 📄 delete.php                # 🗑️ 删除单个产品
│       ├── 📄 list.php                  # 📋 产品列表查询
│       ├── 📄 batch_add.php             # 📦 批量导入产品（Excel）
│       ├── 📄 batch_edit.php            # 📝 批量修改产品
│       ├── 📄 batch_delete.php          # 🗑️ 批量删除产品
│       ├── 📄 upload.php                # 📤 文件上传
│       ├── 📄 gallery.php               # 🖼️ 图片库管理
│       ├── 📄 refresh_sn.php            # 🔄 刷新/重新生成序列号
│       ├── 📄 stats.php                 # 📊 数据统计
│       │
│       ├── 📄 admin_add.php             # 👤 添加管理员
│       ├── 📄 admin_edit.php            # ✏️ 编辑管理员
│       ├── 📄 admin_delete.php          # 🗑️ 删除管理员
│       ├── 📄 admin_list.php            # 👥 管理员列表
│       │
│       ├── 📄 type_add.php              # ➕ 添加产品类型
│       ├── 📄 type_edit.php             # ✏️ 编辑产品类型
│       ├── 📄 type_delete.php           # 🗑️ 删除产品类型
│       ├── 📄 type_list.php             # 📋 产品类型列表
│       │
│       ├── 📄 template_add.php          # 📄 添加产品模板
│       ├── 📄 template_delete.php       # 🗑️ 删除产品模板
│       ├── 📄 template_list.php         # 📋 产品模板列表
│       │
│       ├── 📄 log_list.php              # 📋 操作日志查看
│       ├── 📄 log_config.php            # ⚙️ 日志配置（开关、保留天数、清空）
│       │
│       └── 📄 settings.php              # ⚙️ 系统设置管理
│
├── 📁 css/                              # 🎨 层叠样式表
│   ├── 📄 admin.css                     # 管理员后台样式
│   ├── 📄 index.css                     # 主页/查询界面样式
│   └── 📄 login.css                     # 登录页面样式
│
├── 📁 js/                               # ⚡ JavaScript 脚本
│   ├── 📄 admin.js                      # 管理员后台逻辑
│   ├── 📄 index.js                      # 主页/查询界面逻辑
│   └── 📄 login.js                      # 登录页面逻辑
│
├── 📁 images/                           # 🖼️ 静态图片资源
├── 📁 uploads/                          # 📤 用户上传文件存储
│   └── 📄 placeholder.txt              # 目录占位文件
│
└── 📁 temp_bcrypt/                      # 🔐 bcrypt 密码哈希临时目录
```

## ⚠️ 注意事项

- 🔒 请确保服务器安全，定期备份数据库。
- 🛡️ 上传文件需注意安全，避免恶意文件上传。
- 🔑 首次部署后请立即修改默认管理员密码。
- 🏭 工厂 API 口令建议使用强随机字符串，生产环境务必配置口令。
- 🐛 如有问题，请检查 PHP 错误日志。

## 📜 许可证

本项目采用AGPL-3.0许可证。