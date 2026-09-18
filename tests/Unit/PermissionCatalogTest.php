<?php
// tests/Unit/PermissionCatalogTest.php
namespace Tests\Unit;

use App\Traits\PermissionsTrait;
use PHPUnit\Framework\TestCase;

class PermissionCatalogTest extends TestCase
{
    use PermissionsTrait;

    public function test_catalog_has_expected_groups_and_drops_dead_ones(): void
    {
        $groups = self::getPermissionGroups();

        // Newly added
        $this->assertContains('units', $groups);
        $this->assertContains('variants', $groups);
        $this->assertContains('barcode', $groups);
        $this->assertContains('payments', $groups);
        $this->assertContains('manufacturing', $groups);
        $this->assertContains('locations', $groups);
        $this->assertContains('activities', $groups);

        // Removed
        $this->assertNotContains('pos', $groups);
        $this->assertNotContains('grn', $groups);
        $this->assertNotContains('purchase_returns', $groups);
        $this->assertNotContains('branches', $groups);

        // Spot-check a permission name format
        $this->assertContains('barcode.generate', self::getAllDefinedPermissions());
        $this->assertContains('activities.delete', self::getAllDefinedPermissions());
    }
}
