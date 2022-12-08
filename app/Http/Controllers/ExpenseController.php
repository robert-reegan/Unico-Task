<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Expense;
use App\Models\ExpenseDetails;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ExpenseController extends Controller
{

    public function expense_details(Request $request)
    {

        $participants_pending_amount = $this->get_pending_amount($request->user_id);

        $details = Expense::with('users')->where('user_id', '=', $request->user_id)->get();

        $expense_summary_details = $this->get_expense_summary($details);

        $data = [];
        $data['Expense_Summary'] = $expense_summary_details;
        $data['Participants_Pending_Amount'] = $participants_pending_amount;

        return response()->json($data, 200)->setEncodingOptions(JSON_PRETTY_PRINT);
    }

    public function get_expense_summary($details)
    {
        $expense_del = [];
        foreach ($details as $k => $detail) {
            $expense_del[$k]['name'] = $detail->name;
            $total = 0;
            foreach ($detail->users as $key => $user) {
                $expense_del[$k]['participants'][$key]['name'] = isset($user->name) ? $user->name : '';
                $data = [];
                $data['participants_id'] = isset($user->pivot->participants_id) ? $user->pivot->participants_id : '';
                $data['expense_id'] = isset($user->pivot->expense_id) ? $user->pivot->expense_id : '';
                $participants_amount = ExpenseDetails::where('participants_id', '=', $user->pivot->participants_id)->where('expense_id', '=', $user->pivot->expense_id)->first();
                $expense_del[$k]['participants'][$key]['amount'] = isset($participants_amount['amount']) ? $participants_amount['amount'] : '';

                $total += isset($participants_amount['amount']) ? $participants_amount['amount'] : 0;
            }
            $expense_del[$k]['total'] = $total;
        }
        return $expense_del;
    }

    public function get_pending_amount($user_id)
    {

        $user_details = User::with('expenses')->where('id', '=', $user_id)->first()->toArray();

        $expense_ids = Expense::select('id')->where('user_id', '=', $user_id)->get()->toArray();
        $ids = [];
        foreach ($expense_ids as $values) {
            array_push($ids, $values['id']);
        }

        $user_info = ExpenseDetails::select(DB::raw('sum(amount) as total ,participants_id'))
            ->whereIn('expense_id', $ids)
            ->groupBy('participants_id')
            ->get()->toArray();

        $participants_id = [];
        foreach ($user_info as $list) {
            array_push($participants_id, $list['participants_id']);
        }

        foreach ($user_info as $k => $val) {
            unset($user_info[$k]['participants_id']);

            if ($val['participants_id'] != $user_details['id']) {

                // $total = $this->get_total_amount($val['participants_id'], $user_id);
                // $user_id = User::where('id', $val['participants_id'])->first()->toArray();
                // $user_info[$k]['name'] =  $user_id['name'];
                // $user_info[$k]['total'] =  $val['total'] - $total;

                $get_expense_id = Expense::where('user_id', $val['participants_id'])->get()->toArray();
                $user_data = User::where('id', $val['participants_id'])->first()->toArray();
                $user_info[$k]['name'] =  $user_data['name'];
                if (count($get_expense_id) > 0) {
                    foreach ($get_expense_id as $eval) {
                        $get_users_total = ExpenseDetails::select('amount', 'participants_id')->where('participants_id', '=', $user_id)->where('expense_id', '=', $eval['id'])->get()->toArray();
                        if (count($get_users_total) > 0) {
                            if (isset($eval['amount'])) {
                                $user_info[$k]['total'] =  $val['total'] - $get_users_total[0]['amount'];
                            }
                        }
                    }
                }
            } else {
                unset($user_info[$k]);
            }
        }
        array_unshift($user_info);
        return $user_info;
    }

    public function get_total_amount($participants_id, $user_id)
    {
        $get_expense_id = Expense::where('user_id', $participants_id)->get()->toArray();
        $total_amount = 0;
        if (count($get_expense_id) > 0) {
            foreach ($get_expense_id as $eval) {
                $get_users_total = ExpenseDetails::select('amount', 'participants_id')->where('participants_id', '=', $user_id)->where('expense_id', '=', $eval['id'])->get()->toArray();
                if (count($get_users_total) > 0) {
                    $total_amount += $get_users_total[0]['amount'];
                }
            }
        }
        return $total_amount;
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'type' => 'required',
            'amount' => 'required|digits_between:2,5',
        ]);
        if ($validator->fails()) {
            return response()->json(['message' => 'validation errors'], 400);
        }

        $data = [];
        $data['name'] = $request->post('name');
        $data['type'] = $request->post('type');
        $data['amount'] = $request->post('amount');
        $data['user_id'] = Auth::user()->id;
        $status = Expense::storeData($data);

        if ($status['is_success']) {

            $last_inserted_id = $status['data']['id'];
            if (isset($request->participants) && $request->participants != "") {
                $participants = json_decode($request->post('participants'), true);
                if (count($participants) > 0) {

                    foreach ($participants as $participant) {

                        if ($request->post('type') == 1) {
                            $amount = $request->post('amount') / count($participants);
                        } else if ($request->post('type') == 3) {
                            $amount = ($request->post('amount') * $participant['percent']) / 100;
                        } else {
                            $amount = $participant['amount'];
                        }
                        $data = [];
                        $data['expense_id'] = $last_inserted_id;
                        $data['participants_id'] = $participant['user_id'];
                        $data['amount'] = $amount;
                        $data['status'] = 1;
                        $status = ExpenseDetails::storeData($data);
                    }
                }
            }
            $data = [
                'message' => 'Task successfully added',
                'data' => (object) [],
                'error' => (object) [],

            ];
            return response()->json($data, 200)->setEncodingOptions(JSON_PRETTY_PRINT);
        } else {

            $data = [
                'message' => 'Something went wrong',
                'data' => (object) [],
                'error' => (object) [],

            ];
            return response()->json($data, 400)->setEncodingOptions(JSON_PRETTY_PRINT);
        }
    }
}
