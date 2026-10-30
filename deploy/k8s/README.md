# Kubernetes Deployment & Dual-Mode Orchestration Guide

This directory (`deploy/k8s/`) provides declarative, production-grade Kubernetes manifests for the **Pens-Assistant** multi-container architecture:
- **Laravel + Inertia/Vue Web & API (`laravel-app`)** — PHP-FPM 8.5 + Nginx sidecar Pod
- **Laravel Horizon Queue Worker (`laravel-worker`)** — Redis-backed asynchronous job processor with graceful drain hooks
- **Laravel Reverb (`laravel-reverb`)** — First-party WebSocket server for real-time notifications
- **C++ gRPC Pension Calculation Engine (`calc-engine` / `calc`)** — High-performance C++20 gRPC server on port `50051`
- **Python gRPC OCR Service (`ocr-engine` / `ocr`)** — Tesseract & EasyOCR document parser on port `50052`
- **PostgreSQL 16 (`postgres` StatefulSet)** — Persistent relational storage automatically provisioning `crm_db` and `calc_db`
- **Redis 7 (`redis`)** — In-memory cache, session store, and Horizon queue broker

---

## 1. Architectural Rationale: Why Dual-Mode (Docker Compose vs. Kubernetes)?

### Single-Node Production: Docker Compose + PHP Deployer
In our current single-server AWS production environment, the project intentionally runs via [`docker-compose.yml`](../../docker-compose.yml) and [`deploy.php`](../../deploy.php):
1. **Zero Control-Plane Overhead:** A Kubernetes control plane (`kube-apiserver`, `etcd`, `kube-controller-manager`, `kube-scheduler`, CNI, and `kubelet`) consumes 1.5–2.5 GB of RAM before a single application container starts. On a cost-effective single AWS EC2 instance, Docker Compose dedicates nearly 100% of host CPU and RAM directly to PHP-FPM, the C++ gRPC engine, PostgreSQL, and Redis.
2. **Infrastructure Cost Efficiency:** Running managed Kubernetes on AWS (EKS control plane + multi-AZ NAT Gateways + Application Load Balancers) adds \$100–\$150+/month in baseline infrastructure overhead compared to a single EC2 instance with host-level Let's Encrypt TLS termination.
3. **Atomic Zero-Downtime Symlink Releases:** PHP Deployer (`deploy.php`) manages release directories (`/releases/N`, `/current` symlink, `/shared/crm/storage`, `/shared/.env`) and triggers `horizon:terminate` and `opcache:clear` in seconds without pushing multi-gigabyte images to a remote registry on every commit.

### Multi-Node Cloud Scale: Kubernetes (`deploy/k8s/`)
When horizontal scaling across multiple nodes is required, the manifests in `deploy/k8s/` provide cloud-agnostic orchestration:
- **Horizontal Pod Autoscaling (`hpa.yaml`):** Automatically scales `laravel-app` (2–6 replicas) and `grpc-engine` (2–8 replicas) on CPU and Memory utilization.
- **Self-Healing & Readiness Gates:** Kubernetes `livenessProbe` and `readinessProbe` checks verify `/up` (HTTP), port `9000` (FastCGI), port `50051` (C++ gRPC), `pg_isready` (PostgreSQL), and `horizon:status` (Queue Worker) before routing traffic.
- **Internal DNS Mirroring:** Kubernetes `ClusterIP` Services (`crm-db`, `calc-db`, `redis`, `calc`, `ocr`, `laravel-app`, `laravel-reverb`) mirror the service names in `docker-compose.yml`, requiring zero changes to application code.

---

## 2. Directory Layout

```text
deploy/k8s/
├── namespace.yaml                  # Dedicated 'pens-assistant' namespace
├── configmap.yaml                  # App env vars, Nginx FastCGI config, Postgres multi-DB init SQL
├── secret.example.yaml             # Committed template for Secret (copy to gitignored secret.yaml)
├── postgres-statefulset.yaml       # PostgreSQL 16 StatefulSet + PVC + crm-db / calc-db Services
├── redis-deployment.yaml           # Redis 7 Deployment + ClusterIP Service
├── calc-engine-deployment.yaml     # C++ Calc Engine (50051) Deployment + calc / calc-engine Services
├── ocr-engine-deployment.yaml      # Python OCR Engine (50052) Deployment + ocr / ocr-engine Services
├── laravel-app-deployment.yaml     # Laravel PHP-FPM + Nginx sidecar Deployment, PVC & Service
├── laravel-worker-deployment.yaml  # Laravel Horizon worker Deployment with preStop graceful drain
├── laravel-reverb-deployment.yaml  # Laravel Reverb WebSocket Deployment + Service (8080)
├── ingress.yaml                    # Nginx Ingress routing '/' -> Laravel and '/app(s)' -> Reverb
├── hpa.yaml                        # HorizontalPodAutoscalers (autoscaling/v2) for Web & gRPC Engine
├── kustomization.yaml              # Kustomize manifest for single-command deployment
└── README.md                       # This guide
```

---

## 3. Building Self-Contained Images for Kubernetes

In Docker Compose mode, `docker compose build crm` uses `INSTALL_DEPS=false` (the default in [`crm/Dockerfile`](../../crm/Dockerfile)) because code and dependencies are mounted via host volumes.

For Kubernetes, build **self-contained, immutable images** from the repository root with `--build-arg INSTALL_DEPS=true` so Composer vendors and compiled Vite assets (`public/build`) are baked into `pens-assistant-crm:latest`:

```bash
# 1. Build the Laravel CRM image (used by laravel-app, laravel-worker, and laravel-reverb)
docker build \
  --build-arg INSTALL_DEPS=true \
  -t pens-assistant-crm:latest \
  -f crm/Dockerfile .

# 2. Build the C++ gRPC Calculation Engine image
docker build \
  -t pens-assistant-calc:latest \
  -f calc/Dockerfile .

# 3. Build the Python gRPC OCR Service image
docker build \
  -t pens-assistant-ocr:latest \
  -f ocr/Dockerfile .
```

---

## 4. Step-by-Step Local Validation (Minikube or Kind)

### Option A: Using Minikube
```bash
# 1. Start Minikube with sufficient resources and enable addons
minikube start --cpus=4 --memory=6144
minikube addons enable ingress
minikube addons enable metrics-server

# 2. Load the locally built images into Minikube (or build directly inside minikube docker-env)
minikube image load pens-assistant-crm:latest
minikube image load pens-assistant-calc:latest
minikube image load pens-assistant-ocr:latest
```

### Option B: Using Kind (Kubernetes in Docker)
```bash
# 1. Create a Kind cluster
kind create cluster --name pens-assistant

# 2. Load the locally built images into the Kind nodes
kind load docker-image pens-assistant-crm:latest --name pens-assistant
kind load docker-image pens-assistant-calc:latest --name pens-assistant
kind load docker-image pens-assistant-ocr:latest --name pens-assistant

# 3. Install the Nginx Ingress Controller (if not already installed)
kubectl apply -f https://raw.githubusercontent.com/kubernetes/ingress-nginx/main/deploy/static/provider/kind/deploy.yaml
```

### Apply the Manifests
First, initialize your local Kubernetes secret from the template (note that `deploy/k8s/secret.yaml` is in `.gitignore` so real credentials are never committed to Git):

```bash
# Option 1: Copy from template for local Minikube/Kind testing
cp deploy/k8s/secret.example.yaml deploy/k8s/secret.yaml

# Option 2 (Production): Create secret directly from your .env file without writing secret.yaml
kubectl create namespace pens-assistant --dry-run=client -o yaml | kubectl apply -f -
kubectl create secret generic pens-assistant-secrets --from-env-file=crm/.env -n pens-assistant
```

Then deploy the stack using either Kustomize (`-k`) or standard directory apply (`-f`):

```bash
# Recommended: Apply in dependency order via Kustomize
kubectl apply -k deploy/k8s/

# Alternative: Apply namespace first, then all manifests
kubectl apply -f deploy/k8s/namespace.yaml
kubectl apply -f deploy/k8s/
```

---

## 5. Verification & Troubleshooting Commands

### 1. Check Pod Status and Readiness
```bash
kubectl get pods -n pens-assistant -o wide
```
Wait until all pods reach `Running` and `READY` (`2/2` for `laravel-app` pods, `1/1` for worker, reverb, grpc-engine, postgres, and redis).

### 2. Inspect Services, Ingress, PersistentVolumeClaims & HPA
```bash
kubectl get svc,ingress,pvc,hpa -n pens-assistant
```

### 3. Check Container Logs
```bash
# Laravel Web App (PHP-FPM & Nginx containers)
kubectl logs -n pens-assistant deployment/laravel-app -c php-fpm --tail=50
kubectl logs -n pens-assistant deployment/laravel-app -c nginx --tail=50

# Laravel Horizon Queue Worker
kubectl logs -n pens-assistant deployment/laravel-worker --tail=50

# Laravel Reverb WebSocket Server
kubectl logs -n pens-assistant deployment/laravel-reverb --tail=50

# C++ gRPC Calculation Engine & Python OCR Engine
kubectl logs -n pens-assistant deployment/calc-engine --tail=50
kubectl logs -n pens-assistant deployment/ocr-engine --tail=50
```

### 4. Test HTTP & Health Check Endpoints
Add `pens-assistant.local` to `/etc/hosts` pointing to your Minikube IP (`echo "$(minikube ip) pens-assistant.local" | sudo tee -a /etc/hosts`) or use `kubectl port-forward`:

```bash
# Port-forward the Laravel service locally
kubectl port-forward -n pens-assistant svc/laravel-app 8080:80 &

# Verify Laravel health endpoint (/up returns HTTP 200)
curl -i http://localhost:8080/up

# Test via Nginx Ingress using Host header
curl -i -H "Host: pens-assistant.local" http://$(minikube ip)/up
```

### 5. Test WebSocket Routing to Laravel Reverb
```bash
# Port-forward Reverb or test via Ingress on /app/<REVERB_APP_KEY>
curl -i \
  -H "Host: pens-assistant.local" \
  -H "Connection: Upgrade" \
  -H "Upgrade: websocket" \
  -H "Sec-WebSocket-Version: 13" \
  -H "Sec-WebSocket-Key: dGhlIHNhbXBsZSBub25jZQ==" \
  "http://$(minikube ip)/app/pens-assistant-local-key?protocol=7&client=js&version=8.4.0"
```
A `101 Switching Protocols` response confirms Nginx Ingress is properly upgrading and routing WebSocket connections to `laravel-reverb:8080`.

