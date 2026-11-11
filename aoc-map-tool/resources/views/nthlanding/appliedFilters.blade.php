
<ul class="applied-filters">
    <?php foreach($filters as $filter) { 
            if($filter['name']=='orderby' || $filter['name']=='clearall') {
                continue;
            }

            if(!empty($filter['value'])) {?> 
                <li class="filter-item pull-left col-auto">
                    <div class="item">
                        <span><?php echo $filter['value']?></span>
                        <button type="button" data-value="<?php echo $filter['value']?>" data-id="<?php echo $filter['name']?>" class="btn-close clear-filter" aria-label="Close">X</button>
                    </div>
                </li>
        
    <?php } }?>
</ul>