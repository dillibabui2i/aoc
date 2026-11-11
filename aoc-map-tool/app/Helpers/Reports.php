<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use App\Helpers\H;


class Reports
{

    public static function formatCell($format = '', $value = '')
    {
        if($format=='hide'){
            $newValue = '';
        }elseif($format=='pct'){
            $newValue = $value . '%';
        } elseif($format == 'date') {
            $newValue = H::getFormatedDate($value);
        } elseif($format == '0decimal') {
            return (is_numeric($value)) ? number_format($value, 0, '.', ',') : $value;
        } elseif($format == '1decimal') {
            return (is_numeric($value)) ? number_format($value, (is_float($value) ? 1 : 0), '.', ',') : $value;
        } elseif($format == 'currency') {
            $tmp = (is_numeric($value)) ? number_format($value, 2, '.', ',') : $value;
            return '$' . $tmp;
        } else {
            $newValue = $value;
        }
        return (is_numeric($newValue)) ? number_format($newValue, (is_float($newValue) ? 2 : 0), '.', ',') : $newValue;
    }

    public static function formatCell2($format = '', $value = '')
    {
        if($format=='hide'){
            $newValue = '';
        }elseif($format=='pct'){
            $newValue = $value . '%';
        } elseif($format == 'date') {
            $newValue = H::getFormatedDate($value);
        } elseif($format == 'roundPct') {
			return ((is_numeric($value)) ? number_format($value, 0, '.', ',') : $value) . '%';
		}elseif($format == '0decimal') {
            return (is_numeric($value)) ? number_format($value, 0, '.', ',') : $value;
        } elseif($format == '1decimal') {
            return (is_numeric($value)) ? number_format($value, 0, '.', ',') : $value;
        } elseif($format == 'currency') {
            $tmp = (is_numeric($value)) ? number_format($value, 2, '.', ',') : $value;
            return '$' . $tmp;
        } else {
            $newValue = $value;
        }
        return (is_numeric($newValue)) ? number_format($newValue, 0, '.', ',') : $newValue;
    }

    public static function formatGroupTitle($groupId, $group, $lastValue)
    {
        $newLabel = str_replace('@group4',$lastValue[4],str_replace('@group3',$lastValue[3],str_replace('@group2',$lastValue[2],str_replace('@group1',$lastValue[1],$group['label']))));
        if($group['field']=='state'){
            $dashboardState= config('constant.dashboard_state');
            $newLabel1 = array_search($newLabel, $dashboardState);
            if ($newLabel1 !== false) {
                $newLabel = $newLabel1;
            }
        }

        return $newLabel;
    }

}
