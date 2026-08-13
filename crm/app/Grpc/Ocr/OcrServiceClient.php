<?php

namespace Ocr;

class OcrServiceClient extends \Grpc\BaseStub {
    
    public function __construct($hostname, $opts, $channel = null) {
        parent::__construct($hostname, $opts, $channel);
    }

    public function RecognizeTaxDocument(\Ocr\OcrRequest $argument, $metadata = [], $options = []) {
        return $this->_simpleRequest('/ocr.OcrService/RecognizeTaxDocument',
            $argument,
            ['\Ocr\OcrResponse', 'decode'],
            $metadata, $options);
    }
}