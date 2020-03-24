<?php

namespace Abs\ReceiptPkg;
use Abs\ReceiptPkg\Receipt;
use App\ActivityLog;
use App\Config;
use App\Customer;
use App\Http\Controllers\Controller;
use App\Vendor;
use Auth;
use Carbon\Carbon;
use DB;
use Entrust;
use Illuminate\Http\Request;
use Session;
use Yajra\Datatables\Datatables;

class ReceiptController extends Controller {

	public function __construct() {
		$this->data['theme'] = config('custom.admin_theme');
	}

	public function getReceiptSessionData() {

		$this->data['status'] = Config::select('id','name')->get()->prepend(['id' => '','name' => 'Select Status']);
		$this->data['receipt_of'] = Config::select('id','name')->where('config_type_id',30)->get()->prepend(['id' => '','name' => 'Select Receipt Of']);

		$this->data['search_invoice'] = Session::get('search_invoice');
		$this->data['account_name'] = Session::get('account_name');
		$this->data['account_code'] = Session::get('account_code');
		$this->data['receipt_date'] = Session::get('receipt_date');
		$this->data['receipt_number'] = Session::get('receipt_number');
		$this->data['config_status'] = Session::get('config_status');
		$this->data['success'] = true;
		return response()->json($this->data);
	}

	public function getReceiptList(Request $request) {
		Session::put('search_invoice', $request->search['value']);
		Session::put('account_name', $request->account_name);
		Session::put('account_code', $request->account_code);
		Session::put('receipt_date', $request->receipt_date);
		Session::put('receipt_number', $request->receipt_number);
		Session::put('config_status', $request->config_status);
		$start_date = '';
		$end_date = '';
		if (!empty($request->receipt_date)) {
			$date_range = explode(' - ', $request->receipt_date);
			$start_date = $date_range[0];
			$end_date = $date_range[1];
		}
		$receipts = Receipt::withTrashed()->select(
				DB::raw('IF(receipts.date IS NULL,"NA",DATE_FORMAT(receipts.date,"%d-%m-%Y")) as receipt_date'),
				'receipts.receipt_of_id',
				'receipts.entity_id as entity_id',
				'receipts.id as id',
				DB::raw('IF(receipts.description IS NULL,"NA",receipts.description) as description'),
				DB::raw('IF(receipts.permanent_receipt_no IS NULL,"NA",receipts.permanent_receipt_no) as receipt_number'),
				DB::raw('IF(receipt_ofs.name IS NULL,"NA",receipt_ofs.name) as receipt_of_name'),
				DB::raw('format(receipts.amount,0,"en_IN") as amount'),
				DB::raw('format(receipts.settled_amount,0,"en_IN") as settled_amount'),
				DB::raw('format(receipts.balance_amount,0,"en_IN") as balance_amount'),
				DB::raw('IF(configs.name IS NULL,"NA",configs.name) as status_name'),
				'receipts.permanent_receipt_no',
				'receipts.deleted_at'
			)
			->leftJoin('configs as receipt_ofs','receipts.receipt_of_id','=','receipt_ofs.id')
			->leftJoin('configs','receipts.status_id','=','configs.id')
			->leftJoin('customers','receipts.entity_id','=','customers.id')
			->leftJoin('vendors','receipts.entity_id','=','vendors.id')
			->where('receipts.company_id', Auth::user()->company_id)

			->where(function ($query) use ($request) {
				if (!empty($request->receipt_number)) {
					$query->where('receipts.permanent_receipt_no', 'LIKE', $request->receipt_number);
				}
			})
			->where(function ($query) use ($request, $start_date, $end_date) {
				if (!empty($request->receipt_date) && ($start_date && $end_date)) {
					$query->where('receipts.date', '>=', $start_date)->where('receipts.date', '<=', $end_date);
				}
			});
			if($request->receipt_of_id){
				$receipts = $receipts->where('receipts.receipt_of_id',$request->receipt_of_id);
			}
			if($request->receipt_of_id==7620 && $request->account_code){
				$receipts = $receipts->where('customers.code','LIKE',$request->account_code);
			}
			if($request->receipt_of_id==7620 &&$request->account_name){
				$receipts = $receipts->where('customers.name','LIKE',$request->account_name);
			}
			if($request->account_code && $request->receipt_of_id==7621){
				$receipts = $receipts->where('vendors.code','LIKE',$request->account_code);
			}
			if($request->account_name && $request->receipt_of_id==7621){
				$receipts = $receipts->where('vendors.name','LIKE',$request->account_name);
			}
		return Datatables::of($receipts)
		->addColumn('action', function ($receipts) {
			$img_delete = asset('public/themes/' . $this->data['theme'] . '/img/content/table/delete-default.svg');
			$img_delete_active = asset('public/themes/' . $this->data['theme'] . '/img/content/table/delete-active.svg');
			$view = asset('public/themes/' . $this->data['theme'] . '/img/content/table/eye.svg');
			$view_active = asset('public/themes/' . $this->data['theme'] . '/img/content/table/eye-active.svg');
			$output = '';
			if (Entrust::can('receipts')) {
				$output .= '<a href="#!/receipt-pkg/receipt/view/' . $receipts->id . '" id = "" title="view"><img src="' . $view . '" alt="Edit" class="img-responsive" onmouseover=this.src="' . $view_active . '" onmouseout=this.src="' . $view . '"></a>';
			}
			if (Entrust::can('delete-receipt')) {
				$output .= '<a href="javascript:;" data-toggle="modal" data-target="#receipts-delete-modal" onclick="angular.element(this).scope().deleteReceipt(' . $receipts->id . ')" title="Delete"><img src="' . $img_delete . '" alt="Delete" class="img-responsive delete" onmouseover=this.src="' . $img_delete_active . '" onmouseout=this.src="' . $img_delete . '"></a>';
			}
			return $output;
		})
		
		->addColumn('account_name', function ($receipts){
			$account_data='';
			if($receipts->receipt_of_id == 7620){
				$account_data = Customer::where('id',$receipts->entity_id)->first();
			}elseif($receipts->receipt_of_id == 7621){
				$account_data = Vendor::where('id',$receipts->entity_id)->first();
			}
			return $account_data ? $account_data->name : '-';
		})
		->addColumn('account_code', function ($receipts){
			$status = is_null($receipts->deleted_at) ? 'green' : 'red';
			$account_data='';
			if($receipts->receipt_of_id == 7620){
				$account_data = Customer::where('id',$receipts->entity_id)->first();
			}elseif($receipts->receipt_of_id == 7621){
				$account_data = Vendor::where('id',$receipts->entity_id)->first();
			}
			return $account_data ? '<span class="status-indicator ' . $status . '"></span>' . $account_data->code : '<span class="status-indicator ' . $status . '"></span>' . 'NA';
		})
		->make(true);

	}

	public function getReceiptViewData(Request $request) {
		$id = $request->id;
		if (!$id) {
			$this->data['message'] = 'Invalid Receipt';
			$this->data['success'] = false;
			return response()->json($this->data);
		} else {
			$receipt = Receipt::withTrashed()->select(
				DB::raw('IF(receipts.date IS NULL,"NA",DATE_FORMAT(receipts.date,"%d-%m-%Y")) as receipt_date'),
				'receipts.receipt_of_id',
				// 'customers.code as account_code',
				// 'customers.name as account_name',
				'receipts.id as id',
				'receipts.entity_id as entity_id',
				DB::raw('IF(receipts.description IS NULL,"NA",receipts.description) as description'),
				DB::raw('IF(receipts.permanent_receipt_no IS NULL,"NA",receipts.permanent_receipt_no) as receipt_number'),
				DB::raw('IF(receipt_ofs.name IS NULL,"NA",receipt_ofs.name) as receipt_of_name'),
				DB::raw('format(receipts.amount,0,"en_IN") as amount'),
				DB::raw('format(receipts.settled_amount,0,"en_IN") as settled_amount'),
				DB::raw('format(receipts.balance_amount,0,"en_IN") as balance_amount'),
				DB::raw('IF(configs.name IS NULL,"NA",configs.name) as status_name'),
				'receipts.permanent_receipt_no'
			)
				->leftJoin('configs as receipt_ofs', 'receipts.receipt_of_id', '=', 'receipt_ofs.id')
				->leftJoin('configs', 'receipts.status_id', '=', 'configs.id')
			//->leftJoin('customers','receipts.customer_id','=','customers.id')
				->where('receipts.company_id', Auth::user()->company_id)
				->where('receipts.id', $request->id)
				->first();
			$account_data = '';
			if ($receipt->receipt_of_id == 7620) {
				$account_data = Customer::where('id', $receipt->entity_id)->first();
			} elseif ($receipt->receipt_of_id == 7621) {
				$account_data = Vendor::where('id', $receipt->entity_id)->first();
			}
			$receipt->account_code = $account_data ? $account_data->code : '-';
			$receipt->account_name = $account_data ? $account_data->name : '-';
			$this->data['receipt'] = $receipt;
			// $this->data['transactions'] = DB::table('invoice_details')
			// 	->where('invoice_id',$request->id)
			// 	->leftJoin('configs','invoice_details.status_id','=','configs.id')
			// 	//->leftJoin('configs as type','receipts.type_id','=','configs.id')
			// 	->select(
			// 		DB::raw('DATE_FORMAT(invoice_details.created_at,"%d-%m-%Y") as invoice_date'),
			// 		DB::raw('IF(configs.name IS NULL,"NA",configs.name) as status_name'),
			// 	DB::raw('format(invoice_details.received_amount,0,"en_IN") as received_amount'),
			// 	DB::raw('format((invoice_details.invoice_amount - invoice_details.received_amount),0,"en_IN") as balance_amount')
			// 	)
			// ->get();
		}
		if (!$receipt) {
			$this->data['message'] = 'Receipt Not Found!!';
			$this->data['success'] = false;
			return response()->json($this->data);
		}
		$this->data['success'] = true;
		return response()->json($this->data);
	}

	public function deleteReceiptData(Request $request) {
		DB::beginTransaction();
		try {
			$invoice = Receipt::where('id', $request->id)->delete();
			//$invoice_details = DB::table('invoice_details')->where('invoice_id', $request->id)->delete();
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
