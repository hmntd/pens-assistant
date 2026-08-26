<?php

namespace Calc;

class CalcServiceClient extends \Grpc\BaseStub {
    
    public function __construct($hostname, $opts, $channel = null) {
        parent::__construct($hostname, $opts, $channel);
    }

    public function CalculatePension(\Calc\CalculatePensionRequest $argument, $metadata = [], $options = []) {
        return $this->_simpleRequest('/calc.CalcService/CalculatePension',
            $argument,
            ['\Calc\CalculatePensionResponse', 'decode'],
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

    public function SyncAverageSalaries(\Calc\SyncAverageSalariesRequest $argument, $metadata = [], $options = []) {
        return $this->_simpleRequest('/calc.CalcService/SyncAverageSalaries',
            $argument,
            ['\Calc\SyncAverageSalariesResponse', 'decode'],
            $metadata, $options);
    }

    public function GetAverageSalaries(\Calc\GetAverageSalariesRequest $argument, $metadata = [], $options = []) {
        return $this->_simpleRequest('/calc.CalcService/GetAverageSalaries',
            $argument,
            ['\Calc\GetAverageSalariesResponse', 'decode'],
            $metadata, $options);
    }

    public function UpsertSubsistenceMinimum(\Calc\UpsertSubsistenceMinimumRequest $argument, $metadata = [], $options = []) {
        return $this->_simpleRequest('/calc.CalcService/UpsertSubsistenceMinimum',
            $argument,
            ['\Calc\UpsertSubsistenceMinimumResponse', 'decode'],
            $metadata, $options);
    }

    public function ListSubsistenceMinimums(\Calc\ListSubsistenceMinimumsRequest $argument, $metadata = [], $options = []) {
        return $this->_simpleRequest('/calc.CalcService/ListSubsistenceMinimums',
            $argument,
            ['\Calc\ListSubsistenceMinimumsResponse', 'decode'],
            $metadata, $options);
    }

    public function UpdateSubsistenceMinimum(\Calc\UpdateSubsistenceMinimumRequest $argument, $metadata = [], $options = []) {
        return $this->_simpleRequest('/calc.CalcService/UpdateSubsistenceMinimum',
            $argument,
            ['\Calc\UpdateSubsistenceMinimumResponse', 'decode'],
            $metadata, $options);
    }

    public function DeleteSubsistenceMinimum(\Calc\DeleteSubsistenceMinimumRequest $argument, $metadata = [], $options = []) {
        return $this->_simpleRequest('/calc.CalcService/DeleteSubsistenceMinimum',
            $argument,
            ['\Calc\DeleteSubsistenceMinimumResponse', 'decode'],
            $metadata, $options);
    }
}