<?php

Route::group(['namespace' => 'Abs\ReceiptPkg', 'middleware' => ['web', 'auth'], 'prefix' => 'receipt-pkg'], function () {
	//RECEIPTS
	Route::get('/receipts/get-list', 'ReceiptController@getReceiptList')->name('getReceiptList');
	Route::get('/receipt/get-view-data', 'ReceiptController@getReceiptViewData')->name('getReceiptViewData');
	Route::get('/receipt/get-session-data', 'ReceiptController@getReceiptSessionData')->name('getReceiptSessionData');
	Route::get('/receipt/delete', 'ReceiptController@deleteReceiptData')->name('deleteInvoiceData');
});