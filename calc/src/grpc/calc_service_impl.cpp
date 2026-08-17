#include "calc_service_impl.h"
#include <iostream>

namespace calc {
namespace grpc_service {

CalcServiceImpl::CalcServiceImpl()
    : CalcServiceImpl(false) {}

CalcServiceImpl::CalcServiceImpl(bool mock_mode)
    : calculator_(repository::CoefficientRepository(mock_mode)), repo_(repository::CoefficientRepository(mock_mode)) {}

::grpc::Status CalcServiceImpl::CalculatePension(::grpc::ServerContext* context, const ::calc::CalculatePensionRequest* request, ::calc::CalculatePensionResponse* reply) {
    auto result = calculator_.calculate(request);

    reply->set_success(result.success);
    reply->set_final_pension(result.final_pension);
    reply->set_base_pension(result.base_pension);
    reply->set_zp_macroeconomic_average(result.zp_macroeconomic_average);
    reply->set_kz_wage_coefficient(result.kz_wage_coefficient);
    reply->set_ks_service_coefficient(result.ks_service_coefficient);
    reply->set_total_service_months(result.total_service_months);
    reply->set_pension_type_modifier(result.pension_type_modifier);
    reply->set_extra_service_allowance(result.extra_service_allowance);
    reply->set_total_benefit_surcharges(result.total_benefit_surcharges);
    reply->set_pre_clamped_pension(result.pre_clamped_pension);
    reply->set_is_minimum_clamped(result.is_minimum_clamped);
    reply->set_is_maximum_clamped(result.is_maximum_clamped);

    for (const auto& b : result.applied_benefits) {
        auto* detail = reply->add_applied_benefits();
        detail->set_benefit(b.type);
        detail->set_name(b.name);
        detail->set_amount(b.amount);
    }

    for (const auto& log_line : result.calculation_logs) {
        reply->add_calculation_logs(log_line);
    }

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
    auto created = repo_.add(request->year(), request->month(), request->coefficient(), request->description());
    if (created.has_value()) {
        reply->set_success(true);
        auto* coef = reply->mutable_coefficient();
        coef->set_id(created->id);
        coef->set_year(created->year);
        coef->set_month(created->month);
        coef->set_coefficient(created->coefficient);
        coef->set_description(created->description);
        reply->set_error_message("");
    } else {
        reply->set_success(false);
        reply->set_error_message("Failed to insert pension coefficient record");
    }
    return ::grpc::Status::OK;
}

::grpc::Status CalcServiceImpl::UpdateCoefficient(::grpc::ServerContext* context, const ::calc::UpdateCoefficientRequest* request, ::calc::UpdateCoefficientResponse* reply) {
    auto updated = repo_.update(request->id(), request->year(), request->month(), request->coefficient(), request->description());
    if (updated.has_value()) {
        reply->set_success(true);
        auto* coef = reply->mutable_coefficient();
        coef->set_id(updated->id);
        coef->set_year(updated->year);
        coef->set_month(updated->month);
        coef->set_coefficient(updated->coefficient);
        coef->set_description(updated->description);
        reply->set_error_message("");
    } else {
        reply->set_success(false);
        reply->set_error_message("Failed to update pension coefficient record");
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

::grpc::Status CalcServiceImpl::UpsertSubsistenceMinimum(::grpc::ServerContext* context, const ::calc::UpsertSubsistenceMinimumRequest* request, ::calc::UpsertSubsistenceMinimumResponse* reply) {
    bool ok = repo_.upsertSubsistenceLimits(request->year(), request->for_disabled_persons(), request->general_minimum());
    if (ok) {
        reply->set_success(true);
        reply->set_message("Subsistence minimum updated successfully for year " + std::to_string(request->year()));
        reply->set_error_message("");
    } else {
        reply->set_success(false);
        reply->set_message("");
        reply->set_error_message("Failed to upsert subsistence minimum record in database");
    }
    return ::grpc::Status::OK;
}

::grpc::Status CalcServiceImpl::ListSubsistenceMinimums(::grpc::ServerContext* context, const ::calc::ListSubsistenceMinimumsRequest* request, ::calc::ListSubsistenceMinimumsResponse* reply) {
    auto records = repo_.listSubsistenceMinimums();
    reply->set_success(true);
    for (const auto& item : records) {
        auto* rec = reply->add_records();
        rec->set_id(item.id);
        rec->set_year(item.year);
        rec->set_for_disabled_persons(item.for_disabled_persons);
        rec->set_general_minimum(item.general_minimum);
    }
    reply->set_error_message("");
    return ::grpc::Status::OK;
}

::grpc::Status CalcServiceImpl::UpdateSubsistenceMinimum(::grpc::ServerContext* context, const ::calc::UpdateSubsistenceMinimumRequest* request, ::calc::UpdateSubsistenceMinimumResponse* reply) {
    auto updated = repo_.updateSubsistenceMinimum(request->id(), request->year(), request->for_disabled_persons(), request->general_minimum());
    if (updated.has_value()) {
        reply->set_success(true);
        auto* rec = reply->mutable_record();
        rec->set_id(updated->id);
        rec->set_year(updated->year);
        rec->set_for_disabled_persons(updated->for_disabled_persons);
        rec->set_general_minimum(updated->general_minimum);
        reply->set_error_message("");
    } else {
        reply->set_success(false);
        reply->set_error_message("Failed to update subsistence minimum record");
    }
    return ::grpc::Status::OK;
}

::grpc::Status CalcServiceImpl::DeleteSubsistenceMinimum(::grpc::ServerContext* context, const ::calc::DeleteSubsistenceMinimumRequest* request, ::calc::DeleteSubsistenceMinimumResponse* reply) {
    bool removed = repo_.deleteSubsistenceMinimum(request->id());
    if (removed) {
        reply->set_success(true);
        reply->set_error_message("");
    } else {
        reply->set_success(false);
        reply->set_error_message("Subsistence minimum record not found");
    }
    return ::grpc::Status::OK;
}

}
}
