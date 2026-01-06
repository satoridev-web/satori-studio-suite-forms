<?php
/**
 * ================================================================
 * SATORI Forms Submission Table
 * ================================================================
 */

declare(strict_types=1);

namespace Satori\Forms\Core;

final class SubmissionTable
{
    private const TABLE_NAME = 'satori_form_submissions';

    public static function activate(): void
    {
        $table = new self();
        $table->create_table();
    }

    public static function table_name(): string
    {
        global $wpdb;

        return $wpdb->prefix . self::TABLE_NAME;
    }

    public function create_table(): void
    {
        global $wpdb;

        $tableName = self::table_name();
        $charsetCollate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$tableName} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            form_id bigint(20) unsigned NOT NULL,
            data longtext NOT NULL,
            submitted_at datetime NOT NULL,
            ip_address varchar(45) NULL,
            PRIMARY KEY  (id)
        ) {$charsetCollate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }
}
