<div class="modal fade" id="createConsentTemplateModal" tabindex="-1" role="dialog" aria-labelledby="createConsentTemplateLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="createConsentTemplateLabel">Create consent template</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <form id="createConsentTemplateForm" data-url="{{ route('consent.tracking.save') }}" method="POST">
                @csrf
                <input type="hidden" name="id" value="">
                <div class="modal-body">
                    <div class="form-group row">
                        <div class="col-md-6">
                            <label for="source_type">Type</label>
                            <select id="source_type" name="type" class="form-control">
                                <option value="">Select Type</option>
                                @foreach (config('consentTrackingTemplate.types', []) as $type)
                                    <option value="{{ $type }}">{{ $type }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="source_type">Language</label>
                            <select id="source_type" name="language" class="form-control">
                                <option value="">Select Language</option>
                                @foreach (config('consentTrackingTemplate.language', []) as $code => $label)
                                    <option value="{{ $code }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="form-group row">
                        <div class="col-md-12">
{{--                            <div id="editor"></div>--}}
                            <textarea name="signature" id="signature" class="tinymce"></textarea>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <div id="formAlert" class="alert d-none w-100" role="alert"></div>

                    <img id="loadingGif" src="/images/LoaderIcon.gif"
                         style="display: none; margin-top: 15px; margin-right: 25px; right: 27px; top: 70%; transform: translateY(-50%); width: 16px; height: 16px;">

                    @canPermission('consent.tracking.destroy')
                    <button type="button" id="btnDeleteDraft" class="btn btn-danger" data-dismiss="modal">
                        Delete Draft
                    </button>
                    @endcanPermission

                    @canPermission('consent.tracking.publish')
                    <button type="button" id="btnSavePublish" class="btn btn-primary">
                        Save & Publish
                    </button>
                    @endcanPermission

                    @canPermission('consent.tracking.create')
                    <button type="button" id="btnSaveDraft" class="btn btn-primary">
                        Save As Draft
                    </button>
                    @endcanPermission
                </div>
            </form>
        </div>
    </div>
</div>
