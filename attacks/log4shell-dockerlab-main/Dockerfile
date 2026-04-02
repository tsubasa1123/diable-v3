FROM gradle:7.3.1-jdk17-alpine AS builder

COPY --chown=gradle:gradle ./log4shell-vulnerable-app/ /home/gradle/src
WORKDIR /home/gradle/src

RUN gradle bootJar --no-daemon


FROM openjdk:8u181-jdk-alpine

EXPOSE 8080

WORKDIR /app

COPY --from=builder /home/gradle/src/build/libs/*.jar /app/app.jar

ENV JAVA_OPTS=""
ENV VULNERABLE_PORT=8080

CMD ["sh","-c","java $JAVA_OPTS -jar /app/app.jar --server.port=${VULNERABLE_PORT}"]
