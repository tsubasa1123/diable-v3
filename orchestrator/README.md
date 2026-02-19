# Orchestrator - DIABLE v3.0

**Work Package 1** - Orchestration Kubernetes

---

##  Description

Orchestration des containers de labs avec Kubernetes :
- Déploiement dynamique des labs
- Scaling automatique
- Monitoring et health checks
- Isolation réseau
- Gestion des ressources

---

##  Technologies

- Kubernetes
- Helm Charts
- Prometheus (monitoring)
- Grafana (dashboards)
- Istio (service mesh, optionnel)

---

##  Déploiement

```bash
cd orchestrator

# Déployer avec Helm
helm install diable ./helm/diable

# Ou avec kubectl
kubectl apply -f manifests/
```

---

##  Structure

```
orchestrator/
├── manifests/          # YAML Kubernetes
│   ├── deployments/   # Deployments des labs
│   ├── services/      # Services
│   ├── ingress/       # Ingress rules
│   └── configmaps/    # Configuration
├── helm/              # Helm charts
│   └── diable/
│       ├── Chart.yaml
│       ├── values.yaml
│       └── templates/
└── monitoring/        # Prometheus/Grafana
```

---

##  Configuration

### Ressources par Lab

```yaml
resources:
  requests:
    memory: "256Mi"
    cpu: "100m"
  limits:
    memory: "512Mi"
    cpu: "500m"
```

---

##  Contact

**Responsable WP2:** Kantame, Wail et Thiané  
**Email:** wp1@diable-project.fr
