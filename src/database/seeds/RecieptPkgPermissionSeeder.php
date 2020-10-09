<?php
namespace Abs\ReceiptPkg\Database\Seeds;

use App\Permission;
use Illuminate\Database\Seeder;

class ReceiptPkgPermissionSeeder extends Seeder {
	/**
	 * Run the database seeds.
	 *
	 * @return void
	 */
	public function run() {
		$permissions = [
			//Receipts
			[
				'display_order' => 99,
				'parent' => null,
				'name' => 'receipts-jva',
				'display_name' => 'Receipts-JVA',
			],
			[
				'display_order' => 1,
				'parent' => 'receipts-jva',
				'name' => 'add-receipt-jva',
				'display_name' => 'Add',
			],
			[
				'display_order' => 2,
				'parent' => 'receipts-jva',
				'name' => 'view-receipt-jva',
				'display_name' => 'View',
			],
			[
				'display_order' => 3,
				'parent' => 'receipts-jva',
				'name' => 'delete-receipt',
				'display_name' => 'Delete-receipt-jva',
			],

		];
		Permission::createFromArrays($permissions);
	}
}