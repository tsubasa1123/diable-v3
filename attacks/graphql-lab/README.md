# 🕸️ GraphQL Injection Lab — DIABLE v3.0

**Auteure :** Thiané DIA
**Promo :** DSI ISFA 2025-2026
**Difficulté :** Moyen → Difficile
**Tags :** GraphQL, API, Data Exfiltration, Auth Bypass

---

## 📋 Description

Ce lab explore les vulnérabilités des APIs GraphQL modernes. GraphQL est massivement utilisé par Facebook, GitHub, Shopify, Twitter — et présente des risques spécifiques liés à sa flexibilité.

---

## 🎯 Scénarios

| # | Attaque | Difficulté | Flag |
|---|---------|-----------|------|
| 1 | Introspection Attack | Moyen | `DIABLE{graphql_introspection_exposed}` |
| 2 | Data Exfiltration | Difficile | `DIABLE{graphql_data_exfiltration_pwned}` |
| 3 | Auth Bypass via Mutation | Difficile | `DIABLE{graphql_auth_bypass_admin}` |

---

## 🚀 Lancement

```bash
# Depuis la racine du projet DIABLE
docker-compose up -d graphql-lab

# Accès
http://localhost:8085
http://localhost:8085/graphql  # Interface GraphiQL (vulnérabilité en soi)
```

---

## 🛡️ Contre-mesures

| Vulnérabilité | Contre-mesure |
|--------------|---------------|
| Introspection exposée | `introspection: false` en production |
| Data exfiltration | graphql-shield, autorisation par champ |
| Auth bypass | Tokens signés cryptographiquement (JWT) |

---

*DIABLE v3.0 — Usage pédagogique uniquement*
