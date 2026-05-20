<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string strProductName
 * @property string strProductDesc
 * @property string strProductCode
 * @property Carbon dtmAdded
 * @property Carbon dtmDiscontinued
 * @property string dcmCostInGbp
 * @property integer intStock
 */
class Product extends Model
{
    protected $table = 'tblProductData';
    public $timestamps = false;

    protected $fillable = [
        'strProductName',
        'strProductDesc',
        'strProductCode',
        'dtmAdded',
        'dtmDiscontinued',
        'dcmCostInGbp',
        'intStock'
    ];
}
