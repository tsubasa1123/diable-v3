FROM node:18-alpine

LABEL maintainer="Thiané DIA - DSI ISFA 2025-2026"
LABEL description="DIABLE v3.0 - GraphQL Injection Lab"
LABEL diable.lab="graphql-injection"
LABEL diable.difficulty="medium-hard"
LABEL diable.tag="GraphQL,API,Data"

WORKDIR /app

COPY package.json ./
RUN npm install --production

COPY src/ ./src/

EXPOSE 3000

HEALTHCHECK --interval=30s --timeout=10s --start-period=5s --retries=3 \
  CMD wget -qO- http://localhost:3000/health || exit 1

CMD ["node", "src/server.js"]
