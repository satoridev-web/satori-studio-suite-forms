<?php
/**
 * ================================================================
 * SATORI Forms Submission Repository
 * ================================================================
 */

declare(strict_types=1);

namespace Satori\Forms\Core;

final class SubmissionRepository
{
    public function persist(int $formId, array $data, ?string $ipAddress): ?int
    {
        global $wpdb;

        $tableName = SubmissionTable::table_name();
        $payload = wp_json_encode($data);
        if ($payload === false) {
            return null;
        }
        $timestamp = gmdate('Y-m-d H:i:s');
        $ipAddress = $this->sanitize_ip($ipAddress);

        $inserted = $wpdb->insert(
            $tableName,
            [
                'form_id' => $formId,
                'data' => $payload,
                'submitted_at' => $timestamp,
                'ip_address' => $ipAddress,
            ],
            [
                '%d',
                '%s',
                '%s',
                '%s',
            ]
        );

        if ($inserted === false) {
            return null;
        }

        return (int) $wpdb->insert_id;
    }

    private function sanitize_ip(?string $ipAddress): ?string
    {
        if ($ipAddress === null || $ipAddress === '') {
            return null;
        }

        $ipAddress = wp_unslash($ipAddress);

        if (filter_var($ipAddress, FILTER_VALIDATE_IP) === false) {
            return null;
        }

        return $ipAddress;
    }
}
