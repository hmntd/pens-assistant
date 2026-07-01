#include <iostream>
#include <thread>
#include <chrono>

int main() {
    std::cout << "Calc Microservice (C++) is successfully running!" << std::endl;
    std::cout << "Waiting for gRPC implementation..." << std::endl;

    while (true) {
        std::this_thread::sleep_for(std::chrono::seconds(60));
    }

    return 0;
}