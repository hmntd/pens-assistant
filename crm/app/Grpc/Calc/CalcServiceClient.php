<?php

namespace Calc;

class CalcServiceClient extends \Grpc\BaseStub {
    
    public function __construct($hostname, $opts, $channel = null) {
        parent::__construct($hostname, $opts, $channel);
    }

    public function CalculatePension(\Calc\PensionRequest $argument, $metadata = [], $options = []) {
        return $this->_simpleRequest('/calc.CalcService/CalculatePension',
            $argument,
            ['\Calc\PensionResponse', 'decode'],
            $metadata, $options);
    }

    public function ListCoefficients(\Calc\ListCoefficientsRequest $argument, $metadata = [], $options = []) {
        return $this->_simpleRequest('/calc.CalcService/ListCoefficients',
            $argument,
            ['\Calc\ListCoefficientsResponse', 'decode'],
            $metadata, $options);
    }

    public function AddCoefficient(\Calc\AddCoefficientRequest $argument, $metadata = [], $options = []) {
        return $this->_simpleRequest('/calc.CalcService/AddCoefficient',
            $argument,
            ['\Calc\AddCoefficientResponse', 'decode'],
            $metadata, $options);
    }

    public function UpdateCoefficient(\Calc\UpdateCoefficientRequest $argument, $metadata = [], $options = []) {
        return $this->_simpleRequest('/calc.CalcService/UpdateCoefficient',
            $argument,
            ['\Calc\UpdateCoefficientResponse', 'decode'],
            $metadata, $options);
    }

    public function DeleteCoefficient(\Calc\DeleteCoefficientRequest $argument, $metadata = [], $options = []) {
        return $this->_simpleRequest('/calc.CalcService/DeleteCoefficient',
            $argument,
            ['\Calc\DeleteCoefficientResponse', 'decode'],
            $metadata, $options);
    }
}