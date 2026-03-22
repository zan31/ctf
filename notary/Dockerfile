FROM reg.dctf.si/ubuntu:25.04 AS builder
WORKDIR /app
ENV DEBIAN_FRONTEND=noninteractive

RUN apt-get update && apt-get install -y --no-install-recommends \
    build-essential \
    make \
    clang

COPY . .
RUN make


FROM reg.dctf.si/ubuntu:25.04
WORKDIR /app

RUN apt-get update && \
    apt-get install -y --no-install-recommends build-essential socat && \
    mkdir users files

COPY --from=builder /app/bin/main /app/main

CMD ["socat", "TCP-LISTEN:1337,reuseaddr,fork", "EXEC:/app/main"]
