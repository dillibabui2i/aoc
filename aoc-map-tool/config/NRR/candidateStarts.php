<?php

use App\Helpers\LD;

return [
    'title' => 'Candidate Starts',
    'reportId' => 'candidate_starts',
    'goalField' => 'candidate_start_hour_goals',

    /**
     * define the fields to group by for subtotals.
     * I set it up to allow up to 4 groupings.  The last one with id = 9 is for the grand totals.
     * leave 'field' empty for ones you do not need.  It will ignore it.
     * do not remove any or change the id values!!!!
     * make sure your query sorts the data in the same order as the grouping are defined!!!
     * it prints the subtotal row when the data in column 'field' changes
     * style = the style to apply to the <tr> tag
     * label = what to display before the grouping value use @group3 @group2 to insert the value of the grouping fields
     * (number = groupID)
     *
     * Define groups with the lowest level first
     *
     */
    'groupBy' => [
        [
            'id' => 1,
            'label' => '',
            'field' => 'region',
            'showCols' => 'region',
            'style' => 'background-color: #D9D9D9;color:#444;',
            'level' => 1
        ],
        [
            'id' => 2,
            'label' => '',
            'field' => 'whiteboard_row',
            'showCols' => 'whiteboard_row',
            'style' => 'background-color: #dbf0fd;color:#444444;',
            'level' => 2
        ],
        [
            'id' => 3,
            'label' => '@group3',
            'field' => 'state',
            'showCols' => 'state',
            'style' => 'background-color: #215eac;color:#fff;',
            'level' => 3
        ],
        [
            'id' => 4,
            'label' => '',
            'field' => '',
            'showCols' => '',
            'style' => '',
            'level' => 4
        ],
        [
            'id' => 9,
            'label' => 'National Totals',
            'field' => '',
            'showCols' => '',
            'style' => 'background-color: #D5D5D5;color:#000',
            'level' => 5
        ],
    ],
    'recruiterColumns' => [
        [
            'field' => 'state',
            'heading' => ['title' => 'State', 'attributes' => '', 'style' => ''],
            'cell' => ['format' => 'hide', 'attributes' => '', 'style' => ''],
            'totals' => ['formula' => '', 'attributes' => '', 'style' => ''],
        ],
        [
            'field' => 'whiteboard_row',
            'heading' => ['title' => 'Service', 'attributes' => '', 'style' => ''],
            'cell' => ['format' => 'hide', 'attributes' => '', 'style' => ''],
            'totals' => ['formula' => '', 'attributes' => '', 'style' => ''],
        ],
        [
            'field' => 'region',
            'heading' => ['title' => 'Region', 'attributes' => '', 'style' => ''],
            'cell' => ['format' => 'hide', 'attributes' => '', 'style' => ''],
            'totals' => ['formula' => '', 'attributes' => '', 'style' => ''],
        ],
        [
            'field' => 'Recruiter',
            'heading' => ['title' => 'Recruiter', 'attributes' => '', 'style' => ''],
            'cell' => ['format' => '', 'attributes' => '', 'style' => ''],
            'totals' => ['formula' => '', 'attributes' => '', 'style' => ''],
        ],
        [
            'field' => 'territory',
            'heading' => ['title' => 'Territory', 'attributes' => '', 'style' => ''],
            'cell' => ['format' => '', 'attributes' => '', 'style' => ''],
            'totals' => ['formula' => '', 'attributes' => '', 'style' => ''],
        ],
        [
            'field' => 'hire_date',
            'heading' => ['title' => 'Hire Date', 'attributes' => '', 'style' => ''],
            'cell' => ['format' => 'date', 'attributes' => '', 'style' => ''],
            'totals' => ['formula' => '', 'attributes' => '', 'style' => ''],
        ],
    ],
    'branchColumns' => [
        [
            'field' => 'state',
            'heading' => ['title' => 'State', 'attributes' => '', 'style' => ''],
            'cell' => ['format' => 'hide', 'attributes' => '', 'style' => ''],
            'totals' => ['formula' => '', 'attributes' => '', 'style' => ''],
        ],
        [
            'field' => 'whiteboard_row',
            'heading' => ['title' => 'Service', 'attributes' => '', 'style' => ''],
            'cell' => ['format' => 'hide', 'attributes' => '', 'style' => ''],
            'totals' => ['formula' => '', 'attributes' => '', 'style' => ''],
        ],
        [
            'field' => 'region',
            'heading' => ['title' => 'Region', 'attributes' => '', 'style' => ''],
            'cell' => ['format' => 'hide', 'attributes' => '', 'style' => ''],
            'totals' => ['formula' => '', 'attributes' => '', 'style' => ''],
        ],
        [
            'field' => 'Branch_Location',
            'heading' => ['title' => 'Branch', 'attributes' => '', 'style' => ''],
            'cell' => ['format' => '', 'attributes' => '', 'style' => ''],
            'totals' => ['formula' => '', 'attributes' => '', 'style' => ''],
        ]
    ],
    'sourceFilters' => [
        [
            'label' => 'By Recruiter',
            'value' => 'recruiter'
        ],
        [
            'label' => 'By Branch',
            'value' => 'branch'
        ]
    ]
];
