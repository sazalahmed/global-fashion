<?php

namespace Modules\Accounting\Tests\Unit;

use Modules\Accounting\Models\Account;
use Modules\Accounting\Services\ChartOfAccountsService;
use Tests\TestCase;

class ChartOfAccountsServiceTest extends TestCase
{
    protected ChartOfAccountsService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ChartOfAccountsService::class);
        $this->actingAs($this->admin);
    }

    public function test_list_accounts(): void
    {
        $results = $this->service->list();
        $this->assertGreaterThan(0, $results->total());
    }

    public function test_list_with_type_filter(): void
    {
        $results = $this->service->list(['account_type' => 'asset']);
        foreach ($results as $account) {
            $this->assertEquals('asset', $account->account_type);
        }
    }

    public function test_create_account(): void
    {
        $account = $this->service->create([
            'account_code' => '9999',
            'account_name' => 'Test Account',
            'account_type' => 'expense',
            'sub_type' => 'operating_expense',
            'status' => 'active',
        ]);

        $this->assertInstanceOf(Account::class, $account);
        $this->assertEquals('Test Account', $account->account_name);
        $this->assertDatabaseHas('accounts', ['account_code' => '9999']);
    }

    public function test_update_account(): void
    {
        $account = $this->service->create([
            'account_code' => '9998',
            'account_name' => 'Original Name',
            'account_type' => 'expense',
            'sub_type' => 'operating_expense',
            'status' => 'active',
        ]);

        $updated = $this->service->update($account, ['account_name' => 'Updated Name']);
        $this->assertEquals('Updated Name', $updated->account_name);
    }

    public function test_delete_non_system_account(): void
    {
        $account = $this->service->create([
            'account_code' => '9997',
            'account_name' => 'Deletable',
            'account_type' => 'expense',
            'sub_type' => 'other_expense',
            'status' => 'active',
            'is_system' => false,
        ]);

        $result = $this->service->delete($account);
        $this->assertTrue($result);
    }

    public function test_delete_system_account_throws(): void
    {
        $account = Account::where('is_system', true)->first();
        if (!$account) {
            $this->markTestSkipped('No system account found');
        }

        $this->expectException(\RuntimeException::class);
        $this->service->delete($account);
    }

    public function test_delete_account_with_children_throws(): void
    {
        $parent = $this->service->create([
            'account_code' => '9990',
            'account_name' => 'Parent Account',
            'account_type' => 'asset',
            'sub_type' => 'current_asset',
            'status' => 'active',
        ]);
        $this->service->create([
            'account_code' => '9991',
            'account_name' => 'Child Account',
            'account_type' => 'asset',
            'sub_type' => 'current_asset',
            'parent_id' => $parent->id,
            'status' => 'active',
        ]);

        $this->expectException(\RuntimeException::class);
        $this->service->delete($parent);
    }

    public function test_compute_balance_as_of(): void
    {
        $account = Account::where('account_code', '1001')->firstOrFail();
        $balance = $this->service->computeBalanceAsOf($account);
        $this->assertIsFloat($balance);
    }

    public function test_find_account(): void
    {
        $account = Account::where('account_code', '1001')->firstOrFail();
        $found = $this->service->find($account->id);
        $this->assertEquals($account->id, $found->id);
    }
}
