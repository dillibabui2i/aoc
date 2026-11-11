<?php
namespace App\Helpers;


use App\FieldEvaluation;
use App\RightSignatureDocuments;
use Barryvdh\DomPDF\Facade\Pdf as PDFGenerator;
use Exception;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class RightSignature
{
    public $privateToken;
    public $ch;
    const DOCUMENT = '/documents/';

    private function initCurl($endpoint = 'documents')
    {
        $this->ch = null;
        $this->privateToken = config('rightSignature.privateToken');

        $endpointFound = false;

        foreach (config('rightSignature.allowedEndpoints') as $allowedEndpoint) {
            if (strpos($endpoint, $allowedEndpoint) !== false) {
                $endpointFound = true;
            }
        }

        if ($endpointFound) {
            $endpointUrl = config('rightSignature.url') . $endpoint;
            $this->ch = curl_init();

            curl_setopt($this->ch, CURLOPT_URL, $endpointUrl);
            curl_setopt($this->ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/json',
                'Authorization: Basic ' . base64_encode($this->privateToken . ':')
            ]);
            curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);
        }

        return $this;
    }

    /**
     * @return bool|string
     */
    public function getDocuments(): bool|string
    {
        $this->initCurl('documents');
        $response = curl_exec($this->ch);
        curl_close($this->ch);

        return $response;
    }

    /**
     * @param $filePath
     * @param $request
     * @return bool|string
     */
    public function uploadAndSendFile($filePath,$request): bool|string
    {
        $uploadUrl = $request['upload_url'];
        $id = $request['id'];
        // Initialize cURL
        $cUrl = curl_init();

        // Set the URL and other options
        curl_setopt($cUrl, CURLOPT_URL, $uploadUrl);
        curl_setopt($cUrl, CURLOPT_PUT, true);
        curl_setopt($cUrl, CURLOPT_RETURNTRANSFER, true);

        // Create a file handle
        $fileHandle = fopen($filePath, 'r');

        // Set the file data as the request body
        curl_setopt($cUrl, CURLOPT_INFILE, $fileHandle);
        curl_setopt($cUrl, CURLOPT_INFILESIZE, filesize($filePath));

        // Execute the request
        curl_exec($cUrl);
        curl_close($cUrl);

        return $this->updateToRS($id);
    }

    /**
     * @param $id
     * @return bool|string
     */
    public  function checkStatus($id): bool|string
    {
        $this->initCurl('sending_requests/'.$id);

        curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, 'GET');
        $res = curl_exec($this->ch);
        curl_close($this->ch);

        return $res;
    }

    /**
     * @param $id
     * @return bool|string
     */
    private function updateToRS($id): bool|string
    {
        $this->initCurl('sending_requests/'.$id.'/uploaded');
        curl_setopt($this->ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Basic ' . base64_encode($this->privateToken . ':'),
        ]);
        curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, 'POST');

        $responseFinal = curl_exec($this->ch);
        curl_close($this->ch);
        $this->ch = null;

        return $responseFinal;
    }

    /**
     * @param $filePath
     * @param $trackerId
     * @param $entityId
     * @param $entityType
     * @param $documentType
     * @param $documentTitle
     * @param $recipientName
     * @param $recipientEmail
     * @param $evaluation_id
     * @param $senderId
     * @param $extraJson
     * @return array
     * @throws Exception
     */
    public function sendDocumentRequest(
        $filePath,
        $trackerId,
        $entityId,
        $entityType,
        $documentType,
        $documentTitle,
        $recipientName,
        $recipientEmail,
        $evaluation_id = null,
        $senderId = null,
        $extraJson = null
    ) {
        $filename = basename($filePath);
        $signerSequencing = false;
        $personalizedMessage = '';
        if ($documentType == 'field_eval' || $documentType == 'therapy_eval' || $documentType == 'office_eval') {
            $personalizedMessage = 'Thank you for helping make AOC the awesome place it is! ';
            $personalizedMessage .= 'We appreciate your participation in our annual performance review process.';
            $personalizedMessage .= 'In order to close out the reviews, all staff must sign the review acknowledgment.';
            $personalizedMessage .= ' Please make sure that you sign your review and ';
            $personalizedMessage .= 'let your manager know if you have any questions.';
            $personalizedMessage .= ' Here’s to another great #HALOS year!';

            if (LD::checkFeatureFlag('rightsignature-multiple-signer')) {
                if (($documentType == 'field_eval' || $documentType == 'therapy_eval') && !empty($evaluation_id)) {
                    $managerData = null;
                    $evData = FieldEvaluation::where('id', $evaluation_id)->first();

                    if (!empty($evData->manager)) {
                        $managerData = DB::table('users')->select('*')->where('id', $evData->manager)->first();
                    }

                    if (!empty($managerData) && $managerData->name) {
                        $roles = [
                            [
                                'name' => 'a',
                                'sequence' => 0,
                                'signer_name' => $recipientName,
                                'signer_email' => $recipientEmail,
                                'message' => $personalizedMessage,
                            ],
                            [
                                'name' => 'b',
                                'sequence' => 1,
                                'signer_name' => $managerData->name,
                                'signer_email' => $managerData->email,
                                'message' => $personalizedMessage,
                            ],
                        ];

                        $signerSequencing = true;
                    } else {
                        $roles = [
                            [
                                'name' => 'a',
                                'signer_name' => $recipientName,
                                'signer_email' => $recipientEmail,
                                'message' => $personalizedMessage,
                            ],
                        ];
                    }
                } else {
                    $roles = [
                        [
                            'name' => 'a',
                            'signer_name' => $recipientName,
                            'signer_email' => $recipientEmail,
                            'message' => $personalizedMessage,
                        ],
                    ];
                }
            } else {
                $roles = [
                    [
                        'name' => 'a',
                        'signer_name' => $recipientName,
                        'signer_email' => $recipientEmail,
                        'message' => $personalizedMessage,
                    ],
                ];
            }
        } else {
            $roles = [
                [
                    'name' => 'a',
                    'signer_name' => $recipientName,
                    'signer_email' => $recipientEmail,
                ],
            ];
        }

        $expiresIn = 365;
        $documentName = $documentTitle;
        $shareWith = [];

        if (
            LD::checkFeatureFlag('enable-rightsignature-document-sharedwith') &&
            (
                $documentType == 'field_eval' ||
                $documentType == 'therapy_eval' ||
                $documentType == 'office_eval'
            )
        ) {
            $shareWith = ['perfeval@angelsofcare.com'];
        }

        $documentAttributes = [
            'file' => [
                'name' => $filename,
                'source' => 'upload',
            ],
            'document' => [
                'signer_sequencing' => $signerSequencing,
                'expires_in' => $expiresIn,
                'name' => $documentName,
                'roles' => $roles,
                'callback_url' => route('handleeventdocumentlevel'),
                'width' => 200,
                'height' => 100,
                'personalized_messages' => !empty($personalizedMessage),
                'shared_with' => $shareWith,
            ],
            'callback_url' => route('handledocumentstatus'),
            'sending_request' => [],
        ];

        $this->initCurl('sending_requests');
        curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($this->ch, CURLOPT_POSTFIELDS, json_encode($documentAttributes));

        $response = curl_exec($this->ch);
        curl_close($this->ch);
        $this->ch = null;

        $responseData = json_decode($response, true);

        if (isset($responseData['sending_request'])) {
            if ($documentType == 'field_eval') {
                $description = Date('Y') . ' ' . 'Field Evaluation';
            } elseif ($documentType == 'therapy_eval') {
                $description = Date('Y') . ' ' . 'Therapy Evaluation';
            } elseif ($documentType == 'pay_rate') {
                $description = Date('Y') . ' ' . 'Pay Rate Agreement';
            } else {
                $description = Date('Y') . ' ' . ucwords(str_replace('_', ' ', $documentType));
            }

            $data = [
                'tracker_id' => $trackerId,
                'entity_id' => $entityId,
                'entity_type' => $entityType,
                'sending_request_id' => $responseData['sending_request']['id'],
                'document_filename' => $filename,
                'document_type' => $documentType,
                'description' => $description,
                'created_at' => date('Y-m-d H:i:s'),
                'evaluation_id' => $evaluation_id,
            ];

            if (LD::checkFeatureFlag('rightsignature-snsform-pdf-trigger')) {
                if ($documentType == 'pay_rate' && !empty($extraJson)) {
                    $data['extra_data'] = $extraJson;
                }

                if ($documentType == 'pay_rate' && !empty($senderId)) {
                    $data['sender_id'] = $senderId;
                }
            }
            if ($documentType == 'field_eval' || $documentType == 'therapy_eval') {
                $name = $recipientName;
                $email = $recipientEmail;

                $noteData = [];
                $noteData['entity'] = 'clinician';
                $noteData['entity_id'] = $entityId;
                $noteData['notes'] = 'Annual evaluation was sent to ' . $name . ' at ' . $email . ' .';

                H::getHelper('ActivityNotes')->setActivityNotesFromConsole($noteData);
            }

            $request = [
                'id' => $responseData['sending_request']['id'],
                'upload_url' => $responseData['sending_request']['upload_url'],
            ];
            $this->addToRightSignatureTable($data);
            $this->uploadAndSendFile($filePath, $request);

            return $request;
        } else {
            return [];
        }
    }

    /**
     * @param $documentId
     * @return mixed
     */
    public function getDocumentByDocumentId($documentId): mixed
    {
        $this->initCurl('documents/' . $documentId);
        curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, 'GET');

        $response = $this->executeCurl();
        $responseData = json_decode($response, true);
        H::ExcelLog(print_r($responseData, true), 'rightsignaturedocument.log');

        return $responseData;
    }

    /**
     * @param $documentId
     * @return array|bool|string
     */
    public function sendReminder($documentId): bool|array|string
    {
        $responseData = $this->getDocumentByDocumentId($documentId);

        if (isset($responseData['document']['recipients'][0])) {
            $recipient = $responseData['document']['recipients'][0];
            $this->initCurl('signers/' . $recipient['id'] . '/reminders');

            curl_setopt($this->ch, CURLOPT_CUSTOMREQUEST, 'POST');
            $reminder = $this->executeCurl();

            $reminderData = DB::table('aoc_documents')
                ->select('reminder_count')
                ->where('document_id', $documentId)
                ->first();
            $reminderCount = 1;
            if (!empty($reminderData->reminder_count)) {
                $reminderCount = $reminderData->reminder_count + 1;
            }

            $this->updateDocumentStatusByDocumentId(
                $documentId,
                [
                    'reminder_date' => date('Y-m-d'),
                    'reminder_count' => $reminderCount,
                ]
            );
            return $reminder;
        } else {
            return [];
        }
    }

    /**
     * @param $documentId
     * @return void
     */
    public function downloadSignedDocument($documentId): void
    {
        $responseData = $this->getDocumentByDocumentId($documentId);

        try {
            if (
                isset($responseData['document']['merged_document_certificate_url']) &&
                !empty($responseData['document']['merged_document_certificate_url'])
            ) {
                $filename = $responseData['document']['filename'];
                $filePath = storage_path() . SELF::DOCUMENT . $filename;
                $file = fopen($filePath, 'w');

                H::ExcelLog('Replace Url:' . storage_path() . SELF::DOCUMENT . $filename, 'rightsignature_signed.log');
                H::ExcelLog(
                    'Signed Url:' . $responseData['document']['merged_document_certificate_url'],
                    'rightsignature_signed.log'
                );

                $merged_document_certificate_url_path = $responseData['document']['merged_document_certificate_url'];
                $merged_document_certificate_url_data = file_get_contents($merged_document_certificate_url_path);

                fwrite($file, $merged_document_certificate_url_data);
                fclose($file);
            }
        } catch (\Exception $e) {
            H::ExcelLog('Error URL:' . $e->getMessage(), 'rightsignature_signed.log');
        }
    }

    /**
     * @return bool|string
     */
    public function executeCurl(): bool|string
    {
        $response = curl_exec($this->ch);
        curl_close($this->ch);
        $this->ch = null;

        return $response;
    }

    /**
     * @param $id
     * @param $data
     * @return $this
     */
    public function assignDocumentIdToRequestId($id, $data): static
    {
        RightSignatureDocuments::where('sending_request_id', $id)->update($data);

        return $this;
    }

    /**
     * @param $id
     * @param $data
     * @return $this
     */
    public function updateDocumentStatusByDocumentId($id, $data): static
    {
        RightSignatureDocuments::where('document_id', $id)->update($data);

        return $this;
    }

    /**
     * @param $data
     * @return $this
     */
    public function addToRightSignatureTable($data): static
    {
        RightSignatureDocuments::insert($data);
        return $this;
    }

    /**
     * @param $filename
     * @param $data
     * @param $template
     * @param $download
     * @return Response|string
     */
    public function generateRightSignaturePDFTemplete(
        $filename,
        $data = [],
        $template = 'pay_rate',
        $download = false
    ): Response|string
    {
        if (!is_dir(storage_path() . '/documents')) {
            mkdir(storage_path() . '/documents');
        }

        $pdf = PDFGenerator::loadView('rightsignature.pdftemplate.' . $template, $data);

        if ($download) {
            return $pdf->download($filename);
        } else {
            $pdf->save(storage_path() . self::DOCUMENT . $filename);
            return storage_path() . self::DOCUMENT . $filename;
        }
    }

    /**
     * @param $filename
     * @param $data
     * @return string
     */
    public function generateEvalution($filename, $data = []): string
    {
        $pdf = PDFGenerator::loadView('rightsignature.pdftemplate.fieldevaluation', $data);
        $pdf->save(storage_path() . SELF::DOCUMENT . $filename);

        return storage_path() . SELF::DOCUMENT . $filename;
    }

    /**
     * @param $aocFacilityId
     * @return mixed|string
     */
    public function getMinimumWage($aocFacilityId): mixed
    {
        $data['AZ'] = ['N/A'];
        $data['CO'] = ['13.65'];
        $data['FL'] = ['11.00'];
        $data['NC'] = ['7.25'];
        $data['PA'] = ['7.25'];
        $data['PA-PITS'] = ['7.25'];
        $data['SC'] = ['7.25'];
        $data['TX'] = ['7.25'];

        if (!empty($aocFacilityId)) {
            $facility = H::getHelper('ConstantHelper')->getFacilityDataById($aocFacilityId);
            if (isset($data[$facility->State][0])) {
                if ($facility->State == 'PA' && $facility->FacilityName == 'PA - Pittsburgh') {
                    return $data['PA-PITS'][0];
                } else {
                    return $data[$facility->State][0];
                }
            }
        }

        return 'N/A';
    }

    /**
     * @param $skill
     * @param $aocFacilityId
     * @return mixed|string
     */
    public function getTrainingRate($skill, $aocFacilityId): mixed
    {
        $data['AZ'] = ['LVN' => 'N/A', 'LPN' => 'N/A', 'RN' => 'N/A', 'CNA' => 'N/A', 'IHSS' => 'N/A'];
        $data['CO'] = ['LVN' => '15.00', 'LPN' => '15.00', 'RN' => '17.00', 'CNA' => '13.65', 'IHSS' => '13.65'];
        $data['FL'] = ['LVN' => '15.00', 'LPN' => '15.00', 'RN' => '17.00', 'CNA' => '11.00', 'IHSS' => 'N/A'];
        $data['NC'] = ['LVN' => '15.00', 'LPN' => '15.00', 'RN' => '17.00', 'CNA' => '7.35', 'IHSS' => 'N/A'];
        $data['PA'] = ['LVN' => '15.00', 'LPN' => '15.00', 'RN' => '17.00', 'CNA' => '7.25', 'IHSS' => 'N/A'];
        $data['PA-PITS'] = ['LVN' => '20.00', 'LPN' => '20.00', 'RN' => '20.00', 'CNA' => '10.00', 'IHSS' => 'N/A'];
        $data['SC'] = ['LVN' => '15.00', 'LPN' => '15.00', 'RN' => '17.00', 'CNA' => '7.35', 'IHSS' => 'N/A'];
        $data['TX'] = ['LVN' => '15.00', 'LPN' => '15.00', 'RN' => '17.00', 'CNA' => 'N/A', 'IHSS' => 'N/A'];

        if (!empty($aocFacilityId)) {
            $facility = H::getHelper('ConstantHelper')->getFacilityDataById($aocFacilityId);
            if (isset($data[$facility->State][$skill])) {
                if ($facility->State == 'PA' && $facility->FacilityName == 'PA - Pittsburgh') {
                    return $data['PA-PITS'][$skill];
                } else {
                    return $data[$facility->State][$skill];
                }
            }
        }

        return 'N/A';
    }

    /**
     * @param $providerId
     * @param $scheduleStartDate
     * @param $scheduleEndDate
     * @return mixed|string
     */
    public function getCCMFromProvider($providerId,$scheduleStartDate,$scheduleEndDate): mixed
    {
        // TODO(AOC-4484): Interim override — GA-Atlanta nurses' managers aren't set up in
        // KanTime yet, so the CCM lookup below always comes up empty for them. Remove this
        // block once KanTime is properly configured for that branch.
        $facility = DB::table('mssql_providers')
            ->select('mssql_facilities.FacilityName')
            ->leftJoin('mssql_facilities', 'mssql_facilities.FacilityID', '=', 'mssql_providers.FacilityID')
            ->where('mssql_providers.ProviderID', $providerId)
            ->first();

        if ($facility && $facility->FacilityName === 'GA - Atlanta') {
            return 'Erin Smith';
        }

        $scheduleStartDate = date('Y-01-04', strtotime('-1 Year'));
        $scheduleEndDate = date('Y-01-03');

        $patient = DB::table('mssql_schedulemaster')
            ->select('mssql_patients.ccm_id', 'mssql_schedulemaster.PatientID', DB::raw('sum(Hours) as totalhours'))
            ->leftJoin('mssql_patients', 'mssql_patients.PatientID', '=', 'mssql_schedulemaster.PatientID')
            ->where('ProviderID', $providerId)
            ->whereIn('ScheduleStatusID', [1, 2, 3])
            ->whereDate('ScheduleDate', '>=', $scheduleStartDate)
            ->whereDate('ScheduleDate', '<=', $scheduleEndDate)
            ->whereRaw('ifnull(mssql_patients.ccm_id,0) != 0 and ifnull(mssql_patients.PatientID,0) != 0')
            ->groupBy('PatientID')
            ->orderBy('totalhours', 'desc')
            ->limit(1)
            ->first();

        if ($patient && $patient->ccm_id) {
            $ccm = DB::table('mssql_patients')
                ->select('users.name')
                ->leftJoin('users', 'users.id', '=', 'mssql_patients.ccm_id')
                ->where('mssql_patients.PatientID', $patient->PatientID)
                ->first();

            return $ccm->name;
        } else {
            $branchDBO = $this->getDBORDOByClinician($providerId, "DBO");
            if (!empty($branchDBO)) {
                return $branchDBO;
            } else {
                return $this->getDBORDOByClinician($providerId, "RDO");
            }
        }
    }

    /**
     * @param $provider
     * @param $type
     * @return mixed|string
     */
    public function getDBORDOByClinician($provider,$type): mixed
    {
        $rolename = 'Director of Branch Operations';
        if ($type == 'RDO') {
            $rolename = 'Regional Director of Operations';
        }

        $branch = DB::table('mssql_providers')->select('mssql_facilities.branchid')
            ->leftJoin('mssql_facilities', 'mssql_facilities.FacilityID', '=', 'mssql_providers.FacilityID')
            ->where('mssql_providers.ProviderID', $provider)->first();

        if ($branch) {
            if ($branch->branchid) {
                $user = DB::table('users')->select('users.name')
                    ->leftJoin('users_has_roles', 'users.id', '=', 'users_has_roles.user_id')
                    ->leftJoin('roles', 'users_has_roles.role_id', '=', 'roles.id')
                    ->where('roles.name', $rolename)->where('users.status', 1)
                    ->whereRaw('find_in_set(' . $branch->branchid . ', users.branchid)')->first();
                if ($user) {
                    return $user->name;
                } else {
                    return '';
                }
            }
        }
        return '';
    }
}
