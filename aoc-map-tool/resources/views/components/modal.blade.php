<style>
.modal-content {
    padding: 15px;
}

.extra-header-cnt {
    display: flex;
    flex: auto;
    align-items: center;
}

.w70 {
    max-width: 70% !important;
}

.w60 {
    max-width: 60% !important;
}

.w80 {
    max-width: 80% !important;
}

.modal-header {
    border-bottom: 1px solid #ddd;
    padding-bottom: 5px;
    align-items: center;
}

.modal-footer {
    border-top: 0px;
}
</style>
<div>
    <div class="modal fade {{ $class ?? '' }}" id="{{ $id }}" role="dialog" aria-labelledby="{{ $id }}-title"
        aria-modal="true" {{isset($isStatic) && ($isStatic) ? 'data-backdrop=static data-keyboard=false' : ''}}>
        <div class="modal-dialog modal-dialog-centered {{ $size ?? '' }}" role="document">
            <div class="modal-content shadow">
                <div class="modal-header">
                    <h5 class="modal-title" id="{{ Str::slug($title) }}-title">{{ $title }}</h5>
                    @if(isset($extraHeader))
                    <div class="extra-header-cnt">
                        {{ $extraHeader }}
                    </div>
                    @endif
                    @if(!isset($isCloseBtn) || (isset($isCloseBtn) && $isCloseBtn != 'false'))
                    <button type="button" class="close {{isset($isCloseBtn) ? $isCloseBtn : ''}}" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                    @endif
                </div>
                <div class="modal-body">
                    {{ $body }}
                </div>
                <div class="modal-footer">
                    {{ isset($footer) ? $footer : ''  }}
                </div>
            </div>
        </div>
    </div>
</div>
