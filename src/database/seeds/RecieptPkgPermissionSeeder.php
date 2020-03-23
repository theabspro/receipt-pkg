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
				'name' => 'receipts',
				'display_name' => 'Receipts',
			],
			[
				'display_order' => 1,
				'parent' => 'receipts',
				'name' => 'add-receipt',
				'display_name' => 'Add',
			],
			[
				'display_order' => 2,
				'parent' => 'receipts',
				'name' => 'edit-receipt',
				'display_name' => 'Edit',
			],
			[
				'display_order' => 3,
				'parent' => 'receipts',
				'name' => 'delete-receipt',
				'display_name' => 'Delete',
			],

		];
		Permission::createFromArrays($permissions);
	}
}