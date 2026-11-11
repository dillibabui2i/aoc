<?php
namespace App\Helpers;

use App\Helpers\H;
use Illuminate\Support\Facades\Cache;

class CommonDatatableFilters
{
    protected $filter = ['state','aoc_facility_id'];

    /**
     * @param $field
     * @param $btype
     * @return array
     */
    public function getFilters($field, $btype = 'all'): array
    {
        $data = Cache::remember('common_datatable_filters:' . $btype, 300, function () use ($field, $btype) {

            // general state and branch
            $data['state']['tablename'] = 'mssql_facilities';
            $data['state']['pk'] = 'mssql_recruitertracker.aoc_facility_id';
            $data['state']['fk'] = 'mssql_facilities.id';
            $data['state']['datasource'] = H::getHelper('ConstantHelper')->getDashboardState();
            $data['state']['field'] = 'mssql_facilities.State';

            $branchObject = H::getHelper('ConstantHelper')->getFacilities();
            $branches = [];
            foreach ($branchObject as $br) {
                if (LD::checkFeatureFlag('cc-dashboard-remove-tx-skilled-visits-branch-monthly-calls')) {
                    if ($btype == 'monthlycall') {
                        if ($br->id != '14') {
                            $branches[] = ['value' => $br->id, 'text' => $br->FacilityName];
                        }
                    } else {
                        $branches[] = ['value' => $br->id, 'text' => $br->FacilityName];
                    }
                } else {
                    $branches[] = ['value' => $br->id, 'text' => $br->FacilityName];
                }
            }

            $data['aoc_facility_id']['datasource'] = $branches;
            $data['aoc_facility_id']['field'] = 'mssql_recruitertracker.aoc_facility_id';

            // specific to oc dashboard only
            $data['ocstate']['tablename'] = 'branch_locations';
            $data['ocstate']['pk'] = 'recruiting_schedule.ipea_location_id';
            $data['ocstate']['fk'] = 'branch_locations.id';
            $data['ocstate']['field'] = 'branch_locations.state';
            $ocStateObject = H::getHelper('ConstantHelper')->getDashboardState();
            $ocStates = [];
            foreach ($ocStateObject as $br) {
                $ocStates[] = ['value' => $br['text'], 'text' => $br['text']];
            }
            $data['ocstate']['datasource'] = $ocStates;
            $ocBranchObject = H::getHelper('ConstantHelper')->getBranches();
            $ocBranch = [];
            foreach ($ocBranchObject as $br) {
                $ocBranch[] = ['value' => $br->id, 'text' => $br->branch];
            }
            $data['ipea_location_id']['datasource'] = $ocBranch;
            $data['ipea_location_id']['field'] = 'recruiting_schedule.ipea_location_id';

            $ocObject = H::getOrientationCoordinatorList();

            $ocName = [];
            foreach ($ocObject as $br) {
                $ocName[] = ['value' => $br->id, 'text' => $br->name];
            }
            $data['oc']['datasource'] = $ocName;

            return $data;
        });

        return $data[$field] ?? [];
    }

    public function  getFilterDataSource($field,$btype='all'){
        $filter= $this->getFilters($field,$btype);
        if(isset($filter["datasource"])) {
            return $filter['datasource'];
        } else {
            return [];
        }
    }



    public function isGeneralFilter($filterName){
        if(in_array($filterName,$this->filter)){
            return true;
        } else {
            return false;
        }

    }

    public function generateQuery($query,$field,$data){
        $filter= $this->getFilters($field);


        if(!empty($filter['tablename']) && !empty($filter['fk'])) {
            $query->leftJoin($filter['tablename'],function($q) use($filter) {
                $q->on($filter['pk'],'=',$filter['fk']);
            });
        }
        if(is_array($data)) {
            $query->where(function($q) use($filter,$data){
                foreach($data as $val) {
                    $q->orWhere($filter["field"],'=',$val);
                }
            });
        } else {
            $query->where($filter["field"],'=',$data);
        }
        return $query;
    }

}
