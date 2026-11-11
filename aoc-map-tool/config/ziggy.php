<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Route groups
    |--------------------------------------------------------------------------
    |
    | The app has ~850 named routes, and `@routes` embeds whatever it is given
    | into the page as JSON. Exposing all of them would bloat every page and
    | publish the entire admin surface to the browser, so this group lists only
    | the routes the Vue frontend actually calls.
    |
    | Emitted once from layouts/myaccount.blade.php, right before the Vue bundle
    | (see the `page-scripts` push there) — the bundle already loads on every page
    | using that layout, so there is nothing to gain from emitting it per view.
    |
    */
    'groups' => [
        'vbc' => [
            'hospitalization.*',
            'care-coordination.*',
            'follow-up.*',
            'immunization.*',
            // Shared filter-options payload for all three ICM dashboard buckets + All Hospitalizations;
            // also the frontend's permission probe (see usePermissions.js).
            'filters.index',
            // Legacy page the tracker's person icon links out to.
            'client.getDetails',
        ],
    ],

    /*
    | The `route()` helper is imported from the `ziggy-js` module by the Vue
    | bundle (see the alias in webpack.mix.js), so `@routes` only needs to emit
    | the route list — inlining the helper as well would ship the same code in
    | the HTML of every page that uses it.
    */
    'skip-route-function' => true,

];
