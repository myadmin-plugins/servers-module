<?php

namespace Detain\MyAdminServers;

/**
 * Output masking for bin/get_server_network_info.php (MyAdmin plan_2way §5.5,
 * review 2 R2-M7): get_server_network_info() returns every asset row whole,
 * three passwords included (plaintext today, SecretBox envelopes after the
 * flip). The CLI prints the rest of the structure as before.
 */
class NetworkInfoOutput
{
    public const SECRET_KEYS = ['asset_password', 'ipmi_admin_password', 'ipmi_client_password'];

    /**
     * @param mixed $info get_server_network_info()'s result
     * @return mixed the same structure with each non-empty asset password masked
     */
    public static function mask($info)
    {
        if (!is_array($info) || !isset($info['assets']) || !is_array($info['assets'])) {
            return $info;
        }
        foreach ($info['assets'] as $id => $asset) {
            if (!is_array($asset)) {
                continue;
            }
            foreach (self::SECRET_KEYS as $key) {
                if (isset($asset[$key]) && $asset[$key] !== '') {
                    $info['assets'][$id][$key] = '[redacted]';
                }
            }
        }
        return $info;
    }
}
