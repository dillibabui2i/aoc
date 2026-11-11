<?php

use App\Contracts\NationalRecruitingReport\ReportTabInterface;
use App\Helpers\H;
use App\Helpers\Reports;
use App\Helpers\LD;

$maxLevel = 0;
$groupLevelName = [];
$lastValue = [];
$currentValue = [];
foreach ($report['groupby'] as $group) {
    $lastValue[$group['id']] = '';
    $currentValue[$group['id']] = '';
    if (!empty($group['showCols']) && $group['level'] > $maxLevel) {
        $maxLevel = $group['level'];
        $key = array_search($group['field'], array_column($report['columns'], 'field'));
        $groupLevelName[$group['level']] = $report['columns'][$key]['heading']['title'];
    }
}
$states = [];
$tmp = H::getHelper("ConstantHelper")->getDashboardState();
foreach ($tmp as $key => $value) {
    $states[] = $value['value'];
}
$subtotals = [];
$rowCount = [];
$sixWeekCount = [];
$sixWeekTotals = [];
$thirteenWeekCount = [];
$thirteenWeekTotals = [];
foreach ($report['groupby'] as $group) {
    foreach ($report['columns'] as $column) {
        if ($column['totals']['formula'] != '') {
            $subtotals[$group['id']][$column['field']] = 0;
            $rowCount[$group['id']][$column['field']] = 0;
        }
    }
    $sixWeekCount[$group['id']] = 0;
    $sixWeekTotals[$group['id']] = 0;
    $thirteenWeekCount[$group['id']] = 0;
    $thirteenWeekTotals[$group['id']] = 0;
}
$groupCount = 0; //counting the groups within each state
$firstRowDone = 0; //set this to 1 after we load the first row and set $lastValue for grouping
$output = [];
$output[1] = '';
$output[2] = '';
$output[3] = '';
$output[4] = '';
$output[9] = '';
$finalOutput = '';

$count1 = $count2 = $count3 = $count4 = $count9 = 0;
?>
@extends('layouts.myaccount')
@section('title', 'Recruiting Report')
@section('content')

    <!-- Begin Page Content -->
    <div class="container-fluid nrrreport-main report-sticky-compact">

        <!-- Table -->
        <div>
            @if ($message = Session::get('success111'))
                <div class="alert alert-success alert-block">
                    <button type="button" class="close" data-dismiss="alert">×</button>
                    <strong>{{ $message }}</strong>
                </div>
            @endif
            <div class="card-body">
                @php
                    $types = [
                        'candidates' => 'Candidates',
                        'IPEAs' => 'IPEAs',
                        'PEPIs' => 'PEPIs',
                        'WEPIs' => 'WEPIs',
                        'candidateMatch' => 'CM',
                        'hoursRemoved' => 'Hours Removed',
                        'candidateStarts' => 'Candidate Starts',
                        'grossMargin' => 'Gross Margin'
                    ];
                @endphp
                <div class="top-sticky-container">

                    <h2 class="h3 form-group text-gray-800">National Recruiting Report</h2>

                    <div id="tabs" class="ui-tabs ui-corner-all ui-widget ui-widget-content">
                        <ul class="ui-tabs-nav ui-corner-all ui-helper-reset ui-helper-clearfix ui-widget-header">
                            @foreach ($types as $id => $type)
                                <li class="ui-tabs-tab ui-corner-top ui-state-default ui-tab @php if(Route::currentRouteName() == $id): @endphp ui-tabs-active ui-state-active@php endif; @endphp">
                                    <a href="{{route($id, ['tab' => $id])}}"
                                       class="ui-tabs-anchor">@php echo ucfirst($type); @endphp</a></li>
                            @endforeach
                        </ul>
                    </div>
                    <div class="filters-common">
                        @if (LD::checkFeatureFlag('save-report-filters-to-user-table') && !LD::checkFeatureFlag('nrr-session-filter-retention'))
                            @component('components.report-filters-new',['filters' => $filters, 'sourceFilters' => $sourceFilters??''])
                            @endcomponent
                        @else
                            @component('components.report-filters',['filters' => $filters, 'sourceFilters' => $sourceFilters??'', 'source' => $source])
                            @endcomponent
                        @endif
                    </div>
                    <div class="float-left">
                        @for($i=$maxLevel;$i>0;$i--)
                            <a href="javascript:void(0)" onclick="collapseExpandAllTab(this, {{$i}})"><span
                                    class="badge badge-primary"><span>Collapse</span> All {{$groupLevelName[$i]}}</span></a>
                        @endfor
                    </div>
                    <div id="cover-spin"></div>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered nrrreports" style="font-size: 12px !important;"
                           id="report_table-{{ $report['id'] }}" width="100%" cellspacing="0">
                        <thead>
                        <tr>
                            @foreach ($report['columns'] as $column)
                                <th style="{{ $column['heading']['style'] }}"<?php echo $column['heading']['attributes']; ?>>{{ $column['heading']['title'] }}</th>
                            @endforeach
                        </tr>
                        </thead>
                        <tbody>
                        @php
                            $lastStateName = "";
						    $rowcount2 = 0;
                        @endphp

                        @foreach ($data as $rowId => $row)
                                <?php
                                $rowcount2++;
                                //on the first row only, set $lastValue for each group to the field in the first row
                                if ($firstRowDone == 0) {
                                    foreach ($report['groupby'] as $group) {
                                        if ($group['field'] != '') $lastValue[$group['id']] = $row[$group['field']];
                                    }
                                    $firstRowDone = 1;
                                }
                                //set $currentValue for each group to the field in this row

                                foreach ($report['groupby'] as $group) {
                                    if ($group['field'] != '') $currentValue[$group['id']] = $row[$group['field']];
                                }
                                //check to see if we need to output one or more subtotal rows
                                //if the $currentValue <> $lastValue for the group or any 'downstream' groups, show the subtotal row
                                $parentGroup = '';
                                foreach ($report['groupby'] as $group) {
                                    $groupId = $group['id'];
                                    $parentGroup = $group['field'];
                                    $showTotal = 0;
                                    if ($groupId == 1) {
                                        if ($group['field'] != '') {
                                            $title = str_replace('@group4', $lastValue[4], str_replace('@group3', $lastValue[3], str_replace('@group2', $lastValue[2], str_replace('@group1', $lastValue[1], $group['label']))));
                                            $trStyle = $group['style'];
                                            if ($currentValue[1] != $lastValue[1] || $currentValue[2] != $lastValue[2] || $currentValue[3] != $lastValue[3] || $currentValue[4] != $lastValue[4] || $rowId == 'grand') $showTotal = 1;
                                        }
                                    } elseif ($groupId == 2) {
                                        if ($group['field'] != '') {
                                            $title = Reports::formatGroupTitle($groupId, $group, $lastValue);
                                            $trStyle = $group['style'];
                                            if ($currentValue[2] != $lastValue[2] || $currentValue[3] != $lastValue[3] || $currentValue[4] != $lastValue[4] || $rowId == 'grand') $showTotal = 1;
                                        }
                                    } elseif ($groupId == 3) {
                                        if ($group['field'] != '') {
                                            $title = Reports::formatGroupTitle($groupId, $group, $lastValue);
                                            $trStyle = $group['style'];
                                            if ($currentValue[3] != $lastValue[3] || $currentValue[4] != $lastValue[4] || $rowId == 'grand') $showTotal = 1;
                                        }
                                    } elseif ($groupId == 4) {
                                        if ($group['field'] != '') {
                                            $title = str_replace('@group4', $lastValue[4], str_replace('@group3', $lastValue[3], str_replace('@group2', $lastValue[2], str_replace('@group1', $lastValue[1], $group['label']))));
                                            $trStyle = $group['style'];
                                            if ($currentValue[4] != $lastValue[4] || $rowId == 'grand') $showTotal = 1;
                                        }
                                    } elseif ($groupId == 9) {
                                        $title = $group['label'] . ' ' . $lastValue[9];
                                        $trStyle = $group['style'];
                                        if ($rowId == 'grand' && $group['label'] != '') $showTotal = 1;
                                    }
                                    if ($showTotal == 1) {
                                        ${'count' . $groupId}++;
                                        $dataid = 0;
                                        if ($groupId == 1) {
                                            $dataid = $count3 . '' . $count2;
                                        } else if ($groupId == 2) {
                                            $dataid = $count3;
                                        } else if ($groupId == 3) {
                                            $dataid = $count3;
                                        } else if ($groupId == 4) {
                                            $dataid = 0;
                                        } else if ($groupId == 9) {
                                            $dataid = 0;
                                        }
                                        $totalOutput = '';
                                        if ($report['id'] == 'wepi' && $group['field'] == 'state') {
                                            $nextState = array_shift($states);

                                            while ($nextState !== null && $nextState != $lastValue[$group['id']]) {
                                                if ($nextState != $lastValue[$group['id']]) {
                                                    //state is missing so we need to print a row for it.
                                                    $totalOutput .= '<tr class="' . $group['field'] . '-totals ' . $group['field'] . '-totals-' . $dataid . ' parent' . $group['level'] . ' ' . ($group['level'] == $maxLevel ? 'hidechild' : 'hidechild hidden') . '" data-level="' . $dataid . '" style="' . $trStyle . '">';

                                                    $firstColumn = 1;

                                                    foreach ($report['columns'] as $column) {
                                                        if ($firstColumn == 1) {
                                                            $totalOutput .= '<td>' . $nextState . '</td>';
                                                            $firstColumn = 0;
                                                        } else {
                                                            if ($column['totals']['formula'] != '') {
                                                                $totalOutput .= '<td>0</td>';
                                                            } else {
                                                                $totalOutput .= '<td></td>';
                                                            }
                                                        }
                                                    }
                                                    $totalOutput .= '</tr>';
                                                }
                                                $nextState = array_shift($states);
                                            }
                                        }
                                        $totalOutput .= '<tr class="' . $group['field'] . '-totals ' . $group['field'] . '-totals-' . $dataid . ' parent' . $group['level'] . ' ' . ($group['level'] == $maxLevel ? 'hidechild' : 'hidechild hidden') . '" data-level="' . $dataid . '" style="' . $trStyle . '">';
                                        $firstColumn = 1;

                                        foreach ($report['columns'] as $column) {
                                            if ($firstColumn == 1) {
                                                $totalOutput .= '<td>' . $title . '</td>';
                                                $firstColumn = 0;
                                            } else {
                                                if ($column['totals']['formula'] != '') {
                                                    if ($column['totals']['formula'] == 'avg') {

                                                        if ($rowCount[$groupId][$column['field']] > 0) {
                                                            $tmpAvg = round($subtotals[$groupId][$column['field']] / $rowCount[$groupId][$column['field']], 0);
                                                            $totalOutput .= '<td>' . Reports::formatCell2($column['cell']['format'], $tmpAvg) . '</td>';  //add formatting before output
                                                        } else {
                                                            $totalOutput .= '<td>0%</td>';
                                                        }
                                                    } elseif ($column['totals']['formula'] == 'pct') {
                                                        if ($report['id'] == 'candidate_starts') {
                                                            //calculate the total percent to goal
                                                            if ($subtotals[$groupId][$report['goalField']] > 0) {
                                                                if ($column['field'] == 'Total') {
                                                                    $nonZeroCount = 0;
                                                                    $nonZeroTotal = 0;
                                                                    foreach ($report['columns'] as $tmpColumn) {
                                                                        if ($tmpColumn['field'] != 'Total' && $tmpColumn['cell']['format'] == 'pct'
                                                                            && $subtotals[$group['id']][$tmpColumn['field']] > 0) {
                                                                            $nonZeroCount++;
                                                                            $nonZeroTotal = $nonZeroTotal + round($subtotals[$group['id']][$tmpColumn['field']] /
                                                                                    $subtotals[$groupId][$report['goalField']] * 100, 0);
                                                                        }
                                                                    }
                                                                    if ($nonZeroCount > 0) {
                                                                        $tmpAvg = round($nonZeroTotal / $nonZeroCount, 0);
                                                                    } else {
                                                                        $tmpAvg = 0;
                                                                    }
                                                                } else {
                                                                    $tmpAvg = round($subtotals[$groupId][$column['field']] / $subtotals[$groupId][$report['goalField']] * 100, 0);
                                                                }
                                                                $totalOutput .= '<td>' . Reports::formatCell2($column['cell']['format'], $tmpAvg) . '</td>';  //add formatting before output
                                                            } else {
                                                                $totalOutput .= '<td>0%</td>';
                                                            }
                                                        } else {
                                                            $totalOutput .= '<td>0%</td>';
                                                        }
                                                    } elseif ($column['totals']['formula'] == 'sum') {
                                                        $totalOutput .= '<td>' . Reports::formatCell2($column['cell']['format'], $subtotals[$groupId][$column['field']]) . '</td>';  //add formatting before output
                                                    } else {
                                                        if ($column['field'] == 'SixWeekAvg' || $column['field'] == 'ThirteenWeekAvg') {
                                                            if ($report['id'] == 'gross_margin') {
                                                                if ($column['field'] == 'SixWeekAvg') {
                                                                    if ($sixWeekCount[$groupId] > 0) {
                                                                        $tmpAvg = round($sixWeekTotals[$groupId] / $sixWeekCount[$groupId], 0);
                                                                        $totalOutput .= '<td>' . Reports::formatCell2($column['cell']['format'], $tmpAvg) . '</td>';  //add formatting before output
                                                                    } else {
                                                                        $totalOutput .= '<td>0%</td>';
                                                                    }
                                                                }
                                                                if ($column['field'] == 'ThirteenWeekAvg') {
                                                                    if ($thirteenWeekCount[$groupId] > 0) {
                                                                        $tmpAvg = round($thirteenWeekTotals[$groupId] / $thirteenWeekCount[$groupId], 0);
                                                                        $totalOutput .= '<td>' . Reports::formatCell2($column['cell']['format'], $tmpAvg) . '</td>';  //add formatting before output
                                                                    } else {
                                                                        $totalOutput .= '<td>0%</td>';
                                                                    }
                                                                }
                                                            } else {
                                                                $tmpAvg = round($subtotals[$groupId][$column['field']] / $column['totals']['attributes'], 0);
                                                                $totalOutput .= '<td>' . Reports::formatCell2($column['cell']['format'], $tmpAvg) . '</td>';  //add formatting before output
                                                            }
                                                        } elseif ($column['field'] == 'AvgTrend') {
                                                            $arrayIconAttribute = '';
                                                            if ($report['id'] == 'gross_margin') {
                                                                if ($sixWeekCount[$groupId] > 0) {
                                                                    $tmpSixWeekAvg = round($sixWeekTotals[$groupId] / $sixWeekCount[$groupId], 0);
                                                                } else {
                                                                    $tmpSixWeekAvg = 0;
                                                                }
                                                                if ($thirteenWeekCount[$groupId] > 0) {
                                                                    $tmpThirteenWeekAvg = round($thirteenWeekTotals[$groupId] / $thirteenWeekCount[$groupId], 0);
                                                                } else {
                                                                    $tmpThirteenWeekAvg = 0;
                                                                }
                                                            } else {
                                                                $tmpSixWeekAvg = round($subtotals[$groupId]['SixWeekAvg'] / 6, 0);
                                                                $tmpThirteenWeekAvg = round($subtotals[$groupId]['ThirteenWeekAvg'] / 13, 0);
                                                            }
                                                            if ($tmpSixWeekAvg > $tmpThirteenWeekAvg) {
                                                                $arrayIconAttribute = ' <i class="fa fa-long-arrow-up" style="color:#32de84"></i>';
                                                            } elseif ($tmpSixWeekAvg < $tmpThirteenWeekAvg) {
                                                                $arrayIconAttribute = ' <i class="fa fa-long-arrow-down" style="color:red"></i>';
                                                            } else {
                                                                $arrayIconAttribute = '<i class="fa fa-solid fa-minus" style="color:yellow;"></i>';
                                                            }
                                                            if ($report['id'] == 'gross_margin' && $group['level'] == 5) {
                                                                $totalOutput .= '<td></td>';  //add formatting before output
                                                            } else {
                                                                $totalOutput .= '<td>' . $arrayIconAttribute . '</td>';
                                                            }
                                                        } else {
                                                            $totalOutput .= '<td>' . $column['totals']['formula'] . '</td>';
                                                        }
                                                    }
                                                } else {
                                                    if ($group['field'] == 'region') {
                                                        if ($column['field'] == 'whiteboard_row') {
                                                            $tmp = explode(' | ', $lastRecord['region']);
                                                            if (array_search('Recruiter', array_column($report['columns'], 'field')) !== false) {
                                                                $totalOutput .= '<td style="text-align:left" colspan="3">' . $tmp[0] . '</td>';
                                                            } else {
                                                                $totalOutput .= '<td style="text-align:left" colspan="2">' . $tmp[0] . '</td>';
                                                            }
                                                        } elseif ($column['field'] == 'territory') {
                                                            $tmp = explode(' | ', $lastRecord['region']);
                                                            if (isset($tmp[1])) {
                                                                $totalOutput .= '<td>' . $tmp[1] . '</td>';
                                                            } else {
                                                                $totalOutput .= '<td></td>';
                                                            }
                                                        } elseif ($column['field'] == 'region' || $column['field'] == 'Recruiter') {
                                                            //do nothing
                                                        } else {
                                                            $totalOutput .= '<td></td>';
                                                        }
                                                    } else {
                                                        if (strpos(',' . $group['showCols'] . ',', ',' . $column['field'] . ',') !== false) {
                                                            $totalOutput .= '<td>' . $lastRecord[$column['field']] . '</td>';
                                                        } else {
                                                            $totalOutput .= '<td></td>';
                                                        }
                                                    }
                                                }
                                            }
                                        }

                                        $totalOutput .= '</tr>';

                                        if ($groupId == 9) {
                                            $finalOutput = $output[9] . $output[4] . $output[3] . $output[2] . $output[1];
                                            $removeArray = ["hidechild", "hidden", "parent5"];
                                            echo str_replace($removeArray, "", $totalOutput);
                                            echo $finalOutput;
                                            echo $totalOutput; //show grand totals at the bottom
                                        } elseif ($groupId == 4) {
                                            $output[9] .= $totalOutput . $output[4];
                                            $output[4] = '';
                                        } elseif ($groupId == 3) {
                                            $output[4] .= $totalOutput . $output[3];
                                            $output[3] = '';
                                        } elseif ($groupId == 2) {
                                            $output[3] .= $totalOutput . $output[2];
                                            $output[2] = '';
                                        } elseif ($groupId == 1) {
                                            if (Route::currentRouteName() != 'candidates') {
                                                if ($lastStateName == 'TX') {
                                                    $output[2] .= $totalOutput . $output[1];
                                                } else {
                                                    $output[2] .= $output[1];
                                                }
                                            } else {
                                                $output[2] .= $totalOutput . $output[1];
                                            }
                                            $output[1] = '';
                                        } else {
                                            //error?
                                        }
                                        //zero out these totals
                                        foreach ($report['columns'] as $column) {
                                            if ($column['totals']['formula'] != '') {
                                                $subtotals[$groupId][$column['field']] = 0;
                                                $rowCount[$groupId][$column['field']] = 0;
                                            }
                                        }
                                        $sixWeekCount[$groupId] = 0;
                                        $sixWeekTotals[$groupId] = 0;
                                        $thirteenWeekCount[$groupId] = 0;
                                        $thirteenWeekTotals[$groupId] = 0;
                                    }
                                }
                                if ($rowId != 'grand') {
                                    //set grouping $lastValue = $currentValue (except for grand totals)
                                    foreach ($report['groupby'] as $group) {
                                        if ($group['id'] != 9) $lastValue[$group['id']] = $currentValue[$group['id']];
                                    }

                                    foreach ($report['groupby'] as $group) {
                                        if ($group['field'] == 'region') {
                                            $lastStateName = $row['state'];
                                        }
                                    }
                                    //accumulate totals
                                    foreach ($report['groupby'] as $group) {
                                        $groupId = $group['id'];
                                        foreach ($report['columns'] as $column) {
                                            if ($column['totals']['formula'] != '') {
                                                $subtotals[$groupId][$column['field']] = $subtotals[$groupId][$column['field']] + $row[$column['field']];

                                                if ($report['id'] == 'gross_margin' && $column['field'] == 'Total') {
                                                    $rowCount[$groupId][$column['field']] = $rowCount[$groupId][$column['field']] + $row['Count'];
                                                } elseif ($row[$column['field']] > 0) {
                                                    $rowCount[$groupId][$column['field']]++;
                                                }
                                            }
                                        }
                                        if ($report['id'] == 'gross_margin') {
                                            if ($row['SixWeekCount'] > 0) {
                                                $sixWeekCount[$groupId] = $sixWeekCount[$groupId] + $row['SixWeekCount'];
                                                $sixWeekTotals[$groupId] = $sixWeekTotals[$groupId] + $row['SixWeekAvg'];
                                            }
                                            if ($row['ThirteenWeekCount'] > 0) {
                                                $thirteenWeekCount[$groupId] = $thirteenWeekCount[$groupId] + $row['ThirteenWeekCount'];
                                                $thirteenWeekTotals[$groupId] = $thirteenWeekTotals[$groupId] + $row['ThirteenWeekAvg'];
                                            }
                                        }
                                    }

                                    //create the output into a variable
                                    $style = '';
                                    if (isset($report['rowStyle']) && $report['rowStyle'] != '') $style = ' style="' . $report['rowStyle'] . '"';
                                    $output[1] .= '<tr' . $style . ' class="hidden">';
                                    foreach ($report['columns'] as $column) {
                                        $cell = $row[$column['field']];
                                        if ($report['id'] == 'candidate_starts' && $column['cell']['format'] == 'pct') {
                                            if ($row[$report['goalField']] > 0) {
                                                if ($column['field'] == 'Total') {
                                                    $nonZeroCount = 0;
                                                    $nonZeroTotal = 0;
                                                    foreach ($report['columns'] as $tmpColumn) {
                                                        if ($tmpColumn['field'] != 'Total' && $tmpColumn['cell']['format'] == 'pct' && $row[$tmpColumn['field']] > 0) {
                                                            $nonZeroCount++;
                                                            $nonZeroTotal = $nonZeroTotal + round($row[$tmpColumn['field']] / $row[$report['goalField']] * 100, 0);
                                                        }
                                                    }
                                                    if ($nonZeroCount > 0) {
                                                        $cell = round($nonZeroTotal / $nonZeroCount, 0);
                                                    } else {
                                                        $cell = 0;
                                                    }
                                                } else {
                                                    $cell = round($row[$column['field']] / $row[$report['goalField']] * 100, 0);
                                                }
                                            } else {
                                                $cell = '0';
                                            }
                                        }
                                        if ($report['id'] == 'gross_margin') {
                                            if ($column['field'] == 'Total') {
                                                if ((int)$row['Count'] !== 0) {
                                                    $cell = round($row[$column['field']] / $row['Count']);
                                                } else {
                                                    $cell = $row[$column['field']];
                                                }
                                            }
                                        }
                                        $clickable = isset($column['clickable']) ? $column['clickable'] : false;
                                        $defaultval = isset($clickable['defaultval']) ? $clickable['defaultval'] : false;
                                        /*echo "<br><br><br>";
                                        var_dump($row);
                                        echo "<br>";

                                        var_dump($column);
                                        var_dump($defaultval); */

                                        $valArray = [];
                                        $jsonValues = '';
                                        $function = '';
                                        if ($clickable) {
                                            $function = $clickable['jsfunction'];
                                            foreach ($clickable['clickedfilters'] as $cfilter) {
                                                if (isset($row[$cfilter])) {
                                                    if ($row[$cfilter] === ReportTabInterface::INTAKE_ROW_NAME && isset($row['state4intake'])) {
                                                        $valArray[$cfilter] = ($row['state4intake']);
                                                    } else {
                                                        $valArray[$cfilter] = ($row[$cfilter]);
                                                    }
                                                }
                                            }

                                            if (!array_key_exists("Branch_Location", $valArray) && $source == 'branch' && Route::currentRouteName() != 'candidates') {
                                                $valArray['Branch_Location'] = $row['Branch_Location'] ?? null;
                                            }
                                        }
                                        if ($defaultval) {

                                            foreach ($defaultval as $defaultkey => $defaultv) {
                                                $valArray[$defaultkey] = $defaultv;
                                            }
                                        }
                                        if (!empty($valArray) && Reports::formatCell2($column["cell"]["format"], $cell) != 0) {
                                            $jsonValues = json_encode($valArray, JSON_ERROR_UTF8);
                                            $output[1] .= '<td class=\'showhand nrrreport\'  data-report="' . $report['id'] . '" data-json=\'' . $jsonValues . '\'>' . Reports::formatCell2($column["cell"]["format"], $cell) . '</td>';
                                        } else {
                                            if ($column['field'] == 'SixWeekAvg' || $column['field'] == 'ThirteenWeekAvg') {
                                                if ($report['id'] == 'gross_margin') {
                                                    if ($column['field'] == 'SixWeekAvg') {
                                                        if ($row['SixWeekCount'] > 0) {
                                                            $tmpAvg = round($row[$column['field']] / $row['SixWeekCount'], 0);
                                                            $output[1] .= '<td>' . Reports::formatCell2($column['cell']['format'], $tmpAvg) . '</td>';
                                                        } else {
                                                            $output[1] .= '<td>0%</td>';
                                                        }
                                                    }
                                                    if ($column['field'] == 'ThirteenWeekAvg') {
                                                        if ($row['ThirteenWeekCount'] > 0) {
                                                            $tmpAvg = round($row[$column['field']] / $row['ThirteenWeekCount'], 0);
                                                            $output[1] .= '<td>' . Reports::formatCell2($column['cell']['format'], $tmpAvg) . '</td>';
                                                        } else {
                                                            $output[1] .= '<td>0%</td>';
                                                        }
                                                    }
                                                } else {
                                                    $tmpAvg = round($row[$column['field']] / $column['totals']['attributes'], 0);
                                                    $output[1] .= '<td>' . Reports::formatCell2($column['cell']['format'], $tmpAvg) . '</td>';
                                                }
                                            } elseif ($column['field'] == 'AvgTrend') {
                                                $arrayIconAttribute = '';
                                                if ($report['id'] == 'gross_margin') {
                                                    if ($row['SixWeekCount'] > 0) {
                                                        $tmpSixWeekAvg = round($row[$column['field']] / $row['SixWeekCount'], 0);
                                                    } else {
                                                        $tmpSixWeekAvg = 0;
                                                    }
                                                    if ($row['ThirteenWeekCount'] > 0) {
                                                        $tmpThirteenWeekAvg = round($row[$column['field']] / $row['ThirteenWeekCount'], 0);
                                                    } else {
                                                        $tmpThirteenWeekAvg = 0;
                                                    }
                                                } else {
                                                    $tmpSixWeekAvg = round($row['SixWeekAvg'] / 6, 0);
                                                    $tmpThirteenWeekAvg = round($row['ThirteenWeekAvg'] / 13, 0);
                                                }
                                                if ($tmpSixWeekAvg > $tmpThirteenWeekAvg) {
                                                    $arrayIconAttribute = ' <i class="fa fa-long-arrow-up" style="color:#32de84"></i>';
                                                } elseif ($tmpSixWeekAvg < $tmpThirteenWeekAvg) {
                                                    $arrayIconAttribute = ' <i class="fa fa-long-arrow-down" style="color:red"></i>';
                                                } else {
                                                    $arrayIconAttribute = '<i class="fa fa-solid fa-minus" style="color:yellow;"></i>';
                                                }
                                                $output[1] .= '<td>' . $arrayIconAttribute . '</td>';
                                            } else {
                                                // if ($column['field'] !== 'Recruiter') {
                                                $output[1] .= '<td>' . Reports::formatCell2($column['cell']['format'], $cell) . '</td>';
                                                // }
                                            }
                                        }
                                    }
                                    $output[1] .= '</tr>';

                                    $lastRecord = $row;
                                }
                                ?>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- /.container-fluid -->
    <script type="text/javascript" id="nrrreportsjs">
        jQuery('input[type="radio"][name="source"]').change(function () {
            if (jQuery(this).val() == 'recruiter') {
                jQuery('.viewfilter').show();
            } else {
                jQuery('input[type="radio"][value="count"]').prop('checked', true);
                jQuery('.viewfilter').hide();
            }
        });

        function collapseExpandAllTab(element, level) {
            if ($(element).find('span span').text() == 'Collapse') {
                $(element).find('span span').text('Expand');
            } else {
                $(element).find('span span').text('Collapse');
            }
            $('tr.parent' + level).click();
        }

        <?php
        foreach ($report['groupby'] as $group) {
            if ($group['field'] != '') {
                $class = $group['field'] . '-totals';
                echo "
                $('." . $class . "').on('click', function() {
					$(this).toggleClass('hidechild');
					if($(this).nextAll('tr.parent" . $group['level'] . "').length > 0){
						if($(this).hasClass('hidechild')) {
							$(this).nextUntil('tr.parent" . $group['level'] . "').not('tr.parent" . ($group['level'] + 1) . "').not('tr.parent" . ($group['level'] + 2) . "').addClass('hidden');
						} else {
							$(this).nextUntil('tr.parent" . $group['level'] . "').not('tr.parent" . ($group['level'] + 1) . "').not('tr.parent" . ($group['level'] + 2) . "').removeClass('hidden').removeClass('hidechild');
						}
					} else if($(this).nextAll('tr.parent5').length > 0){
						if($(this).hasClass('hidechild')) {
							$(this).nextUntil('tr.parent5').addClass('hidden');
						} else {
							$(this).nextUntil('tr.parent5').removeClass('hidden').removeClass('hidechild');
						}
					}
                });
            ";
            }
        }
        ?>
@if(in_array(Route::currentRouteName(),['hoursOnNTH', 'IPEAs', 'PEPIs', 'WEPIs', 'candidateMatch', 'hoursRemoved', 'candidateStarts', 'grossMargin','candidates']))
    $(document).on("change", '#state', function (event, defaultBranch = []) {
        let _token = $('meta[name="csrf-token"]').attr('content');
        $.ajax({
            url: "{{ route('getstatebranch') }}",
            type: "GET",
            data: {
                state: $(this).val(),
                _token: _token
            },
            success: function (response) {
                $("#aoc_facility_id").empty();
                $.each(response, function (index, value) {
                    $("#aoc_facility_id").append(
                        '<option ' + (defaultBranch.includes('' + value.id) ? 'selected' : '') + ' value="' + value.id + '">' + value.FacilityName + '</option>'
                    );
                    $("#aoc_facility_id").multiselect("rebuild");
                });
            },
        });

	});
@endif

        @if (LD::checkFeatureFlag('save-report-filters-to-user-table'))
        $(document).on("click", "#ReportsFilterSearchForm .clearall", function (e) {
            $('#appSource').multiselect("clearSelection");
        });
        let defaultBranch = {!!json_encode(H::getReportFiltersFieldVal('aoc_facility_id', 'multiselect'))!!} || [];
        $('#state').trigger('change', defaultBranch);
        @endif
        $(window).scroll(function () {
            var scroll = $(window).scrollTop();
            if (scroll >= 80) {
                $("body").addClass("sticky");
            } else {
                $("body").removeClass("sticky");
            }
        });

        $(document).on('click', '.intake-totals-999', function () {
            const $clicked = $(this);
            let $next = $clicked.next();

            const isCollapsing = !$next.hasClass('hidden'); // se já está visível, vamos esconder

            // Toggle da classe no header (estético/visual)
            $clicked.toggleClass('hidechild');

            // Percorre todas as linhas do grupo
            while ($next.length && !$next.hasClass('intake-totals') && !$next.hasClass('parent999')) {
                if (isCollapsing) {
                    $next.addClass('hidden');
                } else {
                    $next.removeClass('hidden');
                }

                $next = $next.next();
            }
        });
    </script>
@endsection
