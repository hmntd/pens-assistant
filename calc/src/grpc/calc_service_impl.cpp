#include "calc_service_impl.h"
#include <iostream>

namespace calc {
namespace grpc_service {

CalcServiceImpl::CalcServiceImpl()
    : calculator_(repository::CoefficientRepository()), repo_(repository::CoefficientRepository()) {}

::grpc::Status CalcServiceImpl::CalculatePension(::grpc::ServerContext* context, const ::calc::PensionRequest* request, ::calc::PensionResponse* reply) {
    auto result = calculator_.calculate(request);
    reply->set_success(result.success);
    reply->set_estimated_monthly_pension(result.estimated_monthly_pension);
    reply->set_total_accumulated_capital(result.total_accumulated_capital);
    reply->set_calculation_breakdown(result.breakdown);
    reply->set_error_message(result.error_message);
    return ::grpc::Status::OK;
}

::grpc::Status CalcServiceImpl::ListCoefficients(::grpc::ServerContext* context, const ::calc::ListCoefficientsRequest* request, ::calc::ListCoefficientsResponse* reply) {
    auto records = repo_.listAll();
    reply->set_success(true);
    for (const auto& item : records) {
        auto* coef = reply->add_coefficients();
        coef->set_id(item.id);
        coef->set_year(item.year);
        coef->set_month(item.month);
        coef->set_coefficient(item.coefficient);
        coef->set_description(item.description);
    }
    reply->set_error_message("");
    return ::grpc::Status::OK;
}

::grpc::Status CalcServiceImpl::AddCoefficient(::grpc::ServerContext* context, const ::calc::AddCoefficientRequest* request, ::calc::AddCoefficientResponse* reply) {
    auto opt = repo_.add(request->year(), request->month(), request->coefficient(), request->description());
    if (opt.has_value()) {
        reply->set_success(true);
        auto* coef = reply->mutable_coefficient();
        coef->set_id(opt->id);
        coef->set_year(opt->year);
        coef->set_month(opt->month);
        coef->set_coefficient(opt->coefficient);
        coef->set_description(opt->description);
    } else {
        reply->set_success(false);
        reply->set_error_message("Failed to add coefficient record");
    }
    return ::grpc::Status::OK;
}

::grpc::Status CalcServiceImpl::UpdateCoefficient(::grpc::ServerContext* context, const ::calc::UpdateCoefficientRequest* request, ::calc::UpdateCoefficientResponse* reply) {
    auto opt = repo_.update(request->id(), request->year(), request->month(), request->coefficient(), request->description());
    if (opt.has_value()) {
        reply->set_success(true);
        auto* coef = reply->mutable_coefficient();
        coef->set_id(opt->id);
        coef->set_year(opt->year);
        coef->set_month(opt->month);
        coef->set_coefficient(opt->coefficient);
        coef->set_description(opt->description);
    } else {
        reply->set_success(false);
        reply->set_error_message("Coefficient record not found");
    }
    return ::grpc::Status::OK;
}

::grpc::Status CalcServiceImpl::DeleteCoefficient(::grpc::ServerContext* context, const ::calc::DeleteCoefficientRequest* request, ::calc::DeleteCoefficientResponse* reply) {
    bool removed = repo_.remove(request->id());
    if (removed) {
        reply->set_success(true);
        reply->set_error_message("");
    } else {
        reply->set_success(false);
        reply->set_error_message("Coefficient record not found");
    }
    return ::grpc::Status::OK;
}

::grpc::Status CalcServiceImpl::SyncAverageSalaries(::grpc::ServerContext* context, const ::calc::SyncAverageSalariesRequest* request, ::calc::SyncAverageSalariesResponse* reply) {
    int processed = 0;
    for (int i = 0; i < request->salaries_size(); ++i) {
        const auto& item = request->salaries(i);
        if (repo_.upsertAverageSalary(item.year(), item.month(), item.amount())) {
            processed++;
        }
    }
    reply->set_success(true);
    reply->set_processed_count(processed);
    reply->set_error_message("");
    return ::grpc::Status::OK;
}

}
}
