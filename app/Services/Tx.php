<?php

namespace App\Services;

use GuzzleHttp\Command\Result;
use Qcloud\Cos\Client as CosClient;
use Qcloud\Cos\Exception\ServiceResponseException;
use Qcloud\Cos\ImageParamTemplate\ImageMogrTemplate;
use Qcloud\Cos\ImageParamTemplate\PicOperationsTransformation;
use function Qcloud\Cos\region_map;

/**
 * Tencent cloud service.
 */
class Tx {
    public $bucket    = '';
    public $secretKey = '';

    public const SUPPORTED_FORMAT = [
        'webp' => 'webp',
        'jpg'  => 'jpg',
        'png'  => 'png',
        'gif'  => 'gif',
        'bmp'  => 'bmp'
    ];
    // see [九宫格方位图](https://cloud.tencent.com/document/product/436/44881)
    public const GRAVITY = [
        'center'    => 'center',
        'east'      => 'east',
        'west'      => 'west',
        'south'     => 'south',
        'north'     => 'north',
        'northeast' => 'northeast',
        'northwest' => 'northwest',
        'southeast' => 'southeast',
        'southwest' => 'southwest'
    ];

    /**
     * @var array
     */
    protected $cosConfig;
    /**
     * @var CosClient
     */
    protected $cosClient;

    public function __construct($cosConfig = []) {
        $cosConfig['credentials'] = [
            'appId'     => config('filesystems.disks.cosv5.credentials.appId'),
            'secretId'  => config('filesystems.disks.cosv5.credentials.secretId'),
            'secretKey' => config('filesystems.disks.cosv5.credentials.secretKey'),
            'token'     => config('filesystems.disks.cosv5.credentials.token')
        ];
        $cosConfig['schema']          = $cosConfig['schema'] ?? config('filesystems.disks.cosv5.scheme');
        $cosConfig['region']          = isset($cosConfig['region']) ? region_map($cosConfig['region']) : config('filesystems.disks.cosv5.region');
        $cosConfig['appId']           = $cosConfig['credentials']['appId'];
        $cosConfig['secretId']        = $cosConfig['credentials']['secretId'];
        $cosConfig['secretKey']       = $cosConfig['credentials']['secretKey'];
        $cosConfig['token']           = $cosConfig['credentials']['token'];
        $cosConfig['timeout']         = $cosConfig['timeout']         ?? config('filesystems.disks.cosv5.timeout');
        $cosConfig['connect_timeout'] = $cosConfig['connect_timeout'] ?? config('filesystems.disks.cosv5.connect_timeout');

        $cosConfig['allow_accelerate'] = $cosConfig['allow_accelerate'] ?? config('filesystems.disks.cosv5.allow_accelerate');

        $this->bucket    = config('filesystems.disks.cosv5.bucket');
        $this->secretKey = $cosConfig['credentials']['secretKey'] ?? config('filesystems.disks.cosv5.credentials.secretKey');

        $this->cosConfig = $cosConfig;
        $this->cosClient = new CosClient($cosConfig);
    }

    public function getAuthorization($method, $url) {
        $cosRequest = new \GuzzleHttp\Psr7\Request($method, $url);

        $signature = new \Qcloud\Cos\Signature(
            $this->cosConfig['secretId'],
            $this->cosConfig['secretKey']
        );

        return $signature->createAuthorization($cosRequest);
    }

    public function getUrl($key, array $args = []) {
        $command = $this->cosClient->getCommand('GetObject', $args + ['Bucket' => $this->bucket, 'Key' => $key]);
        $request = $this->cosClient->commandToRequestTransformer($command);

        return $request->getUri()->__toString();
    }

    /**
     * @param string $fileID
     * @param string $toFileID
     * @param string $format
     * @param int    $w
     * @param int    $h
     * @param int    $q
     * @return array
     * @throws ServiceResponseException
     */
    public function scropFile(string $fileID, string $toFileID, string $format = self::SUPPORTED_FORMAT['webp'],
                              int $w = 600, int $h = 600, int $q = 80) {
        $imageMogrTemplate = new \Qcloud\Cos\ImageParamTemplate\ImageMogrTemplate();
        $imageMogrTemplate->scrop($w, $h);
        $imageMogrTemplate->thumbnailByMinWH($w, $h);
        $imageMogrTemplate->cropByWH($w, $h, self::GRAVITY['center']);
        $imageMogrTemplate->format($format);
        $imageMogrTemplate->quality($q, 1);
        $picOperationsTemplate = new \Qcloud\Cos\ImageParamTemplate\PicOperationsTransformation();
        $picOperationsTemplate->setIsPicInfo(1);
        $picOperationsTemplate->addRule($imageMogrTemplate, '/' . $toFileID);

        // dd($picOperationsTemplate->queryString());
        /** @var Result $result */
        $result = $this->cosClient->ImageProcess([
            'Bucket'         => $this->bucket,
            'PicOperations'  => $picOperationsTemplate->queryString(),
            'Key'            => $fileID,
        ]);

        return $result->toArray();
    }

    /**
     * @param string $fileID
     * @param string $toFileID
     * @param string $content
     * @param string $format
     * @param int    $w
     * @param int    $h
     * @param int    $q
     * @return array
     * @throws ServiceResponseException
     */
    public function scropAndUpload(string $fileID, string $toFileID, string $content, string $format = self::SUPPORTED_FORMAT['webp'],
                                   int $w = 600, int $h = 600, int $q = 80) {
        $imageMogrTemplate = new \Qcloud\Cos\ImageParamTemplate\ImageMogrTemplate();
        $imageMogrTemplate->scrop($w, $h);
        $imageMogrTemplate->thumbnailByMinWH($w, $h);
        $imageMogrTemplate->cropByWH($w, $h, self::GRAVITY['center']);
        $imageMogrTemplate->format($format);
        $imageMogrTemplate->quality($q);
        $picOperationsTemplate = new \Qcloud\Cos\ImageParamTemplate\PicOperationsTransformation();
        $picOperationsTemplate->setIsPicInfo(1);
        $picOperationsTemplate->addRule($imageMogrTemplate, '/' . $toFileID);

        /** @var Result $result */
        $result = $this->cosClient->putObject([
            'Bucket'         => $this->bucket,
            'Key'            => $fileID,
            'Body'           => $content,
            'PicOperations'  => $picOperationsTemplate->queryString(),
        ]);

        logger()->info('scropAndUpload:', $result->toArray());

        return $result->toArray();
    }

    public function upload($fileID, $content, string $format = self::SUPPORTED_FORMAT['webp']) {
        $imageMogrTemplate = new \Qcloud\Cos\ImageParamTemplate\ImageMogrTemplate();
        $imageMogrTemplate->format($format);
        $picOperationsTemplate = new \Qcloud\Cos\ImageParamTemplate\PicOperationsTransformation();
        $picOperationsTemplate->setIsPicInfo(1);
        $picOperationsTemplate->addRule($imageMogrTemplate, '/' . $fileID);

        $result = $this->cosClient->putObject([
            'Bucket'         => $this->bucket,
            'Key'            => $fileID,
            'Body'           => $content,
            'PicOperations'  => $picOperationsTemplate->queryString(),
        ]);

        logger()->info('uploaded:', $result->toArray());

        return $result->toArray();
    }

    /**
     * @param $fileID
     * @param null $versionID
     * @return mixed
     */
    public function deleteObject($fileID, $versionID = null) {
        $options = [
            'Bucket' => $this->bucket,
            'Key'    => $fileID
        ];
        if ($versionID) {
            $options['VersionId'] = $versionID;
        }

        return $this->cosClient->deleteObject($options)->toArray();
    }

    /**
     * @param string $fileID
     * @param string $fileName
     * @param $content
     * @param string $format
     * @param int    $w
     * @param int    $h
     * @param int    $q
     * @param string $gravity
     * @return array
     * @throws ServiceResponseException
     */
    public function thumbnailAndUpload(string $fileID, string $fileName, $content, string $format = self::SUPPORTED_FORMAT['webp'],
                                       int $w = null, int $h = null, int $q = 70, $gravity = self::GRAVITY['center']) {
        $imageMogrTemplate = new ImageMogrTemplate();
        if ($w && $h) {
            $imageMogrTemplate->thumbnailByMinWH($w, $h);
            $imageMogrTemplate->cropByWH($w, $h, $gravity);
        }
        $imageMogrTemplate->quality($q);
        $imageMogrTemplate->format($format);
        $picOperationsTemplate = new PicOperationsTransformation();
        $picOperationsTemplate->setIsPicInfo(1);
        $picOperationsTemplate->addRule($imageMogrTemplate, '/' . $fileName);

        return $this->cosClient->putObject([
            'Bucket'        => $this->bucket,
            'Key'           => $fileID,
            'Body'          => $content,
            'PicOperations' => $picOperationsTemplate->queryString(),
        ])->toArray();
    }

    public static function useCustomDomain($url) {
        return preg_replace('#https?://(poe|p)\-1254719278\.cos.+\.myqcloud.com#', config('app.cos_custom_domain_url'), $url);
    }
}
