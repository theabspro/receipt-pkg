<?php

namespace Abs\ReceiptPkg;
use Abs\ReceiptPkg\Receipt;
use App\Http\Controllers\Controller;
use Auth;
use Carbon\Carbon;
use DB;
use Entrust;
use Illuminate\Http\Request;
use Validator;
use Yajra\Datatables\Datatables;

class ReceiptController extends Controller {

	public function __construct() {
		$this->data['theme'] = config('custom.admin_theme');
	}

	public function getReceiptList(Request $request) {
		$receipts = Receipt::withTrashed()
			->select([
				'receipts.id',
				'receipts.name',
				DB::raw('COALESCE(receipts.description,"--") as description'),
				DB::raw('IF(receipts.deleted_at IS NULL, "Active","Inactive") as status'),
			])
			->where('receipts.company_id', Auth::user()->company_id)
			->where(function ($query) use ($request) {
				if (!empty($request->name)) {
					$query->where('receipts.name', 'LIKE', '%' . $request->name . '%');
				}
			})
			->where(function ($query) use ($request) {
				if ($request->status == '1') {
					$query->whereNull('receipts.deleted_at');
				} else if ($request->status == '0') {
					$query->whereNotNull('receipts.deleted_at');
				}
			})
			->orderby('receipts.id', 'Desc');

		return Datatables::of($receipts)
			->addColumn('name', function ($receipt) {
				$status = $receipt->status == 'Active' ? 'green' : 'red';
				return '<span class="status-indicator ' . $status . '"></span>' . $receipt->name;
			})
			->addColumn('action', function ($receipt) {
				$img1 = asset('public/themes/' . $this->data['theme'] . '/img/content/table/edit-yellow.svg');
				$img1_active = asset('public/themes/' . $this->data['theme'] . '/img/content/table/edit-yellow-active.svg');
				$img_delete = asset('public/themes/' . $this->data['theme'] . '/img/content/table/delete-default.svg');
				$img_delete_active = asset('public/themes/' . $this->data['theme'] . '/img/content/table/delete-active.svg');
				$output = '';
				if (Entrust::can('edit-receipt')) {
					$output .= '<a href="#!/receipt-pkg/receipt/edit/' . $receipt->id . '" id = "" title="Edit"><img src="' . $img1 . '" alt="Edit" class="img-responsive" onmouseover=this.src="' . $img1_active . '" onmouseout=this.src="' . $img1 . '"></a>';
				}
				if (Entrust::can('delete-receipt')) {
					$output .= '<a href="javascript:;" data-toggle="modal" data-target="#receipt-delete-modal" onclick="angular.element(this).scope().deleteReceipt(' . $receipt->id . ')" title="Delete"><img src="' . $img_delete . '" alt="Delete" class="img-responsive delete" onmouseover=this.src="' . $img_delete_active . '" onmouseout=this.src="' . $img_delete . '"></a>';
				}
				return $output;
			})
			->make(true);
	}

	public function getReceiptFormData(Request $request) {
		$id = $request->id;
		if (!$id) {
			$receipt = new Receipt;
			$action = 'Add';
		} else {
			$receipt = Receipt::withTrashed()->find($id);
			$action = 'Edit';
		}
		$this->data['receipt'] = $receipt;
		$this->data['action'] = $action;
		$this->data['theme'];

		return response()->json($this->data);
	}

	public function saveReceipt(Request $request) {
		// dd($request->all());
		try {
			$error_messages = [
				'name.required' => 'Name is Required',
				'name.unique' => 'Name is already taken',
				'name.min' => 'Name is Minimum 3 Charachers',
				'name.max' => 'Name is Maximum 64 Charachers',
				'description.max' => 'Description is Maximum 255 Charachers',
			];
			$validator = Validator::make($request->all(), [
				'name' => [
					'required:true',
					'min:3',
					'max:64',
					'unique:receipts,name,' . $request->id . ',id,company_id,' . Auth::user()->company_id,
				],
				'description' => 'nullable|max:255',
			], $error_messages);
			if ($validator->fails()) {
				return response()->json(['success' => false, 'errors' => $validator->errors()->all()]);
			}

			DB::beginTransaction();
			if (!$request->id) {
				$receipt = new Receipt;
				$receipt->created_by_id = Auth::user()->id;
				$receipt->created_at = Carbon::now();
				$receipt->updated_at = NULL;
			} else {
				$receipt = Receipt::withTrashed()->find($request->id);
				$receipt->updated_by_id = Auth::user()->id;
				$receipt->updated_at = Carbon::now();
			}
			$receipt->fill($request->all());
			$receipt->company_id = Auth::user()->company_id;
			if ($request->status == 'Inactive') {
				$receipt->deleted_at = Carbon::now();
				$receipt->deleted_by_id = Auth::user()->id;
			} else {
				$receipt->deleted_by_id = NULL;
				$receipt->deleted_at = NULL;
			}
			$receipt->save();

			DB::commit();
			if (!($request->id)) {
				return response()->json([
					'success' => true,
					'message' => 'Receipt Added Successfully',
				]);
			} else {
				return response()->json([
					'success' => true,
					'message' => 'Receipt Updated Successfully',
				]);
			}
		} catch (Exceprion $e) {
			DB::rollBack();
			return response()->json([
				'success' => false,
				'error' => $e->getMessage(),
			]);
		}
	}

	public function deleteReceipt(Request $request) {
		DB::beginTransaction();
		try {
			$receipt = Receipt::withTrashed()->where('id', $request->id)->forceDelete();
			if ($receipt) {
				DB::commit();
				return response()->json(['success' => true, 'message' => 'Receipt Deleted Successfully']);
			}
		} catch (Exception $e) {
			DB::rollBack();
			return response()->json(['success' => false, 'errors' => ['Exception Error' => $e->getMessage()]]);
		}
	}
}
