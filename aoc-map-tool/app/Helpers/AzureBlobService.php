<?php
namespace App\Helpers;
use Illuminate\Support\Facades\DB;
use App\Helpers\H;
use App\Helpers\LD;
use Illuminate\Support\Facades\Http;

class AzureBlobService
{
    protected $accountName;
    protected $accountKey;
    protected $containerName;
    protected $version= '2015-02-21';
    public function __construct()
    {
        $this->accountName = env("AZURE_BLOB_ACCOUNTNAME");
        $this->accountKey = env("AZURE_BLOB_ACCOUNTKEY");
    
    }

    protected function generateAuthorizationHeader($containerName,$blobName='',$additionalArray = []){

        
        $currentDate = gmdate("D, d M Y H:i:s T", time());
        
        $method = "GET";
        $contentType = '';
        $fileLen = '';
        if(!empty($additionalArray)) {
            $method = "PUT";
            list($fileLen,$contentType) = $additionalArray;
        }
        if(!empty($blobName)) {
            $headerResource = "x-ms-blob-cache-control:max-age=3600\nx-ms-blob-type:BlockBlob";
            $headerResource.="\nx-ms-date:$currentDate\nx-ms-version:$this->version";
            $urlResource = "/$this->accountName/$containerName/$blobName";
        } else {
            $headerResource = "x-ms-date:$currentDate\nx-ms-version:2014-02-14";
            $urlResource =  "/{$this->accountName}/{$containerName}\nrestype:container";
        }
        
    
        $arraysign = array();
        $arraysign[] = $method;             /*HTTP Verb*/
        $arraysign[] = '';                  /*Content-Encoding*/
        $arraysign[] = '';                  /*Content-Language*/
        $arraysign[] = $fileLen;            /*Content-Length (include value when zero)*/
        $arraysign[] = '';                  /*Content-MD5*/
        $arraysign[] = $contentType;        /*Content-Type*/
        $arraysign[] = '';                  /*Date*/
        $arraysign[] = '';                  /*If-Modified-Since */
        $arraysign[] = '';                  /*If-Match*/
        $arraysign[] = '';                  /*If-None-Match*/
        $arraysign[] = '';                  /*If-Unmodified-Since*/
        $arraysign[] = '';                  /*Range*/
        $arraysign[] = $headerResource;     /*CanonicalizedHeaders*/
        $arraysign[] = $urlResource;        /*CanonicalizedResource*/
    
        $str2sign = implode("\n", $arraysign);

       
    
        $sig = base64_encode(
            hash_hmac('sha256',urldecode(utf8_encode($str2sign)),
            base64_decode($this->accountKey), true));
        $authHeader = "SharedKey $this->accountName:$sig";
      
        return [$currentDate,$authHeader];
    }
   


    public function uploadBlob($container,$blobName,$filetoUpload) {

        $handle = fopen($filetoUpload, "r");
        $fileLen = filesize($filetoUpload);
        $fileType = mime_content_type($filetoUpload);
        $destinationURL = $this->getBlobUrl($container,$blobName);
        list($currentDate,$authHeader) = $this->generateAuthorizationHeader($container,$blobName,[$fileLen,$fileType]);
    
        $headers = [
            'Authorization: ' . $authHeader,
            'x-ms-blob-cache-control: max-age=3600',
            'x-ms-blob-type: BlockBlob',
            'x-ms-date: ' . $currentDate,
            'x-ms-version: '.$this->version,
            'Content-Type: '.$fileType,
            'Content-Length: ' . $fileLen
        ];
    
        $ch = curl_init($destinationURL);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($ch, CURLOPT_INFILE, $handle);
        curl_setopt($ch, CURLOPT_INFILESIZE, $fileLen);
        curl_setopt($ch, CURLOPT_UPLOAD, true);
        $result = curl_exec($ch);
        curl_close($ch);
        if ($result === false) {
            return false;
        }
        return true;
    }

    public function getBlobFromAzure($container,$blobName){
        
        $destinationURL = $this->getBlobUrl($container,$blobName);
        list($currentDate,$authHeader) = $this->generateAuthorizationHeader($container,$blobName);

        $headers = [
            'Authorization: ' . $authHeader,
            'x-ms-blob-cache-control: max-age=3600',
            'x-ms-blob-type: BlockBlob',
            'x-ms-date: ' . $currentDate,
            'x-ms-version: '.$this->version,
        ];
    
        $ch = curl_init($destinationURL);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
        $result = curl_exec($ch);

        $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        
        curl_close($ch);
        if ($result === false) {
            return false;
        }
        return [$result,$contentType];
        
    }
    
    public function getBlobUrl($containerName,$blobName)
    {
        return sprintf(
            'https://%s.blob.core.windows.net/%s/%s',
            $this->accountName,
            $containerName,
            $blobName
        );
    }

    

    public function createContainer($container='')
    {

        $destinationUrl = "https://{$this->accountName}.blob.core.windows.net/{$container}?restype=container";
        list($currentDate,$authHeader) = $this->generateAuthorizationHeader($container,'',[0,'']);

        $headers = [
            'x-ms-date:'.$currentDate,
            'x-ms-version:2014-02-14',
            'Authorization:'.$authHeader,
            'Content-Length:0',
            
        ];
        
        $ch = curl_init($destinationUrl);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        curl_close($ch);
        if ($response === false) {
           return false;
        }
        return true;
    }
}
