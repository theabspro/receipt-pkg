@if(config('receipt-pkg.DEV'))
    <?php $receipt_pkg_prefix = '/packages/abs/receipt-pkg/src';?>
@else
    <?php $receipt_pkg_prefix = '';?>
@endif

<script type="text/javascript">
    var receipt_list_template_url = "{{asset($receipt_pkg_prefix.'/public/themes/'.$theme.'/receipt-pkg/receipt/receipts.html')}}";
</script>
<script type="text/javascript" src="{{asset($receipt_pkg_prefix.'/public/themes/'.$theme.'/receipt-pkg/receipt/controller.js')}}"></script>
