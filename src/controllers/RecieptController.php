<?php

namespace Abs\ReceiptPkg;
use Abs\ReceiptPkg\Receipt;
use App\Http\Controllers\Controller;
use App\Config;
use Auth;
use Carbon\Carbon;
use DB;
use Entrust;
use Illuminate\Http\Request;
use Validator;
use Yajra\Datatables\Datatables;
use Session;

class ReceiptController extends Controller {

	public function __construct() {
		$this->data['theme'] = config('custom.admin_theme');
	}

	public function getReceiptSessionData(){
		$this->data['status'] = Config::select('id','name')->get();
		$this->data['search_invoice'] = Session::get('search_invoice');
		$this->data['account_name'] = Session::get('account_name');
		$this->data['account_code'] = Session::get('account_code');
		$this->data['invoice_date'] = Session::get('invoice_date');
		$this->data['invoice_number'] = Session::get('invoice_number');
		$this->data['config_status'] = Session::get('config_status');
		$this->data['success'] = true;
		return response()->json($this->data);
	}

	public function getReceiptList(Request $request) {
		Session::put('search_invoice',$request->search['value']);
		Session::put('account_name',$request->account_name);
		Session::put('account_code',$request->account_code);
		Session::put('invoice_date',$request->invoice_date);
		Session::put('invoice_number',$request->invoice_number);
		Session::put('config_status',$request->config_status);
		$start_date = '';
		$end_date = '';
		if(!empty($request->invoice_date)){
			$date_range = explode(' - ',$request->invoice_date);
			$start_date = $date_range[0];
			$end_date = $date_range[1];
		}
		
		$receipts = Receipt::select(
				DB::raw('IF(receipts.date IS NULL,"NA",DATE_FORMAT(receipts.date,"%d-%m-%Y")) as receipt_date'),
				'receipts.receipt_of_id',
				// 'customers.code as account_code',
				// 'customers.name as account_name',
				'receipts.id as id',
				DB::raw('IF(receipts.description IS NULL,"NA",receipts.description) as description'),
				DB::raw('IF(receipts.permanent_receipt_no IS NULL,"NA",receipts.permanent_receipt_no) as receipt_number'),
				DB::raw('IF(receipt_ofs.name IS NULL,"NA",receipt_ofs.name) as receipt_of_name'),
				// DB::raw('format(receipts.invoice_amount,0,"en_IN") as invoice_amount'),
				// DB::raw('format(receipts.received_amount,0,"en_IN") as received_amount'),
				// DB::raw('format((receipts.invoice_amount - receipts.received_amount),0,"en_IN") as balance_amount'),
				DB::raw('IF(configs.name IS NULL,"NA",configs.name) as status_name'),
				'receipts.permanent_receipt_no'
			)
			->leftJoin('configs as receipt_ofs','receipts.receipt_of_id','=','receipt_ofs.id')
			->leftJoin('configs','receipts.status_id','=','configs.id')
			//->leftJoin('customers','receipts.customer_id','=','customers.id')
			->where('receipts.company_id', Auth::user()->company_id)
			->where(function ($query) use ($request) {
				if (!empty($request->account_code)) {
					$query->where('customers.code', 'LIKE',$request->account_code);
				}
			})
			->where(function ($query) use ($request) {
				if (!empty($request->account_name)) {
					$query->where('customers.name', 'LIKE',$request->account_name);
				}
			})
			->where(function ($query) use ($request) {
				if (!empty($request->invoice_number)) {
					$query->where('receipts.invoice_number', 'LIKE',$request->invoice_number);
				}
			})
			->where(function ($query) use ($request) {
				if (!empty($request->config_status)) {
					$query->where('configs.id',$request->config_status);
				}
			})
			->where(function ($query) use ($request,$start_date,$end_date){
				if (!empty($request->invoice_date) && ($start_date && $end_date)) {
					$query->where('receipts.invoice_date','>=',$start_date)->where('receipts.invoice_date','<=',$end_date);
				}
			})
			
		;
		$datatable = Datatables::of($receipts)
			->addColumn('action', function ($receipts) {
				$img_delete = asset('public/themes/' . $this->data['theme'] . '/img/content/table/delete-default.svg');
				$img_delete_active = asset('public/themes/' . $this->data['theme'] . '/img/content/table/delete-active.svg');
				$view = asset('public/themes/' . $this->data['theme'] . '/img/content/table/eye.svg');
				$view_active = asset('public/themes/' . $this->data['theme'] . '/img/content/table/eye-active.svg');
				$output = '';
				//if (Entrust::can('view-invoice')) {
					$output .= '<a href="#!/receipt-pkg/invoice/view/' . $receipts->id . '" id = "" title="view"><img src="' . $view . '" alt="Edit" class="img-responsive" onmouseover=this.src="' . $view_active . '" onmouseout=this.src="' . $view . '"></a>';
				/*}
				if (Entrust::can('delete-invoice')) {*/
					$output .= '<a href="javascript:;" data-toggle="modal" data-target="#receipts-delete-modal" onclick="angular.element(this).scope().deleteReceipt(' . $receipts->id . ')" title="Delete"><img src="' . $img_delete . '" alt="Delete" class="img-responsive delete" onmouseover=this.src="' . $img_delete_active . '" onmouseout=this.src="' . $img_delete . '"></a>';
				/*}*/
				return $output;
			});
			// ->addColumn('account_name', function ($receipts) {
				
			// 	return $output;
			// });
			$datatable->make(true);
	}

	public function getReceiptViewData(Request $request) {
		$id = $request->id;
		if (!$id) {
			$this->data['message'] = 'Invalid Invoice';
			$this->data['success'] = false;
			return response()->json($this->data);
		} else {
			$this->data['invoice'] = $invoice = Invoice::select(
				DB::raw('DATE_FORMAT(receipts.invoice_date,"%d-%m-%Y") as invoice_date'),
				//'invoice_ofs.name as invoice_of_name',
				//'receipts.invoice_of_id',
				'receipts.invoice_number',
				'receipts.id as id',
				DB::raw('IF(receipts.remarks IS NULL,"NA",receipts.remarks) as description'),
				DB::raw('format(receipts.invoice_amount,0,"en_IN") as invoice_amount'),
				DB::raw('format(receipts.received_amount,0,"en_IN") as received_amount'),
				DB::raw('format((receipts.invoice_amount - receipts.received_amount),0,"en_IN") as balance_amount'),
				DB::raw('IF(configs.name IS NULL,"NA",configs.name) as status_name'),
				'customers.code as account_code',
				'customers.name as account_name',
				'receipts.invoice_number'
			)
			//->leftJoin('configs as invoice_ofs','receipts.invoice_of_id','=','invoice_ofs.id')
			->leftJoin('configs','receipts.status_id','=','configs.id')
			->leftJoin('customers','receipts.customer_id','=','customers.id')
			->where('receipts.company_id', /*Auth::user()->company_id*/2)
			->where('receipts.id',$request->id)
			->first();
			$this->data['transactions'] = DB::table('invoice_details')
				->where('invoice_id',$request->id)
				->leftJoin('configs','invoice_details.status_id','=','configs.id')
				//->leftJoin('configs as type','receipts.type_id','=','configs.id')
				->select(
					DB::raw('DATE_FORMAT(invoice_details.created_at,"%d-%m-%Y") as invoice_date'),
					DB::raw('IF(configs.name IS NULL,"NA",configs.name) as status_name'),
				DB::raw('format(invoice_details.received_amount,0,"en_IN") as received_amount'),
				DB::raw('format((invoice_details.invoice_amount - invoice_details.received_amount),0,"en_IN") as balance_amount')
					//,'type.name as type_name'
				)
			->get();
		}
			if(!$invoice){
				$this->data['message'] = 'Invoice Not Found!!';
				$this->data['success'] = false;
				return response()->json($this->data);
			}
			$this->data['success'] = true;
			return response()->json($this->data);
	}

	public function deleteReceiptData(Request $request) {
		DB::beginTransaction();
		try {
			$invoice = Invoice::where('id', $request->id)->delete();
			$invoice_details = DB::table('invoice_details')->where('invoice_id', $request->id)->delete();
			if ($invoice) {
				$activity = new ActivityLog;
				$activity->date_time = Carbon::now();
				$activity->user_id = Auth::user()->id;
				$activity->module = 'Invoice';
				$activity->entity_id = $request->id;
				$activity->entity_type_id = 1420;
				$activity->activity_id = 282;
				$activity->activity = 282;
				$activity->details = json_encode($activity);
				$activity->save();

				DB::commit();
				return response()->json(['success' => true, 'message' => 'Invoice Deleted Successfully']);
			}
		} catch (Exception $e) {
			DB::rollBack();
			return response()->json(['success' => false, 'errors' => ['Exception Error' => $e->getMessage()]]);
		}
	}
}
