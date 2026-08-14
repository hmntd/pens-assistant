#ifndef CALC_SERVICE_IMPL_H
#define CALC_SERVICE_IMPL_H

#include <grpcpp/grpcpp.h>
#include "calc.grpc.pb.h"
#include "../service/pension_calculator.h"
#include "../repository/coefficient_repository.h"

namespace calc {
namespace grpc_service {

class CalcServiceImpl final : public calc::CalcService::Service {
private:
    service::PensionCalculator calculator_;
    repository::CoefficientRepository repo_;

public:
    CalcServiceImpl();

    ::grpc::Status CalculatePension(::grpc::ServerContext* context, const ::calc::PensionRequest* request, ::calc::PensionResponse* reply) override;
    ::grpc::Status ListCoefficients(::grpc::ServerContext* context, const ::calc::ListCoefficientsRequest* request, ::calc::ListCoefficientsResponse* reply) override;
    ::grpc::Status AddCoefficient(::grpc::ServerContext* context, const ::calc::AddCoefficientRequest* request, ::calc::AddCoefficientResponse* reply) override;
    ::grpc::Status UpdateCoefficient(::grpc::ServerContext* context, const ::calc::UpdateCoefficientRequest* request, ::calc::UpdateCoefficientResponse* reply) override;
    ::grpc::Status DeleteCoefficient(::grpc::ServerContext* context, const ::calc::DeleteCoefficientRequest* request, ::calc::DeleteCoefficientResponse* reply) override;
    ::grpc::Status SyncAverageSalaries(::grpc::ServerContext* context, const ::calc::SyncAverageSalariesRequest* request, ::calc::SyncAverageSalariesResponse* reply) override;
};

}
}

#endif
