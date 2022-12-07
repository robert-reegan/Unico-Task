<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExpenseDetails extends Model
{

    protected $table = 'expense_details';
    protected $fillable = ['expense_id', 'participants_id', 'amount', 'status'];


    public static function filter(array $filter = [])
    {
        $q = new ExpenseDetails;

        if (isset($filter['participants_id']) && $filter['participants_id'] != "") {
            $q->where('participants_id', '=', $filter['participants_id']);
        }
        if (isset($filter['expense_id']) && $filter['expense_id'] != "") {
            $q->where('expense_id', '=', $filter['expense_id']);
        }
        return $q->orderBy('created_at', 'desc')->get()->toArray();
    }

    public static function storeData(array $data): array
    {
        $possibleFieldsToInsert = [
            'expense_id', 'participants_id', 'amount', 'status'
        ];

        $model = new self();
        foreach ($data as $column => $value) {
            if (in_array($column, $possibleFieldsToInsert)) {
                $model->$column = $value;
            }
        }

        return $model->save()
            ? [
                'is_success' => true,
                'data' => [
                    'id' => $model->id
                ]
            ]
            : [
                'is_success' => false
            ];
    }
}
