<?php

Route::group(['namespace' => 'Abs\ReceiptPkg', 'middleware' => ['web', 'auth'], 'prefix' => 'receipt-pkg'], function () {
	//RECEIPTS
	Route::get('/receipts/get-list', 'ReceiptController@getReceiptList')->name('getReceiptList');
	Route::get('/receipt/get-form-data', 'ReceiptController@getReceiptFormData')->name('getReceiptFormData');
	Route::post('/receipt/save', 'ReceiptController@saveReceipt')->name('saveReceipt');
	Route::get('/receipt/delete', 'ReceiptController@deleteReceipt')->name('deleteReceipt');
});