<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{

    protected $table = 'expenses';
    protected $fillable = ['name', 'type', 'amount', 'user_id'];

    public function users()
    {
        return $this->belongsToMany(User::class, ExpenseDetails::class, 'expense_id', 'participants_id');
    }

    public static function storeData(array $data): array
    {
        $possibleFieldsToInsert = [
            'name', 'type', 'amount', 'user_id'
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
