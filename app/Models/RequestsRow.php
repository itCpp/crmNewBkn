<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
// use Illuminate\Support\Facades\Schema;

class RequestsRow extends Model
{
    use HasFactory;

    /**
     * Вывод информации по колонкам
     * 
     * @return array
     */
    public static function getColumnsList() {

        $model = with(new static);
    
        return \DB::table('INFORMATION_SCHEMA.COLUMNS')
        ->select(
            'COLUMN_NAME as name',
            'COLUMN_TYPE as type',
            'COLUMN_COMMENT as comment'
        )
        ->where('table_name', $model->getTable())
        ->get();

    }

}
