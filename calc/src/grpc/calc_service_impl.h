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

    ::grpc::Status CalculatePension(::grpc::ServerContext* context, const ::calc::CalculatePensionRequest* request, ::calc::CalculatePensionResponse* reply) override;
    ::grpc::Status ListCoefficients(::grpc::ServerContext* context, const ::calc::ListCoefficientsRequest* request, ::calc::ListCoefficientsResponse* reply) override;
    ::grpc::Status AddCoefficient(::grpc::ServerContext* context, const ::calc::AddCoefficientRequest* request, ::calc::AddCoefficientResponse* reply) override;
    ::grpc::Status UpdateCoefficient(::grpc::ServerContext* context, const ::calc::UpdateCoefficientRequest* request, ::calc::UpdateCoefficientResponse* reply) override;
    ::grpc::Status DeleteCoefficient(::grpc::ServerContext* context, const ::calc::DeleteCoefficientRequest* request, ::calc::DeleteCoefficientResponse* reply) override;
    ::grpc::Status SyncAverageSalaries(::grpc::ServerContext* context, const ::calc::SyncAverageSalariesRequest* request, ::calc::SyncAverageSalariesResponse* reply) override;
    ::grpc::Status UpsertSubsistenceMinimum(::grpc::ServerContext* context, const ::calc::UpsertSubsistenceMinimumRequest* request, ::calc::UpsertSubsistenceMinimumResponse* reply) override;
    ::grpc::Status ListSubsistenceMinimums(::grpc::ServerContext* context, const ::calc::ListSubsistenceMinimumsRequest* request, ::calc::ListSubsistenceMinimumsResponse* reply) override;
    ::grpc::Status UpdateSubsistenceMinimum(::grpc::ServerContext* context, const ::calc::UpdateSubsistenceMinimumRequest* request, ::calc::UpdateSubsistenceMinimumResponse* reply) override;
    ::grpc::Status DeleteSubsistenceMinimum(::grpc::ServerContext* context, const ::calc::DeleteSubsistenceMinimumRequest* request, ::calc::DeleteSubsistenceMinimumResponse* reply) override;
};

}
}

#endif
