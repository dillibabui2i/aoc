<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MobileAppMenuItems extends Model
{
    use HasFactory;
    protected $table = 'mobile_app_menu_items';

    private static function getTranslationKey($menuKey)
    {
        $keyMap = [
            'dashboard' => 'menu_dashboard',
            'getUpcomingAppointments' => 'menu_upcoming_appointments',
            'getNTHSchedules' => 'menu_permanent_schedule_needs',
            'requestForm' => 'menu_request_forms',
            'requestForms' => 'menu_request_forms',
            'parentPortal' => 'menu_parent_portal',
            'detail' => 'menu_profile',
            'addSupplyRequest' => 'menu_add_supply_request',
            'getSupplyRequest' => 'menu_supply_request',
            'getPrnDays' => 'menu_get_prn_days',
            'getExpiringRenewables' => 'menu_get_expiring_renewables',
            'vacationRequest' => 'menu_vacation',
            'reportHospitalization' => 'menu_report_hospitalization',
            'callRequest' => 'menu_request_call',
            'submitHalos' => 'menu_submit_halos',
            'featurePhotos' => 'menu_feature_photo',
            'submitDemographic' => 'menu_change_demographic',
            'submitPRNDate' => 'menu_submit_prn_dates',
        ];

        return $keyMap[$menuKey] ?? null;
    }

    private static function getDescriptionKey($menuKey)
    {
        $keyMap = [
            'getUpcomingAppointments' => 'desc_upcoming_appointments',
            'getNTHSchedules' => 'desc_permanent_schedule_needs',
            'vacationRequest' => 'desc_vacation',
            'reportHospitalization' => 'desc_report_hospitalization',
            'callRequest' => 'desc_request_call',
            'submitHalos' => 'desc_submit_halos',
            'featurePhotos' => 'desc_feature_photo',
        ];

        return $keyMap[$menuKey] ?? null;
    }

    public static function getAppMenuItemList($parentId = null,$menu_type=1)
    {
        $menuItemList = self::where('status',1)->where('parent_id', $parentId)->where('menu_type', $menu_type)->orderBy('sort_order')->get()->toArray();
        $result = $response =[];
        $cnt = 0;
        foreach ($menuItemList as $menuItemData) {
            $childMenu = [];
            $menuKey = $menuItemData['menu_item_key'];

            $result[$cnt]['key'] = $menuKey;

            $translationKey = self::getTranslationKey($menuKey);
            $result[$cnt]['value'] = $translationKey ? __('messages.' . $translationKey) : $menuItemData['menu_item_value'];

            $result[$cnt]['page_title'] = $translationKey ? __('messages.' . $translationKey) : (!empty($menuItemData['page_title']) ? $menuItemData['page_title'] : $menuItemData['menu_item_value']);

            $descriptionKey = self::getDescriptionKey($menuKey);
            $result[$cnt]['description'] = $descriptionKey ? __('messages.' . $descriptionKey) : $menuItemData['description'];

            $result[$cnt]['link'] = $menuItemData['link']==null?'':$menuItemData['link'];

            $consentVal = '';
            if($menuKey == 'submitHalos') {
                $consentVal = __('messages.consent_submit_halos');
            } elseif($menuKey == 'featurePhotos') {
                $consentVal = __('messages.consent_feature_photos');
            }
            $result[$cnt]['consent'] = $consentVal;

            $childMenu = self::getAppMenuItemList($menuItemData['id'],$menuItemData['menu_type']);
            $result[$cnt]['childMenu'] = $childMenu;
            $cnt++;
        }
        return $result;
    }
}
