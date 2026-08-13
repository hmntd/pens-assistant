#include <iostream>
#include <string>
#include <memory>
#include <grpcpp/grpcpp.h>
#include "grpc/calc_service_impl.h"

void RunServer() {
    std::string server_address("0.0.0.0:50051");
    calc::grpc_service::CalcServiceImpl service;

    grpc::ServerBuilder builder;
    builder.AddListeningPort(server_address, grpc::InsecureServerCredentials());
    builder.RegisterService(&service);
    
    std::unique_ptr<grpc::Server> server(builder.BuildAndStart());
    std::cout << "Calc gRPC Server listening on " << server_address << std::endl;
    server->Wait();
}

int main() {
    RunServer();
    return 0;
}