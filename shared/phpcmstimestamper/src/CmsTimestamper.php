<?php

namespace Blobfish;

use phpseclib\File\ASN1;

class CmsTimestamper
{
    // NOTE that this is hack!. TODO check better the resulting inner workings of phpseclib because of this.
    private static function importedValueTypeDeclaration($tagNumber)
    {
        return [
            'type' => ASN1::TYPE_OCTET_STRING, "class" => 0x00, "cast" => $tagNumber
        ];
    }

    private static function getOriginalValue($originalCmsBytes, $item)
    {
        $originalValue = substr($originalCmsBytes, $item['start'] + $item['headerlength'], $item['length'] - $item['headerlength']);
        // We are encoding to Base64 because it is required by phpseclib 2.0.7.
        return base64_encode($originalValue);
    }

    /**
     * @param $originalCms string PEM encoded original CMS
     * @param $tsaUrl
     * @return string updated CMS structure encoded as PEM
     * @throws \Exception if there is any failure retrieving the TST
     */
    public static function addTimestampToCms($originalCms, $tsaUrl)
    {
        // Loading the original CMS.
        $x509 = new \phpseclib\File\X509();
        $originalCmsBytes = $x509->_extractBER($originalCms);
        $phpseclibAsn1 = new ASN1();
        $decodedOriginalCms = $phpseclibAsn1->decodeBER($originalCmsBytes);
        // TODO check if these hardcoded indexes won't produce problems.
        $originalContentInfo = $decodedOriginalCms[0]['content'];
        $originalSignedData = $originalContentInfo[1]['content'][0]['content'];
        $originalSignerInfo = $originalSignedData[4]['content'][0]['content'];
        $signatureValue = $originalSignerInfo[5]['content'];

        // Requesting the timestamp.
        $timestampResponseTempFile = tempnam(sys_get_temp_dir(), "tsr_");
        $processTsQueryCommand = "openssl ts -query -cert | curl -X POST --data-binary @- -H \"Content-Type: application/timestamp-query\" --output - {$tsaUrl} > $timestampResponseTempFile && openssl ts -reply -in $timestampResponseTempFile -token_out";
        $descriptors = array(
            0 => array("pipe", "r"),
            1 => array("pipe", "w"),
            2 => array("pipe", "w")
        );
        $pipes = array();
        $process = proc_open($processTsQueryCommand, $descriptors, $pipes);
        fwrite($pipes[0], $signatureValue);
        fclose($pipes[0]);
        $tstBytes = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);
        $exitStatus = proc_close($process);
        unlink($timestampResponseTempFile);
        if ($exitStatus != 0) {
            throw new \Exception("Failure retrieving TST: " . $stderr);
        }

        // Updating the CMS with the retrieved timestamp.
        $updatedContentInfo = [
            'contentType' => self::getOriginalValue($originalCmsBytes, $originalContentInfo[0]),
            'content' => [
                'version' => self::getOriginalValue($originalCmsBytes, $originalSignedData[0]),
                'digestAlgorithms' => self::getOriginalValue($originalCmsBytes, $originalSignedData[1]),
                'encapContentInfo' => self::getOriginalValue($originalCmsBytes, $originalSignedData[2]),
                'certificates' => self::getOriginalValue($originalCmsBytes, $originalSignedData[3]),
                'signerInfos' => [
                    'signerInfo' => [
                        'version' => self::getOriginalValue($originalCmsBytes, $originalSignerInfo[0]),
                        'sid' => self::getOriginalValue($originalCmsBytes, $originalSignerInfo[1]),
                        'digestAlgorithm' => self::getOriginalValue($originalCmsBytes, $originalSignerInfo[2]),
                        'signedAttrs' => self::getOriginalValue($originalCmsBytes, $originalSignerInfo[3]),
                        'signatureAlgorithm' => self::getOriginalValue($originalCmsBytes, $originalSignerInfo[4]),
                        'signature' => self::getOriginalValue($originalCmsBytes, $originalSignerInfo[5]),
                        'unsignedAttrs' => [
                            'attribute' => [
                                // id-aa-timeStampToken.
                                'attrType' => '1.2.840.113549.1.9.16.2.14',
                                // We are encoding to Base64 because it is required by phpseclib 2.0.7.
                                'attrValues' => base64_encode($tstBytes)
                            ]
                        ]
                    ]
                ],
            ]
        ];
        // TODO look for a way to modify an object structure loaded with \phpseclib\File\ASN1::decodeBER and then just write it using \phpseclib\File\ASN1::encodeDER without the need to create a map like the following... if it doesn't exist in phpseclib it could be contributed, or a helper could be created.
        $asn1Mapping = [
            'type' => ASN1::TYPE_SEQUENCE,
            'children' => [
                'contentType' => self::importedValueTypeDeclaration(0x06),
                'content' => [
                    'type' => ASN1::TYPE_SEQUENCE,
                    "class" => 0x02, // TODO check if the class is right in this context, otherwise set cast to A0 directly and class to 0x00.
                    "cast" => 0x20,
                    "explicit" => true,
                    'children' => [
                        'version' => self::importedValueTypeDeclaration(0x02),
                        'digestAlgorithms' => self::importedValueTypeDeclaration(0x31),
                        'encapContentInfo' => self::importedValueTypeDeclaration(0x30),
                        'certificates' => self::importedValueTypeDeclaration(0xA0),
                        'signerInfos' => [
                            'type' => ASN1::TYPE_SET,
                            'children' => [
                                'signerInfo' => [
                                    'type' => ASN1::TYPE_SEQUENCE,
                                    'children' => [
                                        'version' => self::importedValueTypeDeclaration(0x02),
                                        'sid' => self::importedValueTypeDeclaration(0x30),
                                        'digestAlgorithm' => self::importedValueTypeDeclaration(0x30),
                                        'signedAttrs' => self::importedValueTypeDeclaration(0xA0),
                                        'signatureAlgorithm' => self::importedValueTypeDeclaration(0x30),
                                        'signature' => self::importedValueTypeDeclaration(0x04),
                                        'unsignedAttrs' => [
                                            'type' => ASN1::TYPE_SET,
                                            "class" => 0x02, // TODO check if the class is right in this context, otherwise set cast to A0 directly and class to 0x00.
                                            "cast" => 0x21,
                                            'children' => [
                                                'attribute' => [
                                                    'type' => ASN1::TYPE_SEQUENCE,
                                                    'children' => [
                                                        'attrType' => [
                                                            'type' => ASN1::TYPE_OBJECT_IDENTIFIER
                                                        ],
                                                        'attrValues' => self::importedValueTypeDeclaration(0x31)]
                                                ]
                                            ],
                                        ]
                                    ],
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ];
        // NOTE that we are using a preceding @ symbol, otherwise "PHP Notice:  Undefined variable: temp in .../vendor/phpseclib/phpseclib/phpseclib/File/ASN1.php on line 1096" would be produced because of the hack in the method 'importedValueTypeDeclaration'.
        $updatedCmsBytes = @$phpseclibAsn1->encodeDER($updatedContentInfo, $asn1Mapping);
        return "-----BEGIN CMS-----\r\n" . chunk_split(base64_encode($updatedCmsBytes), 64) . '-----END CMS-----';
    }
//    }
}
