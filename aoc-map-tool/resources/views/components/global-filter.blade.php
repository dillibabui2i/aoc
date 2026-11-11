<style>
    .filter-bar {
        background: #eaf2fc;
        border: 1px solid #cfe0f5;
        border-radius: 6px;
        padding: 12px 16px;
        margin-top: 20px;
        margin-bottom: 20px;
    }

    .filter-bar__top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 10px;
    }

    .filter-bar__title-group {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .filter-bar__title {
        font-weight: 600;
        font-size: 14px;
        color: #1b2a3d;
    }

    .filter-bar__controls {
        display: flex;
        align-items: center;
        gap: 10px;
        flex-wrap: wrap;
    }

    select {
        appearance: none;
        background: #fff url('data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="10" height="6"><path d="M0 0l5 6 5-6z" fill="%23555"/></svg>') no-repeat right 10px center;
        border: 1px solid #c7d2e0;
        border-radius: 4px;
        padding: 7px 28px 7px 10px;
        font-size: 13px;
        color: #333;
        min-width: 140px;
        cursor: pointer;
    }

    .badge-region-independent {
        border: 1px solid #2f9e6e;
        color: #2f9e6e;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.3px;
        padding: 4px 8px;
        border-radius: 4px;
        background: #fff;
        white-space: nowrap;
    }
    .gf-modal-backdrop{
        position:fixed;inset:0;z-index:2050;
        display:flex;align-items:center;justify-content:center;
        background:rgba(0,0,0,.35);
        opacity:0;pointer-events:none;transition:opacity .15s ease;
    }
    .gf-modal-backdrop.is-open{opacity:1;pointer-events:auto;}

    .gf-modal{
        width:440px;max-width:92vw;
        background:#fff;border-radius:8px;
        box-shadow:0 8px 28px rgba(0,0,0,.25);
        padding:24px;
        transform:translateY(-8px);transition:transform .15s ease;
    }
    .gf-modal-backdrop.is-open .gf-modal{transform:none;}

    .gf-modal h5{margin:0 0 10px;font-size:17px;font-weight:700;color:#14213d;}
    .gf-modal p{margin:0 0 16px;font-size:13.5px;line-height:1.5;color:#4a5568;}

    .gf-modal-list{
        background:#fdfaf0;border:1px solid #eadfae;border-radius:5px;
        padding:10px 14px;margin-bottom:20px;
        font-size:13px;color:#6b5b2a;
        max-height:120px;overflow:auto;
    }
    .gf-modal-actions{display:flex;justify-content:flex-end;gap:10px;}
    .gf-modal-actions .btn{min-width:96px;}
    .gf-toast{
        position:fixed;
        left:50%;
        bottom:40px;
        transform:translate(-50%,12px);
        z-index:2000;
        max-width:80vw;
        padding:10px 18px;
        border-radius:6px;
        background:#1b3b6f;
        color:#fff;
        font-size:13px;
        line-height:1.4;
        box-shadow:0 2px 8px rgba(0,0,0,.25);
        opacity:0;
        pointer-events:none;
        transition:opacity .2s ease, transform .2s ease;
    }
    .gf-toast.is-visible{
        opacity:1;
        transform:translate(-50%,0);
    }
</style>

<div class="filter-bar" data-state-branch-url="{{ route('getstatebranch') }}">

    <div class="filter-bar__top">
        <div class="filter-bar__title-group">
            <span class="filter-bar__title">Global Location Filter</span>
        </div>
    </div>

    <div class="filter-bar__controls">
        <select multiple class="selectpicker" data-placeholder="Select State" name="state">
            @foreach($stateOptions as $state)
                <option value="{{$state['value']}}">{{$state['text']}}</option>
            @endforeach
        </select>
        <select multiple class="selectpicker" data-placeholder="Select Region" name="region">
            @foreach($regionOptions as $region)
                <option value="{{$region['value']}}">{{$region['text']}}</option>
            @endforeach
        </select>
        <select multiple class="selectpickersearch" data-placeholder="Select Branch" data-live-search="true" name="branch">
            @foreach($branchOptions as $branch)
                <option value="{{$branch['value']}}">{{$branch['text']}}</option>
            @endforeach
        </select>
        <button type="button" class="btn btn-primary">Apply to all buckets</button>
        <button type="button" class="btn btn-secondary">Clear</button>
    </div>
</div>
