@if(config('receipt-pkg.DEV'))
    <?php $receipt_pkg_prefix = '/packages/abs/receipt-pkg/src';?>
@else
    <?php $receipt_pkg_prefix = '';?>
@endif

<script type="text/javascript">
	app.config(['$routeProvider', function($routeProvider) {

	    $routeProvider.
	    //RECEIPTS
	    when('/receipt-pkg/receipt/list', {
	        template: '<receipt-list></receipt-list>',
	        title: 'Receipts',
	    }).
	    when('/receipt-pkg/receipt/add', {
	        template: '<receipt-form></receipt-form>',
	        title: 'Add Receipt',
	    }).
	    when('/receipt-pkg/receipt/edit/:id', {
	        template: '<receipt-form></receipt-form>',
	        title: 'Edit Receipt',
	    });
	}]);

    //JOURNAL VOUCHER
    var receipt_list_template_url = "{{asset($receipt_pkg_prefix.'/public/themes/'.$theme.'/receipt-pkg/receipt/list.html')}}";
    var receipt_form_template_url = "{{asset($receipt_pkg_prefix.'/public/themes/'.$theme.'/receipt-pkg/receipt/form.html')}}";

</script>
<script type="text/javascript" src="{{asset($receipt_pkg_prefix.'/public/themes/'.$theme.'/receipt-pkg/receipt/controller.js')}}"></script>
