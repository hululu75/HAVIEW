<?php
/**
 * Cuisine 房间配置模板
 *
 * 使用说明:
 * 1. 运行 find-sensors.php 查找 Cuisine 的正确传感器 ID
 * 2. 将下面的 entity_id 替换为实际的传感器 ID
 * 3. 复制整个配置块到 config.php 的 sensor_groups 数组中
 */

// ⚠️  注意: 以下 entity_id 是示例，需要替换为实际的 ID
$cuisineConfig = [
    'id' => 'cuisine',
    'name' => [
        'fr' => 'Cuisine',
        'en' => 'Kitchen',
        'zh' => '厨房',
    ],
    'sensors' => [
        [
            'type' => 'temperature',
            // ⚠️  TODO: 运行 find-sensors.php 查找正确的 entity_id
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
            // ⚠️  TODO: 运行 find-sensors.php 查找正确的 entity_id
            'entity_id' => 'sensor.wen_shi_du_chuan_gan_qi_cuisine_humidity',
            'icon' => '💧',
            'name' => [
                'fr' => 'Humidité',
                'en' => 'Humidity',
                'zh' => '湿度',
            ],
        ],
    ],
];

// 打印格式化的配置（可以直接复制到 config.php）
echo "// 复制以下内容到 config.php 的 'sensor_groups' => [ ... ] 数组中\n\n";
echo var_export($cuisineConfig, true);
