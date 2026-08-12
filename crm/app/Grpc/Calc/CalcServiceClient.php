<?php

namespace Calc;

class CalcServiceClient extends \Grpc\BaseStub {
    
    public function __construct($hostname, $opts, $channel = null) {
        parent::__construct($hostname, $opts, $channel);
    }

    public function SayHello(\Calc\HelloRequest $argument, $metadata = [], $options = []) {
        return $this->_simpleRequest('/calc.CalcService/SayHello',
            $argument,
            ['\Calc\HelloResponse', 'decode'],
            $metadata, $options);
    }
}