# 🔧 修复 Emoji 显示问题指南

## 问题描述

如果您在使用 vim 编辑 config.php 时遇到 emoji 显示或编辑问题，本指南提供多种解决方案。

---

## 解决方案 1: 使用自动修复脚本（推荐）

最简单的方法是使用我们提供的自动修复脚本：

```bash
php fix-emoji.php
```

**这个脚本会:**
- ✅ 自动检测并修复损坏的 emoji 格式
- ✅ 创建备份文件 (config.php.emoji-backup)
- ✅ 显示修改的内容
- ✅ 检查可能的配置错误

**输出示例:**
```
🔧 Config.php Emoji 修复工具
==================================================

📦 创建备份: config.php.emoji-backup
📖 读取 config.php...

🔍 检测并修复 emoji 格式问题...

  ✓ 修复 2 个 emoji 问题

✅ 成功修复 2 个问题
✅ 已保存到 config.php
```

---

## 解决方案 2: 使用其他文本编辑器

### 选项 A: 使用 nano 编辑器

nano 编辑器对 UTF-8 和 emoji 支持更好：

```bash
nano config.php
```

**nano 基本操作:**
- 使用方向键移动光标
- 直接输入 emoji（如果终端支持）
- `Ctrl + O` 保存
- `Ctrl + X` 退出

### 选项 B: 使用 sed 命令直接替换

如果只需要修复特定的 emoji 问题：

```bash
# 备份文件
cp config.php config.php.backup

# 修复温度 emoji
sed -i "s/'icon' => '[^']*️\s*',/'icon' => '🌡️',/g" config.php

# 修复湿度 emoji
sed -i "s/'icon' => '\[52;34H',/'icon' => '💧',/g" config.php
```

### 选项 C: 通过 Web 界面编辑

如果您有文件管理器或 Web IDE，可以通过浏览器编辑，浏览器通常能正确显示 emoji。

---

## 解决方案 3: 手动复制正确的配置

### 步骤 1: 查找 Cuisine 传感器

运行传感器查找工具：

```bash
# 在浏览器中访问
http://your-server/find-sensors.php
```

或在命令行中查看所有传感器：

```bash
php -r "
require 'config.php';
require_once 'HomeAssistantClient.php';
\$config = require 'config.php';
\$client = new HomeAssistantClient(\$config['home_assistant_url'], \$config['access_token']);
\$states = \$client->getStates();
foreach (\$states as \$state) {
    if (stripos(\$state['entity_id'], 'cuisine') !== false ||
        stripos(\$state['attributes']['friendly_name'] ?? '', 'cuisine') !== false) {
        echo \$state['entity_id'] . ' - ' . (\$state['attributes']['friendly_name'] ?? '') . \"\n\";
    }
}
"
```

### 步骤 2: 获取配置模板

```bash
php cuisine-config-template.php > cuisine-config.txt
```

### 步骤 3: 复制正确的配置

打开 `config.php` 并找到 Cuisine 配置部分，替换为正确的内容。

---

## 完整的 Cuisine 配置示例

将以下配置添加到 `config.php` 的 `'sensor_groups' => [...]` 数组中：

```php
[
    'id' => 'cuisine',
    'name' => [
        'fr' => 'Cuisine',
        'en' => 'Kitchen',
        'zh' => '厨房',
    ],
    'sensors' => [
        [
            'type' => 'temperature',
            'entity_id' => 'sensor.CUISINE_TEMPERATURE_ENTITY_ID', // ⚠️ 替换为实际 ID
            'icon' => '🌡️',
            'name' => [
                'fr' => 'Température',
                'en' => 'Temperature',
                'zh' => '温度',
            ],
        ],
        [
            'type' => 'humidity',
            'entity_id' => 'sensor.CUISINE_HUMIDITY_ENTITY_ID', // ⚠️ 替换为实际 ID
            'icon' => '💧',
            'name' => [
                'fr' => 'Humidité',
                'en' => 'Humidity',
                'zh' => '湿度',
            ],
        ],
    ],
],
```

---

## 验证配置

修复后，验证配置是否正确：

### 1. 检查配置文件

```bash
# 在浏览器中访问
http://your-server/check-config.php
```

### 2. 测试页面切换

```bash
# 在浏览器中访问
http://your-server/sensors.php
```

应该能看到页面顶部的房间选择器：
- YY的房间
- 厨房 (Cuisine)

点击切换测试。

### 3. 检查 PHP 语法

```bash
php -l config.php
```

应该显示: `No syntax errors detected in config.php`

---

## 常见问题

### Q1: 修复脚本运行后还是有问题？

**A:** 检查以下几点：

1. **Entity ID 是否正确:**
   ```bash
   php find-sensors.php
   ```
   在输出中查找 Cuisine 相关的传感器

2. **PHP 语法是否正确:**
   ```bash
   php -l config.php
   ```

3. **查看详细错误:**
   访问 `check-config.php` 查看具体错误信息

### Q2: 如何恢复原文件？

**A:** 如果修复后有问题，恢复备份：

```bash
cp config.php.emoji-backup config.php
```

或者如果使用 sed 备份：

```bash
cp config.php.backup config.php
```

### Q3: 找不到 Cuisine 的传感器？

**A:** 可能的原因：

1. **传感器名称不包含 "Cuisine":**
   在 Home Assistant 中检查传感器的友好名称

2. **使用 find-sensors.php 搜索所有传感器:**
   访问 `find-sensors.php`，会显示所有可用的传感器

3. **传感器可能离线:**
   在 Home Assistant 中检查传感器状态

### Q4: 页面切换不工作？

**A:** 检查：

1. **确保 config.php 中有多个 sensor_groups**
2. **确保每个 group 都有唯一的 'id'**
3. **检查浏览器控制台是否有 JavaScript 错误**
4. **尝试手动访问:** `sensors.php?group=cuisine`

---

## 完整修复流程

### 推荐步骤（从头到尾）：

```bash
# 1. 运行自动修复脚本
php fix-emoji.php

# 2. 查找 Cuisine 传感器
# 在浏览器访问: http://your-server/find-sensors.php
# 或运行:
php -r "require 'find-sensors.php';"

# 3. 验证配置
# 在浏览器访问: http://your-server/check-config.php

# 4. 测试主页面
# 在浏览器访问: http://your-server/sensors.php
```

---

## 需要帮助？

如果以上方法都无法解决问题，请提供以下信息：

1. `php fix-emoji.php` 的输出
2. `php -l config.php` 的输出
3. `check-config.php` 页面的截图
4. 从 `find-sensors.php` 复制的 Cuisine 传感器 entity_id

---

## 技术说明

### 为什么 vim 无法编辑 emoji？

Vim 对 UTF-8 多字节字符（如 emoji）的支持取决于：
- 终端的 UTF-8 支持
- Vim 的编译选项
- locale 设置

某些 emoji 使用变异选择器（Variation Selector），如 `🌡️` 实际是两个 Unicode 字符：
- U+1F321 (THERMOMETER)
- U+FE0F (VARIATION SELECTOR-16)

在不支持的终端中，这些字符可能显示为 `️  ` 或乱码。

### 推荐的编辑环境设置

如果您想让 vim 支持 emoji：

```bash
# 添加到 ~/.vimrc
set encoding=utf-8
set fileencoding=utf-8
set termencoding=utf-8
```

但更简单的方法是使用 nano 或 GUI 编辑器。
