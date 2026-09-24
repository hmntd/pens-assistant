<?php

namespace Ocr;

use Grpc\BaseStub;

class OcrServiceClient extends BaseStub
{
    public function __construct($hostname, $opts, $channel = null)
    {
        parent::__construct($hostname, $opts, $channel);
    }

    public function RecognizeTaxDocument(OcrRequest $argument, $metadata = [], $options = [])
    {
        return $this->_simpleRequest('/ocr.OcrService/RecognizeTaxDocument',
            $argument,
            ['\Ocr\OcrResponse', 'decode'],
            $metadata, $options);
    }
}
