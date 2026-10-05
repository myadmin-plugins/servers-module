<?php

declare(strict_types=1);

namespace Detain\MyAdminServers\Tests;

use Detain\MyAdminServers\NetworkInfoOutput;
use PHPUnit\Framework\TestCase;

/**
 * MyAdmin plan_2way §5.5, R2-M7: bin/get_server_network_info.php no longer
 * prints the asset passwords; everything else is printed as before.
 */
class NetworkInfoOutputTest extends TestCase
{
    public function testAssetPasswordsAreMaskedAndNothingElseChanges(): void
    {
        $info = [
            'vlans' => ['10.0.0.0/24'],
            'assets' => [
                42 => ['id' => 42, 'hostname' => 'srv42', 'ipmi_ip' => '10.1.1.42', 'asset_password' => "Win'Pw", 'ipmi_admin_password' => 'Admin-Pw', 'ipmi_client_password' => '', 'switchports' => []],
                43 => ['id' => 43, 'hostname' => 'srv43', 'ipmi_admin_password' => null],
            ],
        ];
        $out = NetworkInfoOutput::mask($info);
        $this->assertSame('[redacted]', $out['assets'][42]['asset_password']);
        $this->assertSame('[redacted]', $out['assets'][42]['ipmi_admin_password']);
        $this->assertSame('', $out['assets'][42]['ipmi_client_password'], 'an empty value stays visible as empty');
        $this->assertNull($out['assets'][43]['ipmi_admin_password']);
        $info['assets'][42]['asset_password'] = $info['assets'][42]['ipmi_admin_password'] = '[redacted]';
        $this->assertSame($info, $out);
        $this->assertFalse(NetworkInfoOutput::mask(false));
        $this->assertSame(['vlans' => []], NetworkInfoOutput::mask(['vlans' => []]));
    }

    public function testTheCliPrintsTheMaskedStructure(): void
    {
        $src = (string) file_get_contents(dirname(__DIR__) . '/bin/get_server_network_info.php');
        $this->assertStringContainsString('json_encode(\\Detain\\MyAdminServers\\NetworkInfoOutput::mask($info), JSON_PRETTY_PRINT)', $src);
    }
}
