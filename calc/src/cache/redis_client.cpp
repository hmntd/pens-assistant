#include "redis_client.h"
#include <cstdlib>
#include <cstring>
#include <sstream>
#include <sys/socket.h>
#include <netdb.h>
#include <unistd.h>
#include <fcntl.h>
#include <sys/select.h>

namespace calc
{
    namespace cache
    {

        std::string RedisClient::getHost()
        {
            const char *host = std::getenv("REDIS_HOST");
            return (host && std::strlen(host) > 0) ? std::string(host) : "redis";
        }

        int RedisClient::getPort()
        {
            const char *port = std::getenv("REDIS_PORT");
            if (port && std::strlen(port) > 0)
            {
                int p = std::atoi(port);
                if (p > 0)
                {
                    return p;
                }
            }
            return 6379;
        }

        bool RedisClient::isEnabled()
        {
            const char *disabled = std::getenv("REDIS_DISABLED");
            if (disabled && (std::string(disabled) == "1" || std::string(disabled) == "true"))
            {
                return false;
            }
            return true;
        }

        namespace
        {
            int connectWithTimeout(const std::string &host, int port, int timeout_ms)
            {
                struct addrinfo hints{}, *res = nullptr;
                hints.ai_family = AF_UNSPEC;
                hints.ai_socktype = SOCK_STREAM;

                std::string port_str = std::to_string(port);
                if (getaddrinfo(host.c_str(), port_str.c_str(), &hints, &res) != 0 || !res)
                {
                    return -1;
                }

                int sock = socket(res->ai_family, res->ai_socktype, res->ai_protocol);
                if (sock < 0)
                {
                    freeaddrinfo(res);
                    return -1;
                }

                int flags = fcntl(sock, F_GETFL, 0);
                if (flags >= 0)
                {
                    fcntl(sock, F_SETFL, flags | O_NONBLOCK);
                }

                int rc = connect(sock, res->ai_addr, res->ai_addrlen);
                freeaddrinfo(res);

                if (rc < 0)
                {
                    if (errno != EINPROGRESS)
                    {
                        close(sock);
                        return -1;
                    }

                    fd_set wfds;
                    FD_ZERO(&wfds);
                    FD_SET(sock, &wfds);

                    struct timeval tv{};
                    tv.tv_sec = timeout_ms / 1000;
                    tv.tv_usec = (timeout_ms % 1000) * 1000;

                    rc = select(sock + 1, nullptr, &wfds, nullptr, &tv);
                    if (rc <= 0)
                    {
                        close(sock);
                        return -1;
                    }

                    int so_error = 0;
                    socklen_t len = sizeof(so_error);
                    if (getsockopt(sock, SOL_SOCKET, SO_ERROR, &so_error, &len) < 0 || so_error != 0)
                    {
                        close(sock);
                        return -1;
                    }
                }

                if (flags >= 0)
                {
                    fcntl(sock, F_SETFL, flags);
                }

                struct timeval rw_tv{};
                rw_tv.tv_sec = timeout_ms / 1000;
                rw_tv.tv_usec = (timeout_ms % 1000) * 1000;
                setsockopt(sock, SOL_SOCKET, SO_RCVTIMEO, &rw_tv, sizeof(rw_tv));
                setsockopt(sock, SOL_SOCKET, SO_SNDTIMEO, &rw_tv, sizeof(rw_tv));

                return sock;
            }

            bool readLine(int sock, std::string &buffer, std::string &out_line)
            {
                while (true)
                {
                    auto pos = buffer.find("\r\n");
                    if (pos != std::string::npos)
                    {
                        out_line = buffer.substr(0, pos);
                        buffer.erase(0, pos + 2);
                        return true;
                    }

                    char chunk[1024];
                    ssize_t n = recv(sock, chunk, sizeof(chunk), 0);
                    if (n <= 0)
                    {
                        return false;
                    }
                    buffer.append(chunk, static_cast<size_t>(n));
                }
            }

            bool readExact(int sock, std::string &buffer, size_t count, std::string &out_bytes)
            {
                while (buffer.size() < count)
                {
                    char chunk[1024];
                    ssize_t n = recv(sock, chunk, sizeof(chunk), 0);
                    if (n <= 0)
                    {
                        return false;
                    }
                    buffer.append(chunk, static_cast<size_t>(n));
                }
                out_bytes = buffer.substr(0, count);
                buffer.erase(0, count);
                return true;
            }
        }

        std::optional<std::string> RedisClient::executeCommand(const std::vector<std::string> &args)
        {
            if (!isEnabled() || args.empty())
            {
                return std::nullopt;
            }

            int sock = connectWithTimeout(getHost(), getPort(), 150);
            if (sock < 0)
            {
                return std::nullopt;
            }

            std::ostringstream cmd;
            cmd << "*" << args.size() << "\r\n";
            for (const auto &arg : args)
            {
                cmd << "$" << arg.size() << "\r\n"
                    << arg << "\r\n";
            }

            std::string payload = cmd.str();
            size_t total_sent = 0;
            while (total_sent < payload.size())
            {
                ssize_t sent = send(sock, payload.data() + total_sent, payload.size() - total_sent, 0);
                if (sent <= 0)
                {
                    close(sock);
                    return std::nullopt;
                }
                total_sent += static_cast<size_t>(sent);
            }

            std::string buffer;
            std::string first_line;
            if (!readLine(sock, buffer, first_line) || first_line.empty())
            {
                close(sock);
                return std::nullopt;
            }

            char prefix = first_line[0];
            std::string rest = first_line.substr(1);

            if (prefix == '+' || prefix == ':')
            {
                close(sock);
                return rest;
            }
            else if (prefix == '$')
            {
                int len = std::atoi(rest.c_str());
                if (len < 0)
                {
                    close(sock);
                    return std::nullopt;
                }
                std::string bulk;
                if (!readExact(sock, buffer, static_cast<size_t>(len) + 2, bulk))
                {
                    close(sock);
                    return std::nullopt;
                }
                close(sock);
                return bulk.substr(0, static_cast<size_t>(len));
            }

            close(sock);
            return std::nullopt;
        }

        std::optional<std::string> RedisClient::get(const std::string &key)
        {
            return executeCommand({"GET", key});
        }

        bool RedisClient::set(const std::string &key, const std::string &value, int ttl_seconds)
        {
            if (ttl_seconds > 0)
            {
                auto res = executeCommand({"SETEX", key, std::to_string(ttl_seconds), value});
                return res.has_value() && *res == "OK";
            }
            auto res = executeCommand({"SET", key, value});
            return res.has_value() && *res == "OK";
        }

        bool RedisClient::del(const std::string &key)
        {
            auto res = executeCommand({"DEL", key});
            return res.has_value();
        }

        bool RedisClient::delMany(const std::vector<std::string> &keys)
        {
            if (keys.empty())
            {
                return true;
            }
            std::vector<std::string> args;
            args.reserve(keys.size() + 1);
            args.push_back("DEL");
            for (const auto &k : keys)
            {
                args.push_back(k);
            }
            auto res = executeCommand(args);
            return res.has_value();
        }

    }
}

