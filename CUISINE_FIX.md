# 🔧 Cuisine 房间配置修复说明

## 问题描述

用户点击"厨房"按钮后，页面显示的仍然是 YY 房间的数据，没有切换。

## 根本原因

config.php 中 Cuisine 房间的湿度传感器配置错误：

```php
// ❌ 错误配置
[
    'id' => 'Cuisine_room',
    'sensors' => [
        [
            'type' => 'humidity',
            'entity_id' => 'sensor.wen_shi_du_chuan_gan_qi_yy_humidite',  // 使用了 YY 的传感器！
            ...
        ],
    ],
]
```

**问题：**
1. Cuisine 的湿度传感器使用了 YY 房间的 entity_id
2. 导致切换到厨房时，仍然显示 YY 房间的湿度数据

## 修复方案

### 修复 1: 更正湿度传感器 Entity ID

```php
// ✅ 正确配置
[
    'type' => 'humidity',
    'entity_id' => 'sensor.wen_shi_du_chuan_gan_qi_cuisine_humidite',  // 使用 Cuisine 自己的传感器
    'icon' => '💧',
    'name' => [
        'fr' => 'Humidité',
        'en' => 'Humidity',
        'zh' => '湿度',
    ],
]
```

### 修复 2: 规范化房间 ID

```php
// ❌ 之前
'id' => 'Cuisine_room',

// ✅ 现在
'id' => 'cuisine',
```

**理由：**
- 保持一致性（yy_room 使用下划线，cuisine 使用小写）
- 简化 URL 参数（`?group=cuisine` 比 `?group=Cuisine_room` 更简洁）

## 完整的 Cuisine 配置

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
            'entity_id' => 'sensor.wen_shi_du_chuan_gan_qi_cuisine_temperature',
            'icon' => '🌡️',
            'name' => [
                'fr' => 'Température',
                'en' => 'Temperature',
                'zh' => '温度',
            ],
        ],
        [
            'type' => 'humidity',
            'entity_id' => 'sensor.wen_shi_du_chuan_gan_qi_cuisine_humidite',
            'icon' => '💧',
            'name' => [
                'fr' => 'Humidité',
                'en' => 'Humidity',
                'zh' => '湿度',
            ],
        ],
    ],
]
```

## 验证步骤

修复后，请按以下步骤验证：

### 1. 检查配置语法

```bash
php -l config.php
```

应该显示：`No syntax errors detected in config.php`

### 2. 运行配置检查工具

在浏览器访问：
```
http://your-server/check-config.php
```

应该看到：
- ✅ 2 个传感器组（YY的房间、厨房）
- ✅ 每个组有 2 个传感器（温度、湿度）
- ✅ Entity IDs 正确

### 3. 测试页面切换

访问：
```
http://your-server/sensors.php
```

**测试步骤：**
1. 默认显示 "YY的房间"
2. 看到两个传感器卡片：
   - 温度：sensor.wen_shi_du_chuan_gan_qi_yy_temperature
   - 湿度：sensor.wen_shi_du_chuan_gan_qi_yy_humidite

3. 点击页面顶部的 "厨房" 按钮
4. 页面应该刷新并显示：
   - 标题变为 "🌡️ 厨房"
   - 温度：sensor.wen_shi_du_chuan_gan_qi_cuisine_temperature
   - 湿度：sensor.wen_shi_du_chuan_gan_qi_cuisine_humidite

5. 点击 "YY的房间" 按钮
6. 页面切换回 YY 房间数据

### 4. 测试历史数据

点击任一传感器卡片，应该跳转到 history.php 并显示对应传感器的历史数据。

## 常见问题

### Q1: 点击厨房后还是显示 YY 房间？

**可能原因：**
- 浏览器缓存。解决方法：强制刷新（Ctrl+Shift+R 或 Cmd+Shift+R）
- config.php 没有保存修改。检查文件内容是否正确

### Q2: 提示"未找到传感器"？

**可能原因：**
- Entity ID 拼写错误
- Home Assistant 中该传感器不存在或离线

**解决方法：**
```bash
# 访问查找传感器工具
http://your-server/find-sensors.php
```

搜索 "cuisine" 或 "厨房"，复制正确的 entity_id

### Q3: 显示 "unavailable" 或 "unknown"？

**可能原因：**
- 传感器离线或电池耗尽
- Home Assistant 中传感器未正确配置

**解决方法：**
在 Home Assistant 中检查传感器状态

## 相关文件

- `config.php` - 主配置文件
- `sensors.php` - 传感器显示页面（sensors.php:26 读取 group 参数）
- `history.php` - 历史数据页面
- `check-config.php` - 配置验证工具
- `find-sensors.php` - 传感器查找工具

## Entity ID 命名规范

从配置中可以看出，您的 Home Assistant 使用的命名规范：

```
sensor.wen_shi_du_chuan_gan_qi_{房间名}_{类型}
```

**示例：**
- YY 温度：`sensor.wen_shi_du_chuan_gan_qi_yy_temperature`
- YY 湿度：`sensor.wen_shi_du_chuan_gan_qi_yy_humidite`
- Cuisine 温度：`sensor.wen_shi_du_chuan_gan_qi_cuisine_temperature`
- Cuisine 湿度：`sensor.wen_shi_du_chuan_gan_qi_cuisine_humidite`

**注意：** 湿度使用的是 `humidite`（法语），不是 `humidity`（英语）

## 添加更多房间

如果需要添加更多房间，可以参考 Cuisine 的配置模板：

```php
[
    'id' => 'room_id',  // 唯一标识符，小写字母和下划线
    'name' => [
        'fr' => '法语名称',
        'en' => 'English Name',
        'zh' => '中文名称',
    ],
    'sensors' => [
        [
            'type' => 'temperature',
            'entity_id' => 'sensor.wen_shi_du_chuan_gan_qi_ROOM_temperature',
            'icon' => '🌡️',
            'name' => [
                'fr' => 'Température',
                'en' => 'Temperature',
                'zh' => '温度',
            ],
        ],
        [
            'type' => 'humidity',
            'entity_id' => 'sensor.wen_shi_du_chuan_gan_qi_ROOM_humidite',
            'icon' => '💧',
            'name' => [
                'fr' => 'Humidité',
                'en' => 'Humidity',
                'zh' => '湿度',
            ],
        ],
    ],
]
```

将 `ROOM` 替换为实际的房间标识符，添加到 `sensor_groups` 数组中即可。

---

**修复时间：** 2024-11-07
**修复人：** Claude Code
**版本：** 1.0
