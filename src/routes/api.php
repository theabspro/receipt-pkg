<?php
Route::group(['namespace' => 'Abs\ReceiptPkg\Api', 'middleware' => ['api']], function () {
	Route::group(['prefix' => 'receipt-pkg/api'], function () {
		Route::group(['middleware' => ['auth:api']], function () {
			// Route::get('taxes/get', 'TaxController@getTaxes');
		});
	});
});