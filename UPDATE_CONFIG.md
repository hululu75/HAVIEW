# 🔧 配置文件更新说明

## 问题：未找到传感器

如果您看到错误消息："未找到'YY的房间'的温度或湿度传感器"

这是因为 `config.php` 需要更新到新格式。

---

## ✅ 解决方案

### 方案1：手动更新配置文件

1. **打开您的 `config.php` 文件**

2. **在文件末尾的 `];` 之前添加以下配置**：

```php
    // Configuration des capteurs à afficher
    'sensor_groups' => [
        [
            'id' => 'yy_room',
            'name' => [
                'fr' => 'Chambre de YY',
                'en' => 'YY\'s Room',
                'zh' => 'YY的房间',
            ],
            'sensors' => [
                [
                    'type' => 'temperature',
                    'entity_id' => 'sensor.wen_shi_du_chuan_gan_qi_yy_temperature',
                    'icon' => '🌡️',
                    'name' => [
                        'fr' => 'Température',
                        'en' => 'Temperature',
                        'zh' => '温度',
                    ],
                ],
                [
                    'type' => 'humidity',
                    'entity_id' => 'sensor.wen_shi_du_chuan_gan_qi_yy_humidity',
                    'icon' => '💧',
                    'name' => [
                        'fr' => 'Humidité',
                        'en' => 'Humidity',
                        'zh' => '湿度',
                    ],
                ],
            ],
        ],
    ],

    'default_sensor_group' => 'yy_room',
```

3. **保存文件并刷新页面**

---

### 方案2：使用新的配置示例

1. **备份您当前的 config.php**：
   ```bash
   cp config.php config.php.backup
   ```

2. **复制新的配置示例**：
   ```bash
   cp config.example.php config.php
   ```

3. **编辑 config.php，填入您的信息**：
   - `home_assistant_url` - 您的 Home Assistant 地址
   - `access_token` - 您的访问令牌
   - 其他设置保持默认即可

---

## 📋 完整的 config.php 示例

```php
<?php
return [
    'home_assistant_url' => 'https://homeassistant.familyzhao.fr',
    'access_token' => '您的token',
    'timeout' => 10,

    'sensor_groups' => [
        [
            'id' => 'yy_room',
            'name' => [
                'fr' => 'Chambre de YY',
                'en' => 'YY\'s Room',
                'zh' => 'YY的房间',
            ],
            'sensors' => [
                [
                    'type' => 'temperature',
                    'entity_id' => 'sensor.wen_shi_du_chuan_gan_qi_yy_temperature',
                    'icon' => '🌡️',
                    'name' => [
                        'fr' => 'Température',
                        'en' => 'Temperature',
                        'zh' => '温度',
                    ],
                ],
                [
                    'type' => 'humidity',
                    'entity_id' => 'sensor.wen_shi_du_chuan_gan_qi_yy_humidity',
                    'icon' => '💧',
                    'name' => [
                        'fr' => 'Humidité',
                        'en' => 'Humidity',
                        'zh' => '湿度',
                    ],
                ],
            ],
        ],
    ],

    'default_sensor_group' => 'yy_room',
];
```

---

## ❓ 常见问题

### Q: 我不知道我的 entity_id 是什么？

**A:** 访问 `index.php`（如果还存在）或者在 Home Assistant 中：
1. 进入 开发者工具 → 状态
2. 搜索 "YY" 或 "温湿度"
3. 复制 entity_id

### Q: 配置后还是看不到传感器？

**A:** 检查以下几点：
1. ✅ `entity_id` 是否正确
2. ✅ Home Assistant 中该传感器是否存在
3. ✅ access_token 是否有效
4. ✅ PHP 语法是否正确（逗号、引号）

### Q: 如何添加更多传感器？

**A:** 在 `sensors` 数组中添加新项：
```php
[
    'type' => 'pressure',  // 类型：自定义
    'entity_id' => 'sensor.your_sensor_id',
    'icon' => '🎈',
    'name' => [
        'fr' => 'Pression',
        'en' => 'Pressure',
        'zh' => '气压',
    ],
],
```

---

## 🆘 需要帮助？

如果更新后仍有问题，请提供：
1. 错误消息截图
2. 您的 entity_id（从 Home Assistant 复制）
3. 浏览器控制台的错误信息
