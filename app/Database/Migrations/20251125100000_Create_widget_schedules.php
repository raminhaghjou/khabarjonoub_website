<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class Create_widget_schedules extends Migration
{
    public function up()
    {
        $table = $this->db->DBPrefix . 'widget_schedules';
        $this->db->query('CREATE TABLE IF NOT EXISTS `' . $table . '` (
            `id` INT NOT NULL AUTO_INCREMENT,
            `widget_id` INT NOT NULL,
            `content` MEDIUMTEXT NULL,
            `publish_at` DATETIME NOT NULL,
            `created_at` DATETIME NULL DEFAULT NULL,
            PRIMARY KEY (`id`),
            KEY `idx_widget_publish` (`widget_id`, `publish_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3');
    }

    public function down()
    {
        $this->forge->dropTable('widget_schedules', true);
    }
}
