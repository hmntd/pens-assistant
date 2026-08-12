#include <iostream>
#include <string>
#include <grpcpp/grpcpp.h>
#include "calc.grpc.pb.h"

using grpc::Server;
using grpc::ServerBuilder;
using grpc::ServerContext;
using grpc::Status;
using calc::CalcService;
using calc::HelloRequest;
using calc::HelloResponse;

class CalcServiceImpl final : public CalcService::Service {
    Status SayHello(ServerContext* context, const HelloRequest* request, HelloResponse* reply) override {
        std::cout << "[Calc] Received request from: " << request->name() << std::endl;
        std::string prefix("Hello World, ");
        reply->set_message("! It's C++ Calc service.");
        return Status::OK;
    }
};

void RunServer() {
    std::string server_address("0.0.0.0:50051");
    CalcServiceImpl service;

    ServerBuilder builder;
    builder.AddListeningPort(server_address, grpc::InsecureServerCredentials());
    builder.RegisterService(&service);
    
    std::unique_ptr<Server> server(builder.BuildAndStart());
    std::cout << "Calc gRPC Server listening on " << server_address << std::endl;
    server->Wait();
}

int main() {
    RunServer();
    return 0;
}