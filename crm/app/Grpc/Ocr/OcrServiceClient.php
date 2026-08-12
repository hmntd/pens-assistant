<?php

namespace Ocr;

class OcrServiceClient extends \Grpc\BaseStub {
    
    public function __construct($hostname, $opts, $channel = null) {
        parent::__construct($hostname, $opts, $channel);
    }

    public function SayHello(\Ocr\HelloRequest $argument, $metadata = [], $options = []) {
        return $this->_simpleRequest('/ocr.OcrService/SayHello',
            $argument,
            ['\Ocr\HelloResponse', 'decode'],
            $metadata, $options);
    }
}