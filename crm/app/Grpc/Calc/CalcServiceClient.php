<?php

namespace Calc;

use Grpc\BaseStub;

class CalcServiceClient extends BaseStub
{
    public function __construct($hostname, $opts, $channel = null)
    {
        parent::__construct($hostname, $opts, $channel);
    }

    public function CalculatePension(CalculatePensionRequest $argument, $metadata = [], $options = [])
    {
        return $this->_simpleRequest('/calc.CalcService/CalculatePension',
            $argument,
            ['\Calc\CalculatePensionResponse', 'decode'],
            $metadata, $options);
    }

    public function ListCoefficients(ListCoefficientsRequest $argument, $metadata = [], $options = [])
    {
        return $this->_simpleRequest('/calc.CalcService/ListCoefficients',
            $argument,
            ['\Calc\ListCoefficientsResponse', 'decode'],
            $metadata, $options);
    }

    public function AddCoefficient(AddCoefficientRequest $argument, $metadata = [], $options = [])
    {
        return $this->_simpleRequest('/calc.CalcService/AddCoefficient',
            $argument,
            ['\Calc\AddCoefficientResponse', 'decode'],
            $metadata, $options);
    }

    public function UpdateCoefficient(UpdateCoefficientRequest $argument, $metadata = [], $options = [])
    {
        return $this->_simpleRequest('/calc.CalcService/UpdateCoefficient',
            $argument,
            ['\Calc\UpdateCoefficientResponse', 'decode'],
            $metadata, $options);
    }

    public function DeleteCoefficient(DeleteCoefficientRequest $argument, $metadata = [], $options = [])
    {
        return $this->_simpleRequest('/calc.CalcService/DeleteCoefficient',
            $argument,
            ['\Calc\DeleteCoefficientResponse', 'decode'],
            $metadata, $options);
    }

    public function SyncAverageSalaries(SyncAverageSalariesRequest $argument, $metadata = [], $options = [])
    {
        return $this->_simpleRequest('/calc.CalcService/SyncAverageSalaries',
            $argument,
            ['\Calc\SyncAverageSalariesResponse', 'decode'],
            $metadata, $options);
    }

    public function GetAverageSalaries(GetAverageSalariesRequest $argument, $metadata = [], $options = [])
    {
        return $this->_simpleRequest('/calc.CalcService/GetAverageSalaries',
            $argument,
            ['\Calc\GetAverageSalariesResponse', 'decode'],
            $metadata, $options);
    }

    public function UpsertSubsistenceMinimum(UpsertSubsistenceMinimumRequest $argument, $metadata = [], $options = [])
    {
        return $this->_simpleRequest('/calc.CalcService/UpsertSubsistenceMinimum',
            $argument,
            ['\Calc\UpsertSubsistenceMinimumResponse', 'decode'],
            $metadata, $options);
    }

    public function ListSubsistenceMinimums(ListSubsistenceMinimumsRequest $argument, $metadata = [], $options = [])
    {
        return $this->_simpleRequest('/calc.CalcService/ListSubsistenceMinimums',
            $argument,
            ['\Calc\ListSubsistenceMinimumsResponse', 'decode'],
            $metadata, $options);
    }

    public function UpdateSubsistenceMinimum(UpdateSubsistenceMinimumRequest $argument, $metadata = [], $options = [])
    {
        return $this->_simpleRequest('/calc.CalcService/UpdateSubsistenceMinimum',
            $argument,
            ['\Calc\UpdateSubsistenceMinimumResponse', 'decode'],
            $metadata, $options);
    }

    public function DeleteSubsistenceMinimum(DeleteSubsistenceMinimumRequest $argument, $metadata = [], $options = [])
    {
        return $this->_simpleRequest('/calc.CalcService/DeleteSubsistenceMinimum',
            $argument,
            ['\Calc\DeleteSubsistenceMinimumResponse', 'decode'],
            $metadata, $options);
    }
}
